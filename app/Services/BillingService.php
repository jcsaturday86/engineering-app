<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Billing;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function __construct(
        protected AssessmentService $assessmentService,
    ) {}

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
