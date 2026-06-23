<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Assessment;
use App\Models\AssessmentItem;
use App\Models\FeeCategory;
use App\Models\FeeType;
use App\Notifications\AssessmentCompleteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    public function index()
    {
        $applications = Application::with('permitType')
            ->whereIn('status', ['zoning_assessed', 'engineering_assessed'])
            ->whereHas('permitType', fn ($q) => $q->where('code', 'BP'))
            ->latest()
            ->paginate(20);

        return view('assessments.index', compact('applications'));
    }

    public function occupancyIndex()
    {
        $applications = Application::with('permitType')
            ->whereIn('status', ['submitted', 'engineering_assessed'])
            ->whereHas('permitType', fn ($q) => $q->where('code', 'OP'))
            ->latest()
            ->paginate(20);

        return view('assessments.occupancy-index', compact('applications'));
    }

    public function assess(Application $application)
    {
        $permitCode = $application->permitType->code;
        $assessmentType = $permitCode === 'OP' ? 'occupancy' : 'building';

        $assessment = Assessment::firstOrCreate(
            ['application_id' => $application->id, 'assessment_type' => $assessmentType],
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

        return view('assessments.assess', compact('application', 'assessment', 'feeCategories', 'totals', 'assessmentItems'));
    }

    public function addItem(Request $request, Application $application)
    {
        $validated = $request->validate([
            'fee_category_id' => 'required|exists:fee_categories,id',
            'fee_type_id' => 'nullable|exists:fee_types,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_fee' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $permitCode = $application->permitType->code;
        $assessmentType = $permitCode === 'OP' ? 'occupancy' : 'building';

        $assessment = Assessment::firstOrCreate(
            ['application_id' => $application->id, 'assessment_type' => $assessmentType],
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

    public function summary(Application $application)
    {
        $assessments = Assessment::with('assessmentItems')
            ->where('application_id', $application->id)
            ->get();

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

        return view('assessments.summary', compact('application', 'summary', 'grandTotal'));
    }

    public function finalize(Application $application)
    {
        $assessments = Assessment::where('application_id', $application->id)->where('status', 'draft')->get();

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

            if (in_array($application->status, ['zoning_assessed', 'submitted'])) {
                $application->update([
                    'status' => 'engineering_assessed',
                    'assessed_by' => Auth::id(),
                    'assessed_at' => now(),
                ]);
            }
        });

        activity()->causedBy(Auth::user())->performedOn($application)->log('Assessment finalized');

        // Notify client user if linked
        if ($application->client_user_id) {
            $application->clientUser->notify(new AssessmentCompleteNotification($application));
        }

        return redirect()->route('assessments.index')->with('success', 'Assessment finalized.');
    }

    public function print(Application $application)
    {
        $assessments = Assessment::with('assessmentItems')
            ->where('application_id', $application->id)
            ->get();

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
