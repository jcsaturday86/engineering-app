<?php

namespace App\Http\Controllers;

use App\Contracts\PermitApplicationContract;
use App\Models\Application;
use App\Models\Assessment;
use App\Models\AssessmentItem;
use App\Models\BuildingPart;
use App\Models\FeeCategory;
use App\Models\FeeSchedule;
use App\Models\FeeType;
use App\Models\OccupancyDivision;
use App\Models\OccupancyApplication;
use App\Models\Setting;
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

        $excludedTabs = ['ZONING_LC', 'ZONING_CERT', 'ANN_INSP', 'VIOLATION'];
        $tabCategories = $feeCategories->filter(fn ($c) => !in_array($c->code, $excludedTabs));

        $assessmentItems = $assessment->assessmentItems->where('is_active', true);
        $itemsByCategory = $assessmentItems->groupBy('fee_category_id');
        $totals = $this->calculateTotals($assessment);

        $activeTab = request('tab', $tabCategories->first()?->code ?? 'CONST');

        $buildingParts = BuildingPart::where('is_active', true)->get();

        $applicationGroupIds = [];
        if (method_exists($application, 'applicationOccupancyGroups')) {
            $application->load('applicationOccupancyGroups');
            $applicationGroupIds = $application->applicationOccupancyGroups
                ->pluck('occupancy_group_id')->unique()->values()->toArray();
        }

        $occupancyDivisions = OccupancyDivision::where('is_active', true)
            ->when(!empty($applicationGroupIds), fn ($q) => $q->whereIn('occupancy_group_id', $applicationGroupIds))
            ->orderBy('code')
            ->get();

        $isOp = $permitCode === 'OP';

        return view('assessments.assess', compact(
            'application', 'assessment', 'feeCategories', 'tabCategories',
            'totals', 'assessmentItems', 'itemsByCategory', 'activeTab', 'isOp',
            'buildingParts', 'occupancyDivisions'
        ));
    }

    // BP add item
    public function addConstructionItem(Request $request, Application $application)
    {
        $validated = $request->validate([
            'building_part_id' => 'required|exists:building_parts,id',
            'occupancy_division_id' => 'required|exists:occupancy_divisions,id',
            'area' => 'required|numeric|min:0.01',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'bp',
                'applicationable_id' => $application->id,
                'assessment_type' => 'building',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        $division = OccupancyDivision::findOrFail($validated['occupancy_division_id']);
        $buildingPart = BuildingPart::findOrFail($validated['building_part_id']);
        $area = (float) $validated['area'];

        $feeType = FeeType::where('code', 'CONST_' . $division->code)->first();
        if (!$feeType) {
            return back()->with('error', 'No construction fee type found for division ' . $division->code . '.');
        }

        $feeSchedule = FeeSchedule::where('fee_type_id', $feeType->id)
            ->where('range_from', '<=', $area)
            ->where('range_to', '>=', $area)
            ->where('is_active', true)
            ->first();

        if (!$feeSchedule) {
            return back()->with('error', 'No fee schedule found for ' . $division->name . ' at ' . number_format($area, 2) . ' sq.m.');
        }

        $unitFee = (float) $feeSchedule->fee_per_unit;
        $amount = round($area * $unitFee, 2);

        $constCategory = FeeCategory::where('code', 'CONST')->first();

        AssessmentItem::create([
            'assessment_id' => $assessment->id,
            'fee_category_id' => $constCategory->id,
            'fee_type_id' => $feeType->id,
            'fee_code' => $feeType->code,
            'description' => $buildingPart->name . ' - ' . $division->name,
            'quantity' => $area,
            'unit_fee' => $unitFee,
            'excess_fee' => 0,
            'inspection_fee' => 0,
            'amount' => $amount,
            'computation_details' => [
                'building_part_id' => $buildingPart->id,
                'building_part' => $buildingPart->name,
                'division_id' => $division->id,
                'division_code' => $division->code,
                'fee_schedule_id' => $feeSchedule->id,
            ],
            'is_active' => true,
        ]);

        return redirect()->route('assessments.assess', ['application' => $application->id, 'tab' => 'CONST'])
            ->with('success', 'Construction fee item added.');
    }

    public function addElectricalItem(Request $request, Application $application)
    {
        $validated = $request->validate([
            'electrical_fee_type' => 'required|string|in:ELEC_TCL,ELEC_TRANS,ELEC_UPS,ELEC_POLE,ELEC_MISC_METER,ELEC_MISC_WIRING',
            'kva' => 'nullable|numeric|min:0.01',
            'pole_type' => 'nullable|string',
            'occupancy_type' => 'nullable|string',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'bp',
                'applicationable_id' => $application->id,
                'assessment_type' => 'building',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        $feeTypeCode = $validated['electrical_fee_type'];
        $feeType = FeeType::where('code', $feeTypeCode)->first();
        if (!$feeType) {
            return back()->with('error', 'Electrical fee type not found: ' . $feeTypeCode);
        }

        $elecCategory = FeeCategory::where('code', 'ELEC')->first();
        $inspectionPct = (float) (Setting::where('key', 'assessment.electrical_inspection_percentage')->value('value') ?? 10);

        if (in_array($feeTypeCode, ['ELEC_TCL', 'ELEC_TRANS', 'ELEC_UPS'])) {
            $kva = (float) $validated['kva'];
            if (!$kva) {
                return back()->with('error', 'Capacity (kVA) is required for this fee type.');
            }

            $feeSchedule = FeeSchedule::where('fee_type_id', $feeType->id)
                ->where('range_from', '<=', $kva)
                ->where('range_to', '>=', $kva)
                ->where('is_active', true)
                ->first();

            if (!$feeSchedule) {
                return back()->with('error', 'No fee schedule found for ' . $feeType->name . ' at ' . number_format($kva, 2) . ' kVA.');
            }

            $fixedFee = (float) $feeSchedule->fixed_fee;
            $feePerUnit = (float) $feeSchedule->fee_per_unit;
            $baseFee = round($fixedFee + ($kva * $feePerUnit), 2);
            $inspectionFee = round($baseFee * $inspectionPct / 100, 2);
            $amount = $baseFee + $inspectionFee;
            $description = $feeType->name . ' - ' . number_format($kva, 2) . ' kVA';

            AssessmentItem::create([
                'assessment_id' => $assessment->id,
                'fee_category_id' => $elecCategory->id,
                'fee_type_id' => $feeType->id,
                'fee_code' => $feeType->code,
                'description' => $description,
                'quantity' => $kva,
                'unit_fee' => $feePerUnit,
                'excess_fee' => 0,
                'inspection_fee' => $inspectionFee,
                'amount' => $amount,
                'computation_details' => [
                    'fee_type_code' => $feeTypeCode,
                    'fee_schedule_id' => $feeSchedule->id,
                    'input_kva' => $kva,
                    'fixed_fee' => $fixedFee,
                    'fee_per_unit' => $feePerUnit,
                    'range' => $feeSchedule->range_from . ' - ' . $feeSchedule->range_to,
                ],
                'is_active' => true,
            ]);
        } elseif ($feeTypeCode === 'ELEC_POLE') {
            $poleType = $validated['pole_type'];
            if (!$poleType) {
                return back()->with('error', 'Pole type is required.');
            }

            $feeSchedule = FeeSchedule::where('fee_type_id', $feeType->id)
                ->where('formula', $poleType)
                ->where('is_active', true)
                ->first();

            if (!$feeSchedule) {
                return back()->with('error', 'No fee schedule found for ' . $poleType . '.');
            }

            $baseFee = (float) $feeSchedule->fixed_fee;
            $inspectionFee = round($baseFee * $inspectionPct / 100, 2);
            $amount = $baseFee + $inspectionFee;

            AssessmentItem::create([
                'assessment_id' => $assessment->id,
                'fee_category_id' => $elecCategory->id,
                'fee_type_id' => $feeType->id,
                'fee_code' => $feeType->code,
                'description' => $poleType,
                'quantity' => 1,
                'unit_fee' => 0,
                'excess_fee' => 0,
                'inspection_fee' => $inspectionFee,
                'amount' => $amount,
                'computation_details' => [
                    'fee_type_code' => $feeTypeCode,
                    'fee_schedule_id' => $feeSchedule->id,
                    'pole_type' => $poleType,
                    'fixed_fee' => $amount,
                ],
                'is_active' => true,
            ]);
        } else {
            $occupancyType = $validated['occupancy_type'];
            if (!$occupancyType) {
                return back()->with('error', 'Occupancy type is required.');
            }

            $feeSchedule = FeeSchedule::where('fee_type_id', $feeType->id)
                ->where('formula', $occupancyType)
                ->where('is_active', true)
                ->first();

            if (!$feeSchedule) {
                return back()->with('error', 'No fee schedule found for ' . $occupancyType . '.');
            }

            $baseFee = (float) $feeSchedule->fixed_fee;
            $inspectionFee = round($baseFee * $inspectionPct / 100, 2);
            $amount = $baseFee + $inspectionFee;
            $description = $feeType->name . ' - ' . $occupancyType;

            AssessmentItem::create([
                'assessment_id' => $assessment->id,
                'fee_category_id' => $elecCategory->id,
                'fee_type_id' => $feeType->id,
                'fee_code' => $feeType->code,
                'description' => $description,
                'quantity' => 1,
                'unit_fee' => 0,
                'excess_fee' => 0,
                'inspection_fee' => $inspectionFee,
                'amount' => $amount,
                'computation_details' => [
                    'fee_type_code' => $feeTypeCode,
                    'fee_schedule_id' => $feeSchedule->id,
                    'occupancy_type' => $occupancyType,
                    'fixed_fee' => $amount,
                ],
                'is_active' => true,
            ]);
        }

        return redirect()->route('assessments.assess', ['application' => $application->id, 'tab' => 'ELEC'])
            ->with('success', 'Electrical fee item added.');
    }

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

        $tabCode = $feeCategory->code;
        $route = $permitCode === 'OP'
            ? route('assessments.assess.op', ['occupancyApplication' => $application->id, 'tab' => $tabCode])
            : route('assessments.assess', ['application' => $application->id, 'tab' => $tabCode]);

        return redirect($route)->with('success', 'Fee item added.');
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
