<?php

namespace App\Services;

use App\Contracts\PermitApplicationContract;
use App\Models\Application;
use App\Models\Billing;
use App\Models\BillingItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function __construct(
        protected AssessmentService $assessmentService,
    ) {}

    /**
     * Auto-generate a billing for a BP or OP application from its finalized
     * assessments and move the application to 'billed'. Called right after
     * assessment finalization. No-op unless status is engineering_assessed
     * and no unpaid billing exists yet.
     */
    public function generateFor(PermitApplicationContract $application): void
    {
        if ($application->status !== 'engineering_assessed') {
            return;
        }

        if ($application->billings()->where('status', 'unpaid')->exists()) {
            $application->update(['status' => 'billed']);
            return;
        }

        DB::transaction(function () use ($application) {
            $assessments = $application->assessments()->with('assessmentItems')
                ->where('status', 'finalized')
                ->get();

            $counter = Billing::withTrashed()
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count() + 1;

            $billingNumber = sprintf('BL-%s-%s-%05d', now()->format('Y'), now()->format('m'), $counter);

            $morphType = $application->getPermitTypeCode() === 'OP' ? 'op' : 'bp';

            $billing = Billing::create([
                'applicationable_type' => $morphType,
                'applicationable_id' => $application->id,
                'application_id' => $application->id,
                'billing_number' => $billingNumber,
                'total_amount' => 0,
                'status' => 'unpaid',
                'generated_by' => Auth::id(),
            ]);

            $totalAmount = 0;
            $sortOrder = 0;

            foreach ($assessments as $assessment) {
                $items = $assessment->assessmentItems()->where('is_active', true)->get();
                $grouped = $items->groupBy(fn ($item) => explode('.', $item->fee_code)[0] ?? $item->fee_code);

                foreach ($grouped as $category => $categoryItems) {
                    $categoryTotal = $categoryItems->sum('amount') + $categoryItems->sum('inspection_fee');
                    $totalAmount += $categoryTotal;

                    BillingItem::create([
                        'billing_id' => $billing->id,
                        'category' => $category,
                        'description' => $categoryItems->first()->description,
                        'amount' => $categoryTotal,
                        'sort_order' => $sortOrder++,
                    ]);
                }

                if ($assessment->filing_fee > 0) {
                    $totalAmount += $assessment->filing_fee;
                    BillingItem::create([
                        'billing_id' => $billing->id,
                        'category' => 'OTHER',
                        'description' => 'Filing Fee',
                        'amount' => $assessment->filing_fee,
                        'sort_order' => $sortOrder++,
                    ]);
                }

                if ($assessment->processing_fee > 0) {
                    $totalAmount += $assessment->processing_fee;
                    BillingItem::create([
                        'billing_id' => $billing->id,
                        'category' => 'OTHER',
                        'description' => 'Processing Fee',
                        'amount' => $assessment->processing_fee,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            $billing->update(['total_amount' => $totalAmount]);
            $application->update(['status' => 'billed']);
        });

        activity()->causedBy(Auth::user())->performedOn($application)->log('Billing generated');
    }

    /**
     * Generate a billing record for an application based on its finalized assessments.
     */
    public function generateBilling(Application $application): Billing
    {
        return DB::transaction(function () use ($application) {
            $billingNumber = $this->getBillingNumber();
            $summary = $this->assessmentService->getSummary($application);

            $billing = Billing::create([
                'application_id' => $application->id,
                'billing_number' => $billingNumber,
                'total_amount' => $summary['grand_total'],
                'status' => 'unpaid',
                'generated_by' => auth()->id(),
            ]);

            // Create billing items from assessment summary
            $sortOrder = 0;
            foreach ($summary['assessments'] as $type => $data) {
                foreach ($data['items'] as $item) {
                    $billing->billingItems()->create([
                        'category' => $type,
                        'description' => $item->description,
                        'amount' => $item->amount,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            return $billing->fresh(['billingItems']);
        });
    }

    /**
     * Generate a billing number in YYYY-MM-NNNNN format.
     */
    public function getBillingNumber(): string
    {
        $year = (int) now()->format('Y');
        $month = (int) now()->format('m');

        $maxCounter = DB::table('billings')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->lockForUpdate()
            ->count();

        $nextCounter = $maxCounter + 1;

        return sprintf('%04d-%02d-%05d', $year, $month, $nextCounter);
    }

    /**
     * Reprint an existing billing (returns the billing with its items).
     */
    public function reprintBilling(Billing $billing): Billing
    {
        return $billing->load('billingItems');
    }
}
