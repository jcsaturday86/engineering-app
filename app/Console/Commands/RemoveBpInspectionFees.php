<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Assessment;
use App\Models\AssessmentItem;
use App\Models\Billing;
use App\Models\BillingItem;
use App\Models\Collection;
use App\Models\CollectionDetail;
use App\Models\FeeCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveBpInspectionFees extends Command
{
    protected $signature = 'bp:remove-inspection-fees {--dry-run : Preview changes without writing them}';

    protected $description = 'Retroactively zero out Building Permit Electrical/Mechanical inspection fees and cascade the correction into Billing/Collection records.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $categoryIds = FeeCategory::whereIn('code', ['ELEC', 'MECH'])->pluck('id');

        $affectedItems = AssessmentItem::whereIn('fee_category_id', $categoryIds)
            ->where('inspection_fee', '>', 0)
            ->where('is_active', true)
            ->whereHas('assessment', function ($q) {
                $q->where('applicationable_type', 'bp')->where('assessment_type', 'building');
            })
            ->with('assessment')
            ->get();

        if ($affectedItems->isEmpty()) {
            $this->info('No BP Electrical/Mechanical assessment items with a nonzero inspection fee found. Nothing to do.');

            return self::SUCCESS;
        }

        $byApplication = $affectedItems->groupBy(fn ($item) => $item->assessment->applicationable_id);

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$affectedItems->count()} affected item(s) across {$byApplication->count()} application(s).");
        $this->newLine();

        foreach ($byApplication as $applicationId => $items) {
            $application = Application::find($applicationId);
            if (! $application) {
                $this->warn("Application #{$applicationId} not found, skipping.");

                continue;
            }

            $this->processApplication($application, $items, $dryRun);
        }

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AssessmentItem>  $items
     */
    private function processApplication(Application $application, $items, bool $dryRun): void
    {
        DB::beginTransaction();

        try {
            $billings = Billing::where('applicationable_type', 'bp')->where('applicationable_id', $application->id)->get();

            // Snapshot old totals before any writes, so Collections can be safely
            // matched against their untouched original amount_due.
            $oldBillingTotals = $billings->pluck('total_amount', 'id');
            $collections = Collection::whereIn('billing_id', $billings->pluck('id'))
                ->where('status', 'active')
                ->get();

            $itemsChanged = 0;
            $assessmentIds = $items->pluck('assessment_id')->unique();

            foreach ($items as $item) {
                $oldInspection = (float) $item->inspection_fee;
                $isElec = str_starts_with($item->fee_code, 'ELEC_');
                $newAmount = $isElec ? round((float) $item->amount - $oldInspection, 2) : (float) $item->amount;

                $item->update(['amount' => $newAmount, 'inspection_fee' => 0]);
                $itemsChanged++;
            }

            // Recompute total_amount for every affected (already-finalized) Assessment.
            $assessmentTotals = [];
            foreach (Assessment::whereIn('id', $assessmentIds)->get() as $assessment) {
                $oldTotal = (float) $assessment->total_amount;
                if ($oldTotal <= 0) {
                    continue; // not yet finalized — nothing stored to correct
                }

                $activeItems = $assessment->assessmentItems()->where('is_active', true)->get();
                $newTotal = round(
                    (float) $activeItems->sum('amount') + (float) $activeItems->sum('inspection_fee')
                    + (float) $assessment->filing_fee + (float) $assessment->processing_fee,
                    2
                );

                $assessment->update(['total_amount' => $newTotal]);
                $assessmentTotals[] = [$assessment->id, $oldTotal, $newTotal];
            }

            // Recompute each Billing's line items + total from the (now-corrected) assessment items,
            // mirroring BillingService::generateFor()'s exact grouping/formula.
            $billingChanges = [];
            $finalizedAssessments = $application->assessments()->with('assessmentItems')->where('status', 'finalized')->get();
            $groupedByFeeCode = $finalizedAssessments
                ->flatMap(fn ($a) => $a->assessmentItems->where('is_active', true))
                ->groupBy('fee_code');

            foreach ($billings as $billing) {
                $oldBillingTotal = (float) $billing->total_amount;
                $billingItems = $billing->billingItems;

                foreach ($billingItems as $billingItem) {
                    if (! $groupedByFeeCode->has($billingItem->category)) {
                        continue; // e.g. 'OTHER' filing/processing rows — untouched
                    }

                    $group = $groupedByFeeCode->get($billingItem->category);
                    $newCategoryTotal = round((float) $group->sum('amount') + (float) $group->sum('inspection_fee'), 2);

                    if ($newCategoryTotal != (float) $billingItem->amount) {
                        $billingItem->update(['amount' => $newCategoryTotal]);
                    }
                }

                $newBillingTotal = round((float) $billing->billingItems()->sum('amount'), 2);
                if ($newBillingTotal != $oldBillingTotal) {
                    $billing->update(['total_amount' => $newBillingTotal]);
                }

                $billingChanges[] = [$billing->billing_number, $oldBillingTotal, $newBillingTotal];
            }

            // Cascade into paid Collections whose amount_due still matches the original Billing total.
            $collectionChanges = [];
            $skippedCollections = [];

            foreach ($collections as $collection) {
                $newBilling = $billings->firstWhere('id', $collection->billing_id);
                $oldBillingTotalStr = $oldBillingTotals[$collection->billing_id] ?? null;

                // String comparison (not float) — both amount_due and total_amount are
                // decimal:2-cast strings, so this is exact and avoids float precision issues.
                if ($newBilling === null || $oldBillingTotalStr === null || (string) $collection->amount_due !== (string) $oldBillingTotalStr) {
                    $skippedCollections[] = $collection->or_number;

                    continue;
                }

                $oldAmountDue = (float) $collection->amount_due;
                $newAmountDue = (float) $newBilling->total_amount; // already updated in-place above
                $newChange = round(max(0, (float) $collection->amount_received - $newAmountDue), 2);

                $collection->update(['amount_due' => $newAmountDue, 'change_amount' => $newChange]);
                $collectionChanges[] = [$collection->or_number, $oldAmountDue, $newAmountDue];

                foreach ($collection->collectionDetails as $detail) {
                    if (! $groupedByFeeCode->has($detail->fee_category)) {
                        continue;
                    }

                    $group = $groupedByFeeCode->get($detail->fee_category);
                    $newDetailAmount = round((float) $group->sum('amount') + (float) $group->sum('inspection_fee'), 2);

                    if ($newDetailAmount != (float) $detail->amount) {
                        $detail->update(['amount' => $newDetailAmount]);
                    }
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
                activity()
                    ->performedOn($application)
                    ->withProperties([
                        'items_changed' => $itemsChanged,
                        'assessment_totals' => $assessmentTotals,
                        'billing_totals' => $billingChanges,
                        'collection_totals' => $collectionChanges,
                        'skipped_collections' => $skippedCollections,
                    ])
                    ->log('BP inspection fees retroactively removed');
            }

            $this->printSummary($application, $itemsChanged, $assessmentTotals, $billingChanges, $collectionChanges, $skippedCollections);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Failed processing {$application->application_number}: {$e->getMessage()}");
        }
    }

    private function printSummary(Application $application, int $itemsChanged, array $assessmentTotals, array $billingChanges, array $collectionChanges, array $skippedCollections): void
    {
        $this->line("<fg=cyan>{$application->application_number}</> — {$itemsChanged} item(s) corrected");

        foreach ($assessmentTotals as [$id, $old, $new]) {
            $this->line("  Assessment #{$id}: total_amount ₱" . number_format($old, 2) . ' → ₱' . number_format($new, 2));
        }
        foreach ($billingChanges as [$number, $old, $new]) {
            $this->line("  Billing {$number}: total_amount ₱" . number_format($old, 2) . ' → ₱' . number_format($new, 2));
        }
        foreach ($collectionChanges as [$orNumber, $old, $new]) {
            $this->line("  Collection OR {$orNumber}: amount_due ₱" . number_format($old, 2) . ' → ₱' . number_format($new, 2));
        }
        foreach ($skippedCollections as $orNumber) {
            $this->warn("  Skipped Collection OR {$orNumber} — amount_due no longer matches its Billing total (manual review needed).");
        }

        $this->newLine();
    }
}
