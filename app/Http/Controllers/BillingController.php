<?php

namespace App\Http\Controllers;

use App\Contracts\PermitApplicationContract;
use App\Models\Application;
use App\Models\Assessment;
use App\Models\Billing;
use App\Models\BillingItem;
use App\Models\OccupancyApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function index()
    {
        $bpApplications = Application::with('permitType', 'billings')
            ->where('status', 'engineering_assessed')
            ->latest()
            ->get();

        $opApplications = OccupancyApplication::with('applicationType', 'billings')
            ->where('status', 'engineering_assessed')
            ->latest()
            ->get();

        $applications = $bpApplications->concat($opApplications)->sortByDesc('created_at');

        return view('billing.index', compact('applications'));
    }

    // BP billing
    public function generate(Application $application)
    {
        return $this->doGenerate($application);
    }

    // OP billing
    public function generateOp(OccupancyApplication $occupancyApplication)
    {
        return $this->doGenerate($occupancyApplication);
    }

    private function doGenerate(PermitApplicationContract $application)
    {
        if (!in_array($application->status, ['engineering_assessed', 'billed'])) {
            return back()->with('error', 'Application is not ready for billing.');
        }

        DB::transaction(function () use ($application) {
            $assessments = $application->assessments()->with('assessmentItems')
                ->where('status', 'finalized')
                ->get();

            $counter = Billing::whereYear('created_at', now()->year)
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

        return back()->with('success', 'Billing statement generated.');
    }

    public function print(Billing $billing)
    {
        $billing->load('applicationable', 'billingItems');

        $application = $billing->applicationable;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.billing-statement', compact('billing', 'application'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("billing_{$billing->billing_number}.pdf");
    }
}
