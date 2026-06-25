<?php

namespace App\Http\Controllers;

use App\Contracts\PermitApplicationContract;
use App\Models\Application;
use App\Models\Assessment;
use App\Models\AssessmentItem;
use App\Models\FeeCategory;
use App\Models\FeeType;
use App\Models\OccupancyApplication;
use App\Notifications\AssessmentCompleteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    public function index()
    {
        $applications = Application::with('permitType')
            ->whereIn('status', ['submitted', 'zoning_assessed', 'engineering_assessed'])
            ->latest()
            ->paginate(20);

        return view('assessments.index', compact('applications'));
    }

    public function occupancyIndex()
    {
        $applications = OccupancyApplication::with('applicationType')
            ->whereIn('status', ['submitted', 'zoning_assessed', 'engineering_assessed'])
            ->latest()
            ->paginate(20);

        return view('assessments.occupancy-index', compact('applications'));
    }

    // BP assessment
    public function assess(Application $application)
    {
        return $this->doAssess($application, 'building', 'BP');
    }

    // OP assessment
    public function assessOp(OccupancyApplication $occupancyApplication)
    {
        return $this->doAssess($occupancyApplication, 'occupancy', 'OP');
    }

    private function doAssess(PermitApplicationContract $application, string $assessmentType, string $permitCode)
    {
        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => $permitCode === 'OP' ? 'op' : 'bp',
                'applicationable_id' => $application->id,
                'assessment_type' => $assessmentType,
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        $assessment->load('assessmentItems');

        $feeCategories = FeeCategory::with('feeTypes')
            ->whereHas('permitType', fn ($q) => $q->where('code', $permitCode))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $totals = $this->calculateTotals($assessment);
        $assessmentItems = $assessment->assessmentItems->where('is_active', true);

        $isOp = $permitCode === 'OP';

        return view('assessments.assess', compact('application', 'assessment', 'feeCategories', 'totals', 'assessmentItems', 'isOp'));
    }

    // BP add item
    public function addItem(Request $request, Application $application)
    {
        return $this->doAddItem($request, $application, 'building', 'BP');
    }

    // OP add item
    public function addItemOp(Request $request, OccupancyApplication $occupancyApplication)
    {
        return $this->doAddItem($request, $occupancyApplication, 'occupancy', 'OP');
    }

    private function doAddItem(Request $request, PermitApplicationContract $application, string $assessmentType, string $permitCode)
    {
        $validated = $request->validate([
            'fee_category_id' => 'required|exists:fee_categories,id',
            'fee_type_id' => 'nullable|exists:fee_types,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_fee' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => $permitCode === 'OP' ? 'op' : 'bp',
                'applicationable_id' => $application->id,
                'assessment_type' => $assessmentType,
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        $feeCategory = FeeCategory::find($validated['fee_category_id']);
        $feeType = $validated['fee_type_id'] ? FeeType::find($validated['fee_type_id']) : null;

        $feeCode = $feeType->code ?? $feeCategory->code;
        $description = ($validated['description'] ?? null) ?: ($feeType->name ?? $feeCategory->name);
        $amount = $validated['quantity'] * $validated['unit_fee'];

        AssessmentItem::create([
            'assessment_id' => $assessment->id,
            'fee_category_id' => $validated['fee_category_id'],
            'fee_type_id' => $validated['fee_type_id'],
            'fee_code' => $feeCode,
            'description' => $description,
            'quantity' => $validated['quantity'],
            'unit_fee' => $validated['unit_fee'],
            'excess_fee' => 0,
            'inspection_fee' => 0,
            'amount' => $amount,
        ]);

        return back()->with('success', 'Fee item added.');
    }

    public function removeItem(AssessmentItem $assessmentItem)
    {
        $assessmentItem->update(['is_active' => false]);
        $assessmentItem->delete();

        return back()->with('success', 'Fee item removed.');
    }

    // BP summary
    public function summary(Application $application)
    {
        return $this->doSummary($application);
    }

    // OP summary
    public function summaryOp(OccupancyApplication $occupancyApplication)
    {
        return $this->doSummary($occupancyApplication, true);
    }

    private function doSummary(PermitApplicationContract $application, bool $isOp = false)
    {
        $assessments = $application->assessments()->with('assessmentItems')->get();

        $summary = [];
        $grandTotal = 0;

        foreach ($assessments as $assessment) {
            $items = $assessment->assessmentItems->where('is_active', true);
            $grouped = $items->groupBy('fee_code');
            $total = $items->sum('amount') + $items->sum('inspection_fee');
            $grandTotal += $total;

            $summary[] = [
                'assessment' => $assessment,
                'items' => $items,
                'grouped' => $grouped,
                'total' => $total,
            ];
        }

        return view('assessments.summary', compact('application', 'summary', 'grandTotal', 'isOp'));
    }

    // BP finalize
    public function finalize(Application $application)
    {
        $this->doFinalize($application);
        return redirect()->route('assessments.index')->with('success', 'Assessment finalized.');
    }

    // OP finalize
    public function finalizeOp(OccupancyApplication $occupancyApplication)
    {
        $this->doFinalize($occupancyApplication);
        return redirect()->route('assessments.occupancy')->with('success', 'Assessment finalized.');
    }

    private function doFinalize(PermitApplicationContract $application)
    {
        $assessments = $application->assessments()->where('status', 'draft')->get();

        DB::transaction(function () use ($assessments, $application) {
            foreach ($assessments as $assessment) {
                $total = $assessment->assessmentItems()->where('is_active', true)->sum('amount')
                    + $assessment->assessmentItems()->where('is_active', true)->sum('inspection_fee')
                    + $assessment->filing_fee
                    + $assessment->processing_fee;

                $assessment->update([
                    'total_amount' => $total,
                    'status' => 'finalized',
                    'assessed_by' => Auth::id(),
                    'finalized_at' => now(),
                ]);
            }

            if (in_array($application->status, ['submitted', 'zoning_assessed'])) {
                $application->update([
                    'status' => 'engineering_assessed',
                    'assessed_by' => Auth::id(),
                    'assessed_at' => now(),
                ]);
            }
        });

        activity()->causedBy(Auth::user())->performedOn($application)->log('Assessment finalized');

        if ($application->client_user_id) {
            $application->clientUser->notify(new AssessmentCompleteNotification($application));
        }
    }

    // BP print
    public function print(Application $application)
    {
        return $this->doPrint($application);
    }

    // OP print
    public function printOp(OccupancyApplication $occupancyApplication)
    {
        return $this->doPrint($occupancyApplication);
    }

    private function doPrint(PermitApplicationContract $application)
    {
        $assessments = $application->assessments()->with('assessmentItems')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.assessment-summary', compact('application', 'assessments'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("assessment_{$application->application_number}.pdf");
    }

    private function calculateTotals(Assessment $assessment): array
    {
        $items = $assessment->assessmentItems->where('is_active', true);

        return [
            'subtotal' => $items->sum('amount'),
            'inspection' => $items->sum('inspection_fee'),
            'filing' => $assessment->filing_fee,
            'processing' => $assessment->processing_fee,
            'total' => $items->sum('amount') + $items->sum('inspection_fee') + $assessment->filing_fee + $assessment->processing_fee,
        ];
    }
}
