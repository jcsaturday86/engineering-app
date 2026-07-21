<?php

namespace App\Http\Controllers;

use App\Contracts\PermitApplicationContract;
use App\Models\Application;
use App\Models\Assessment;
use App\Models\AssessmentItem;
use App\Models\BuildingPart;
use App\Models\DemolitionApplication;
use App\Models\FeeCategory;
use App\Models\FeeSchedule;
use App\Models\FeeType;
use App\Models\OccupancyDivision;
use App\Models\OccupancyApplication;
use App\Models\Setting;
use App\Models\Signatory;
use App\Models\FencingApplication;
use App\Models\AnnualInspectionApplication;
use App\Models\SignageApplication;
use App\Models\User;
use App\Notifications\AssessmentCompleteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AssessmentController extends Controller
{
    // Reads the NBC permit inspection fee from the MECH_INSP fee_schedules rows.
    // MECH_REFRIG → FeeType code INSP_REFRIG (range_based = flat), MECH_ELEV_PASS → INSP_ELEV_PASS (cumulative_range = tiered).
    private function resolveInspectionFee(string $code, float $unit): array
    {
        $default = ['fee' => 0, 'excess_threshold' => 0, 'excess_fee' => 0, 'every' => 1, 'method' => 'per_unit'];

        $feeType = FeeType::where('code', 'INSP_' . substr($code, 5))->first();
        if (!$feeType) {
            return $default;
        }

        $query    = FeeSchedule::where('fee_type_id', $feeType->id)->where('is_active', true);
        $schedule = ($feeType->computation_method === 'range_based')
            ? $query->where('range_from', '<=', $unit)->where('range_to', '>=', $unit)->first()
            : $query->orderBy('id')->first();

        if (!$schedule) {
            return $default;
        }

        $method = match ($feeType->computation_method) {
            'cumulative_range' => 'tiered',
            'per_unit'         => 'per_unit',
            default            => ($schedule->fee_per_unit > 0) ? 'per_unit' : 'flat',
        };

        return [
            'fee'              => (float) ($schedule->fee_per_unit > 0 ? $schedule->fee_per_unit : $schedule->fixed_fee),
            'excess_threshold' => (float) $schedule->excess_threshold,
            'excess_fee'       => (float) $schedule->excess_fee,
            'every'            => max(1, (float) $schedule->excess_every),
            'method'           => $method,
        ];
    }

    public function index()
    {
        $applications = Application::with('permitType')
            ->whereIn('status', ['submitted', 'zoning_assessed', 'engineering_assessed', 'billed'])
            ->latest()
            ->paginate(20);

        return view('assessments.index', compact('applications'));
    }

    public function occupancyIndex()
    {
        $applications = OccupancyApplication::with('applicationType')
            ->whereIn('status', ['submitted', 'zoning_assessed', 'engineering_assessed', 'billed'])
            ->latest()
            ->paginate(20);

        return view('assessments.occupancy-index', compact('applications'));
    }

    public function demolitionIndex()
    {
        $applications = DemolitionApplication::whereIn('status', ['submitted', 'engineering_assessed', 'billed'])
            ->latest()
            ->paginate(20);

        return view('assessments.demolition-index', compact('applications'));
    }

    public function signageIndex()
    {
        $applications = SignageApplication::whereIn('status', ['submitted', 'engineering_assessed', 'billed'])
            ->latest()
            ->paginate(20);

        return view('assessments.signage-index', compact('applications'));
    }

    public function fencingIndex()
    {
        $applications = FencingApplication::whereIn('status', ['submitted', 'engineering_assessed', 'billed'])
            ->latest()
            ->paginate(20);

        return view('assessments.fencing-index', compact('applications'));
    }

    public function annualInspectionIndex()
    {
        $applications = AnnualInspectionApplication::whereIn('status', ['submitted', 'engineering_assessed', 'billed'])
            ->latest()
            ->paginate(20);

        return view('assessments.annual-inspection-index', compact('applications'));
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

    // DP assessment
    public function assessDp(DemolitionApplication $demolitionApplication)
    {
        return $this->doAssess($demolitionApplication, 'demolition', 'DP');
    }

    // SGP assessment
    public function assessSgp(SignageApplication $signageApplication)
    {
        return $this->doAssess($signageApplication, 'signage', 'SGP');
    }

    // FP assessment
    public function assessFp(FencingApplication $fencingApplication)
    {
        return $this->doAssess($fencingApplication, 'fencing', 'FP');
    }

    // AI assessment
    public function assessAi(AnnualInspectionApplication $annualInspectionApplication)
    {
        return $this->doAssess($annualInspectionApplication, 'mechanical', 'AI');
    }

    private function morphTypeFor(string $permitCode): string
    {
        return match ($permitCode) {
            'OP' => 'op',
            'DP' => 'dp',
            'SGP' => 'sgp',
            'FP' => 'fp',
            'AI' => 'ai',
            default => 'bp',
        };
    }

    private function doAssess(PermitApplicationContract $application, string $assessmentType, string $permitCode)
    {
        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => $this->morphTypeFor($permitCode),
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

        $excludedTabs = ['ZONING_LC', 'ZONING_CERT', 'ANN_INSP', 'VIOLATION', 'MECH_INSP'];
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
        $isDp = $permitCode === 'DP';
        $isSgp = $permitCode === 'SGP';
        $isFp = $permitCode === 'FP';
        $isAi = $permitCode === 'AI';

        if ($isAi) {
            $application->load('equipmentItems');
        }

        return view('assessments.assess', compact(
            'application', 'assessment', 'feeCategories', 'tabCategories',
            'totals', 'assessmentItems', 'itemsByCategory', 'activeTab', 'isOp', 'isDp', 'isSgp', 'isFp', 'isAi',
            'buildingParts', 'occupancyDivisions'
        ));
    }

    private function redirectIfFinalized(Assessment $assessment, PermitApplicationContract $application): ?\Illuminate\Http\RedirectResponse
    {
        if ($assessment->status === 'finalized') {
            $route = match ($assessment->applicationable_type) {
                'op' => route('assessments.assess.op', $application) . '?tab=SUMMARY',
                'dp' => route('assessments.assess.dp', $application) . '?tab=SUMMARY',
                'sgp' => route('assessments.assess.sgp', $application) . '?tab=SUMMARY',
                'fp' => route('assessments.assess.fp', $application) . '?tab=SUMMARY',
                'ai' => route('assessments.assess.ai', $application) . '?tab=SUMMARY',
                default => route('assessments.assess', $application) . '?tab=SUMMARY',
            };
            return redirect()->to($route)->with('error', 'This assessment has been finalized and cannot be modified.');
        }
        return null;
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

        if ($r = $this->redirectIfFinalized($assessment, $application)) return $r;

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

        if ($r = $this->redirectIfFinalized($assessment, $application)) return $r;

        $feeTypeCode = $validated['electrical_fee_type'];
        $feeType = FeeType::where('code', $feeTypeCode)->first();
        if (!$feeType) {
            return back()->with('error', 'Electrical fee type not found: ' . $feeTypeCode);
        }

        $elecCategory = FeeCategory::where('code', 'ELEC')->first();

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
            $amount = $baseFee;
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
                'inspection_fee' => 0,
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
            $amount = $baseFee;

            AssessmentItem::create([
                'assessment_id' => $assessment->id,
                'fee_category_id' => $elecCategory->id,
                'fee_type_id' => $feeType->id,
                'fee_code' => $feeType->code,
                'description' => $poleType,
                'quantity' => 1,
                'unit_fee' => 0,
                'excess_fee' => 0,
                'inspection_fee' => 0,
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
            $amount = $baseFee;
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
                'inspection_fee' => 0,
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

    public function addMechanicalItem(Request $request, Application $application)
    {
        $allCodes = implode(',', [
            'MECH_REFRIG','MECH_ICE','MECH_CENTRAL_AC','MECH_WINDOW_AC','MECH_VENT',
            'MECH_ESC_KW','MECH_ESC_RANGE','MECH_FUNIC_KW','MECH_FUNIC_LM','MECH_CABLE_KW','MECH_CABLE_LM',
            'MECH_ELEV_DUMB','MECH_ELEV_CONST','MECH_ELEV_PASS','MECH_ELEV_FRT','MECH_ELEV_CAR',
            'MECH_BOILER','MECH_DIESEL','MECH_INT_COMB',
            'MECH_WATER_HEATER','MECH_WATER_PUMP','MECH_SPRINKLER','MECH_COMPRESSED','MECH_GAS_METER',
            'MECH_POWER_PIPE','MECH_PRESSURE_V','MECH_OTHER_EQUIP','MECH_PNEUMATIC','MECH_WEIGH_SCALE',
        ]);

        $validated = $request->validate([
            'mechanical_fee_type' => 'required|string|in:' . $allCodes,
            'unit'                => 'required|numeric|min:0.01',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'bp',
                'applicationable_id'   => $application->id,
                'assessment_type'      => 'building',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        if ($r = $this->redirectIfFinalized($assessment, $application)) return $r;

        $feeTypeCode = $validated['mechanical_fee_type'];
        $unit        = (float) $validated['unit'];

        $feeType = FeeType::where('code', $feeTypeCode)->first();
        if (!$feeType) {
            return back()->with('error', 'Mechanical fee type not found: ' . $feeTypeCode);
        }

        $mechCategory = FeeCategory::where('code', 'MECH')->first();
        $isRangeBased = $feeType->computation_method === 'range_based';

        $scheduleQuery = FeeSchedule::where('fee_type_id', $feeType->id)->where('is_active', true);
        if ($isRangeBased) {
            $scheduleQuery->where('range_from', '<=', $unit)->where('range_to', '>=', $unit);
        }
        $schedule = $scheduleQuery->first();

        if (!$schedule) {
            return back()->with('error', 'No fee schedule found for ' . $feeType->name . ' at unit ' . number_format($unit, 2) . '.');
        }

        $excessFee = 0;
        $unitFee   = 0;

        switch ($feeType->computation_method) {
            case 'per_unit':
                $unitFee = (float) $schedule->fee_per_unit;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'fixed':
                $unitFee = (float) $schedule->fixed_fee;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'range_based':
                $threshold = (float) $schedule->excess_threshold;
                if ($threshold > 0) {
                    // Flat base + excess per unit over threshold (Boiler 75+, Escalator 21+)
                    $excess    = max(0, $unit - $threshold);
                    $excessFee = round($excess * (float) $schedule->excess_fee, 2);
                    $baseFee   = round((float) $schedule->fixed_fee + $excessFee, 2);
                    $unitFee   = 0;
                } elseif ((float) $schedule->fee_per_unit > 0) {
                    // Range per-unit (Diesel, ICE, Central AC)
                    $unitFee = (float) $schedule->fee_per_unit;
                    $baseFee = round($unit * $unitFee, 2);
                } else {
                    // Flat fixed_fee regardless of unit count (Boiler ranges 0–74.9)
                    $baseFee = round((float) $schedule->fixed_fee, 2);
                    $unitFee = 0;
                }
                break;

            default:
                $baseFee = 0;
        }

        // amount = base fee only. Inspection fees are not charged on Building Permit
        // Mechanical items (see resolveInspectionFee(), still used by the separate
        // standalone Annual Inspection feature's addAnnualInspectionUnitItem()).
        $amount = $baseFee;

        AssessmentItem::create([
            'assessment_id'       => $assessment->id,
            'fee_category_id'     => $mechCategory->id,
            'fee_type_id'         => $feeType->id,
            'fee_code'            => $feeType->code,
            'description'         => $feeType->name,
            'quantity'            => $unit,
            'unit_fee'            => $unitFee,
            'excess_fee'          => $excessFee,
            'inspection_fee'      => 0,
            'amount'              => $amount,
            'computation_details' => [
                'fee_type_code'        => $feeTypeCode,
                'fee_schedule_id'      => $schedule->id,
                'input_unit'           => $unit,
                'computation_method'   => $feeType->computation_method,
                'fixed_fee'            => (float) $schedule->fixed_fee,
                'fee_per_unit'         => (float) $schedule->fee_per_unit,
                'excess_threshold'     => (float) $schedule->excess_threshold,
                'excess_fee_rate'      => (float) $schedule->excess_fee,
                'range'                => $isRangeBased ? ($schedule->range_from . ' - ' . $schedule->range_to) : null,
            ],
            'is_active'           => true,
        ]);

        return redirect()->route('assessments.assess', ['application' => $application->id, 'tab' => 'MECH'])
            ->with('success', 'Mechanical fee item added.');
    }

    public function addPlumbingItem(Request $request, Application $application)
    {
        $allCodes = implode(',', [
            'PLUMB_INSTALL',
            'PLUMB_FIX_WC','PLUMB_FIX_FD','PLUMB_FIX_SINK','PLUMB_FIX_LAV','PLUMB_FIX_FAUCET','PLUMB_FIX_SHOWER',
            'PLUMB_SP_SLOP','PLUMB_SP_URINAL','PLUMB_SP_BATH','PLUMB_SP_GREASE','PLUMB_SP_GARAGE',
            'PLUMB_SP_BIDET','PLUMB_SP_DENTAL','PLUMB_SP_GWH','PLUMB_SP_DRINK','PLUMB_SP_BAR',
            'PLUMB_SP_LAUNDRY','PLUMB_SP_LAB','PLUMB_SP_STERIL',
            'PLUMB_WATER_METER','PLUMB_SEPTIC',
        ]);

        $validated = $request->validate([
            'plumbing_fee_type' => 'required|string|in:' . $allCodes,
            'unit'              => 'required|numeric|min:0.01',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'bp',
                'applicationable_id'   => $application->id,
                'assessment_type'      => 'building',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        if ($r = $this->redirectIfFinalized($assessment, $application)) return $r;

        $feeTypeCode  = $validated['plumbing_fee_type'];
        $unit         = (float) $validated['unit'];

        $feeType = FeeType::where('code', $feeTypeCode)->first();
        if (!$feeType) {
            return back()->with('error', 'Plumbing fee type not found: ' . $feeTypeCode);
        }

        $plumbCategory = FeeCategory::where('code', 'PLUMB')->first();
        $isRangeBased  = $feeType->computation_method === 'range_based';

        $scheduleQuery = FeeSchedule::where('fee_type_id', $feeType->id)->where('is_active', true);
        if ($isRangeBased) {
            $scheduleQuery->where('range_from', '<=', $unit)->where('range_to', '>=', $unit);
        }
        $schedule = $scheduleQuery->orderBy('id')->first();

        if (!$schedule) {
            return back()->with('error', 'No fee schedule found for ' . $feeType->name . ' at unit ' . number_format($unit, 2) . '.');
        }

        $excessFee = 0;
        $unitFee   = 0;

        switch ($feeType->computation_method) {
            case 'per_unit':
                $unitFee = (float) $schedule->fee_per_unit;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'range_based':
                $threshold = (float) $schedule->excess_threshold;
                if ($threshold > 0 && $unit > $threshold) {
                    $excess    = $unit - $threshold;
                    $excessFee = round($excess * (float) $schedule->excess_fee, 2);
                    $baseFee   = round((float) $schedule->fixed_fee + $excessFee, 2);
                } elseif ((float) $schedule->fee_per_unit > 0) {
                    $unitFee = (float) $schedule->fee_per_unit;
                    $baseFee = round($unit * $unitFee, 2);
                } else {
                    $baseFee = round((float) $schedule->fixed_fee, 2);
                    $unitFee = 0;
                }
                break;

            default:
                $baseFee = 0;
        }

        AssessmentItem::create([
            'assessment_id'       => $assessment->id,
            'fee_category_id'     => $plumbCategory->id,
            'fee_type_id'         => $feeType->id,
            'fee_code'            => $feeType->code,
            'description'         => $feeType->name,
            'quantity'            => $unit,
            'unit_fee'            => $unitFee,
            'excess_fee'          => $excessFee,
            'inspection_fee'      => 0,
            'amount'              => $baseFee,
            'computation_details' => [
                'fee_type_code'      => $feeTypeCode,
                'fee_schedule_id'    => $schedule->id,
                'input_unit'         => $unit,
                'computation_method' => $feeType->computation_method,
                'fixed_fee'          => (float) $schedule->fixed_fee,
                'fee_per_unit'       => (float) $schedule->fee_per_unit,
                'excess_threshold'   => (float) $schedule->excess_threshold,
                'excess_fee_rate'    => (float) $schedule->excess_fee,
                'range'              => $isRangeBased ? ($schedule->range_from . ' - ' . $schedule->range_to) : null,
            ],
            'is_active'           => true,
        ]);

        return redirect()->route('assessments.assess', ['application' => $application->id, 'tab' => 'PLUMB'])
            ->with('success', 'Plumbing fee item added.');
    }

    public function addElectronicsItem(Request $request, Application $application)
    {
        $allCodes = implode(',', [
            'ELECT_SWITCH','ELECT_BROADCAST','ELECT_ATM','ELECT_OUTLET','ELECT_SECURITY',
            'ELECT_STUDIO','ELECT_TOWER','ELECT_SIGNAGE','ELECT_POLE','ELECT_ATTACH','ELECT_OTHER',
        ]);

        $validated = $request->validate([
            'electronics_fee_type' => 'required|string|in:' . $allCodes,
            'unit'                 => 'required|numeric|min:0.01',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'bp',
                'applicationable_id'   => $application->id,
                'assessment_type'      => 'building',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        if ($r = $this->redirectIfFinalized($assessment, $application)) return $r;

        $feeTypeCode = $validated['electronics_fee_type'];
        $unit        = (float) $validated['unit'];

        $feeType = FeeType::where('code', $feeTypeCode)->first();
        if (!$feeType) {
            return back()->with('error', 'Electronics fee type not found: ' . $feeTypeCode);
        }

        $electCategory = FeeCategory::where('code', 'ELECT')->first();

        $schedule = FeeSchedule::where('fee_type_id', $feeType->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (!$schedule) {
            return back()->with('error', 'No fee schedule found for ' . $feeType->name . '.');
        }

        $unitFee = $feeType->computation_method === 'fixed'
            ? (float) $schedule->fixed_fee
            : (float) $schedule->fee_per_unit;

        $baseFee = round($unit * $unitFee, 2);

        AssessmentItem::create([
            'assessment_id'       => $assessment->id,
            'fee_category_id'     => $electCategory->id,
            'fee_type_id'         => $feeType->id,
            'fee_code'            => $feeType->code,
            'description'         => $feeType->name,
            'quantity'            => $unit,
            'unit_fee'            => $unitFee,
            'excess_fee'          => 0,
            'inspection_fee'      => 0,
            'amount'              => $baseFee,
            'computation_details' => [
                'fee_type_code'      => $feeTypeCode,
                'fee_schedule_id'    => $schedule->id,
                'input_unit'         => $unit,
                'computation_method' => $feeType->computation_method,
                'fixed_fee'          => (float) $schedule->fixed_fee,
                'fee_per_unit'       => (float) $schedule->fee_per_unit,
            ],
            'is_active'           => true,
        ]);

        return redirect()->route('assessments.assess', ['application' => $application->id, 'tab' => 'ELECT'])
            ->with('success', 'Electronics fee item added.');
    }

    public function addOccupancyFeeItem(Request $request, OccupancyApplication $occupancyApplication)
    {
        $allCodes = implode(',', [
            'OCC_DIV_A','OCC_DIV_B','OCC_DIV_CD','OCC_DIV_J1',
            'OCC_DIV_J2_RATE','OCC_DIV_J2_E2','OCC_DIV_J2_E3','OCC_CHANGE_USE',
        ]);

        $validated = $request->validate([
            'occupancy_fee_type' => 'required|string|in:' . $allCodes,
            'unit'               => 'required|numeric|min:0.01',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'op',
                'applicationable_id'   => $occupancyApplication->id,
                'assessment_type'      => 'occupancy',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        if ($r = $this->redirectIfFinalized($assessment, $occupancyApplication)) return $r;

        $feeTypeCode  = $validated['occupancy_fee_type'];
        $unit         = (float) $validated['unit'];

        $feeType = FeeType::where('code', $feeTypeCode)->first();
        if (!$feeType) {
            return back()->with('error', 'Occupancy fee type not found: ' . $feeTypeCode);
        }

        $occCategory  = FeeCategory::where('code', 'OCC')->first();
        $isRangeBased = $feeType->computation_method === 'range_based';

        $scheduleQuery = FeeSchedule::where('fee_type_id', $feeType->id)->where('is_active', true);
        if ($isRangeBased) {
            $scheduleQuery->where('range_from', '<=', $unit)->where('range_to', '>=', $unit);
        }
        $schedule = $scheduleQuery->orderBy('id')->first();

        if (!$schedule) {
            return back()->with('error', 'No fee schedule found for ' . $feeType->name . ' at unit ' . number_format($unit, 2) . '.');
        }

        $excessFee  = 0;
        $unitFee    = 0;
        $pctRate    = 0;
        $excessUnits = 0;

        switch ($feeType->computation_method) {
            case 'per_unit':
                $unitFee = (float) $schedule->fee_per_unit;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'percentage':
                $pctRate = (float) $schedule->percentage;
                $baseFee = round($unit * $pctRate, 2);
                $unitFee = $pctRate;
                break;

            case 'range_based':
                $threshold   = (float) $schedule->excess_threshold;
                $excessEvery = max(1, (float) $schedule->excess_every);
                if ($threshold > 0 && $unit > $threshold) {
                    $excess      = $unit - $threshold;
                    $excessUnits = (int) ceil($excess / $excessEvery);
                    $excessFee   = round($excessUnits * (float) $schedule->excess_fee, 2);
                    $baseFee     = round((float) $schedule->fixed_fee + $excessFee, 2);
                } elseif ((float) $schedule->fee_per_unit > 0) {
                    $unitFee = (float) $schedule->fee_per_unit;
                    $baseFee = round($unit * $unitFee, 2);
                } else {
                    $baseFee = round((float) $schedule->fixed_fee, 2);
                }
                break;

            default:
                $baseFee = 0;
        }

        AssessmentItem::create([
            'assessment_id'       => $assessment->id,
            'fee_category_id'     => $occCategory->id,
            'fee_type_id'         => $feeType->id,
            'fee_code'            => $feeType->code,
            'description'         => $feeType->name,
            'quantity'            => $unit,
            'unit_fee'            => $unitFee,
            'excess_fee'          => $excessFee,
            'inspection_fee'      => 0,
            'amount'              => $baseFee,
            'computation_details' => [
                'fee_type_code'      => $feeTypeCode,
                'fee_schedule_id'    => $schedule->id,
                'input_unit'         => $unit,
                'computation_method' => $feeType->computation_method,
                'fixed_fee'          => (float) $schedule->fixed_fee,
                'fee_per_unit'       => (float) $schedule->fee_per_unit,
                'percentage'         => $pctRate,
                'excess_threshold'   => (float) $schedule->excess_threshold,
                'excess_every'       => (float) $schedule->excess_every,
                'excess_units'       => $excessUnits,
                'excess_fee_rate'    => (float) $schedule->excess_fee,
                'range'              => $isRangeBased ? ($schedule->range_from . ' - ' . $schedule->range_to) : null,
            ],
            'is_active' => true,
        ]);

        return redirect()->route('assessments.assess.op', ['occupancyApplication' => $occupancyApplication->id, 'tab' => 'OCC'])
            ->with('success', 'Occupancy fee item added.');
    }

    public function addAccessoryItem(Request $request, Application $application)
    {
        $allCodes = implode(',', [
            'ACC_OPEN_PARTS', 'ACC_HEIGHT', 'ACC_VAULT', 'ACC_FIREWALL',
            'ACC_POOL_RES', 'ACC_POOL_COM', 'ACC_POOL_SOC', 'ACC_POOL_INDIG',
            'ACC_POOL_SHR_RES', 'ACC_POOL_SHR_COM', 'ACC_POOL_SHR_SOC',
            'ACC_TOWER_RES', 'ACC_TOWER_COM_SS', 'ACC_TOWER_COM_TG',
            'ACC_TOWER_EDU_SS', 'ACC_TOWER_EDU_TG',
            'ACC_SILO', 'ACC_SMOKESTACK', 'ACC_CHIMNEY', 'ACC_OVEN', 'ACC_KILN',
            'ACC_RC_TANK_AG', 'ACC_RC_TANK_UG', 'ACC_WATER_TREAT',
            'ACC_TANK_AG_SM', 'ACC_TANK_AG_LG',
            'ACC_PULL_UG', 'ACC_PULL_SADDLE', 'ACC_REINST_SM', 'ACC_REINST_LG',
            'ACC_BOOTH_PERM', 'ACC_BOOTH_TEMP', 'ACC_BOOTH_KNOCK',
            'ACC_CEM_TOMB', 'ACC_CEM_SEMI', 'ACC_CEM_ENCLOSED', 'ACC_CEM_MULTI', 'ACC_CEM_COLUMB',
        ]);

        $validated = $request->validate([
            'accessory_fee_type' => 'required|string|in:' . $allCodes,
            'unit'               => 'required|numeric|min:0.01',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'bp',
                'applicationable_id'   => $application->id,
                'assessment_type'      => 'building',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        if ($r = $this->redirectIfFinalized($assessment, $application)) return $r;

        $feeTypeCode  = $validated['accessory_fee_type'];
        $unit         = (float) $validated['unit'];

        $feeType = FeeType::where('code', $feeTypeCode)->first();
        if (!$feeType) {
            return back()->with('error', 'Accessory fee type not found: ' . $feeTypeCode);
        }

        $accCategory  = FeeCategory::where('code', 'ACC_BLDG')->first();
        $isRangeBased = $feeType->computation_method === 'range_based';

        $scheduleQuery = FeeSchedule::where('fee_type_id', $feeType->id)->where('is_active', true);
        if ($isRangeBased) {
            $scheduleQuery->where('range_from', '<=', $unit)->where('range_to', '>=', $unit);
        }
        $schedule = $scheduleQuery->orderBy('id')->first();

        if (!$schedule) {
            return back()->with('error', 'No fee schedule found for ' . $feeType->name . ' at unit ' . number_format($unit, 2) . '.');
        }

        $excessFee = 0;
        $unitFee   = 0;

        switch ($feeType->computation_method) {
            case 'per_unit':
                $unitFee = (float) $schedule->fee_per_unit;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'fixed':
                $unitFee = (float) $schedule->fixed_fee;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'percentage':
                $unitFee = (float) $schedule->percentage;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'range_based':
                $threshold = (float) $schedule->excess_threshold;
                if ($threshold > 0 && $unit > $threshold) {
                    $excess    = $unit - $threshold;
                    $excessFee = round($excess * (float) $schedule->excess_fee, 2);
                    $baseFee   = round((float) $schedule->fixed_fee + $excessFee, 2);
                } elseif ((float) $schedule->fee_per_unit > 0) {
                    $unitFee = (float) $schedule->fee_per_unit;
                    $baseFee = round($unit * $unitFee, 2);
                } else {
                    $baseFee = round((float) $schedule->fixed_fee, 2);
                    $unitFee = 0;
                }
                break;

            default:
                $baseFee = 0;
        }

        AssessmentItem::create([
            'assessment_id'       => $assessment->id,
            'fee_category_id'     => $accCategory->id,
            'fee_type_id'         => $feeType->id,
            'fee_code'            => $feeType->code,
            'description'         => $feeType->name,
            'quantity'            => $unit,
            'unit_fee'            => $unitFee,
            'excess_fee'          => $excessFee,
            'inspection_fee'      => 0,
            'amount'              => $baseFee,
            'computation_details' => [
                'fee_type_code'      => $feeTypeCode,
                'fee_schedule_id'    => $schedule->id,
                'input_unit'         => $unit,
                'computation_method' => $feeType->computation_method,
                'fixed_fee'          => (float) $schedule->fixed_fee,
                'fee_per_unit'       => (float) $schedule->fee_per_unit,
                'percentage'         => (float) $schedule->percentage,
                'excess_threshold'   => (float) $schedule->excess_threshold,
                'excess_fee_rate'    => (float) $schedule->excess_fee,
                'range'              => $isRangeBased ? ($schedule->range_from . ' - ' . $schedule->range_to) : null,
            ],
            'is_active'           => true,
        ]);

        return redirect()->route('assessments.assess', ['application' => $application->id, 'tab' => 'ACC_BLDG'])
            ->with('success', 'Accessory fee item added.');
    }

    public function addAccFeeItem(Request $request, Application $application)
    {
        $allCodes = [
            'ASS_LINE_GRADE',
            'ASS_GP_INSPECT', 'ASS_GP_EXCAV', 'ASS_GP_ISSUANCE',
            'ASS_GP_FOUND', 'ASS_GP_OTHER', 'ASS_GP_ENCROACH',
            'ASS_FENCE_MASONRY', 'ASS_FENCE_INDIG',
            'ASS_PAVEMENT', 'ASS_SIDEWALK', 'ASS_SCAFFOLD',
            'ASS_SIGN_ERECT',
            'ASS_SIGN_INSTALL|Business|Neon',       'ASS_SIGN_INSTALL|Advertising|Neon',
            'ASS_SIGN_INSTALL|Business|Illuminated', 'ASS_SIGN_INSTALL|Advertising|Illuminated',
            'ASS_SIGN_INSTALL|Business|Painted-on',  'ASS_SIGN_INSTALL|Advertising|Painted-on',
            'ASS_SIGN_INSTALL|Business|Others',      'ASS_SIGN_INSTALL|Advertising|Others',
            'ASS_SIGN_RENEW|Business|Neon',          'ASS_SIGN_RENEW|Advertising|Neon',
            'ASS_SIGN_RENEW|Business|Illuminated',   'ASS_SIGN_RENEW|Advertising|Illuminated',
            'ASS_SIGN_RENEW|Business|Painted-on',    'ASS_SIGN_RENEW|Advertising|Painted-on',
            'ASS_SIGN_RENEW|Business|Others',        'ASS_SIGN_RENEW|Advertising|Others',
            'ASS_REPAIR_VERT', 'ASS_REPAIR_HORIZ', 'ASS_REPAIR_COST',
            'ASS_DEMO_BLDG', 'ASS_DEMO_FRAME', 'ASS_DEMO_MOVE', 'ASS_DEMO_STRUCT', 'ASS_DEMO_APPEND',
        ];

        $validated = $request->validate([
            'acc_fee_type' => ['required', 'string', Rule::in($allCodes)],
            'unit'         => 'required|numeric|min:0.01',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'bp',
                'applicationable_id'   => $application->id,
                'assessment_type'      => 'building',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        if ($r = $this->redirectIfFinalized($assessment, $application)) return $r;

        $rawCode     = $validated['acc_fee_type'];
        $unit        = (float) $validated['unit'];
        $signFormula = null;

        if (str_contains($rawCode, '|')) {
            $parts       = explode('|', $rawCode, 3);
            $feeTypeCode = $parts[0];
            $signFormula = $parts[1] . '|' . $parts[2];
        } else {
            $feeTypeCode = $rawCode;
        }

        $feeType = FeeType::where('code', $feeTypeCode)->first();
        if (!$feeType) {
            return back()->with('error', 'Acc fee type not found: ' . $feeTypeCode);
        }

        $accFeeCategory = FeeCategory::where('code', 'ACC_FEE')->first();
        $isRangeBased   = $feeType->computation_method === 'range_based';

        $scheduleQuery = FeeSchedule::where('fee_type_id', $feeType->id)->where('is_active', true);
        if ($isRangeBased) {
            $scheduleQuery->where('range_from', '<=', $unit)->where('range_to', '>=', $unit);
        } elseif ($signFormula) {
            $scheduleQuery->where('formula', $signFormula);
        }
        $schedule = $scheduleQuery->orderBy('id')->first();

        // Fallback for range_based when unit exceeds highest range_to (picks last applicable row)
        if (!$schedule && $isRangeBased) {
            $schedule = FeeSchedule::where('fee_type_id', $feeType->id)
                ->where('is_active', true)
                ->where('range_from', '<=', $unit)
                ->orderBy('range_from', 'desc')
                ->first();
        }

        if (!$schedule) {
            return back()->with('error', 'No fee schedule found for ' . $feeType->name . '.');
        }

        $excessFee = 0;
        $unitFee   = 0;

        switch ($feeType->computation_method) {
            case 'per_unit':
                $unitFee = (float) $schedule->fee_per_unit;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'fixed':
                $unitFee = (float) $schedule->fixed_fee;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'percentage':
                $unitFee = (float) $schedule->percentage;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'range_based':
                $threshold = (float) $schedule->excess_threshold;
                if ($threshold > 0 && $unit > $threshold) {
                    $excess    = $unit - $threshold;
                    $excessFee = round($excess * (float) $schedule->excess_fee, 2);
                    $baseFee   = round((float) $schedule->fixed_fee + $excessFee, 2);
                } elseif ((float) $schedule->fee_per_unit > 0) {
                    $unitFee = (float) $schedule->fee_per_unit;
                    $baseFee = round($unit * $unitFee, 2);
                } else {
                    $baseFee = round((float) $schedule->fixed_fee, 2);
                    $unitFee = 0;
                }
                break;

            default:
                $baseFee = 0;
        }

        $description = $feeType->name
            . ($signFormula ? ' (' . str_replace('|', ' - ', $signFormula) . ')' : '');

        AssessmentItem::create([
            'assessment_id'       => $assessment->id,
            'fee_category_id'     => $accFeeCategory->id,
            'fee_type_id'         => $feeType->id,
            'fee_code'            => $feeType->code,
            'description'         => $description,
            'quantity'            => $unit,
            'unit_fee'            => $unitFee,
            'excess_fee'          => $excessFee,
            'inspection_fee'      => 0,
            'amount'              => $baseFee,
            'computation_details' => [
                'fee_type_code'      => $feeTypeCode,
                'sign_formula'       => $signFormula,
                'fee_schedule_id'    => $schedule->id,
                'input_unit'         => $unit,
                'computation_method' => $feeType->computation_method,
                'fixed_fee'          => (float) $schedule->fixed_fee,
                'fee_per_unit'       => (float) $schedule->fee_per_unit,
                'percentage'         => (float) $schedule->percentage,
                'excess_threshold'   => (float) $schedule->excess_threshold,
                'excess_fee_rate'    => (float) $schedule->excess_fee,
                'range'              => $isRangeBased ? ($schedule->range_from . '–' . $schedule->range_to) : null,
            ],
            'is_active'           => true,
        ]);

        return redirect()->route('assessments.assess', ['application' => $application->id, 'tab' => 'ACC_FEE'])
            ->with('success', 'Accessory (misc.) fee item added.');
    }

    public function addSurchargeItem(Request $request, Application $application)
    {
        $validated = $request->validate([
            'surcharge_type' => 'required|string|in:SURCHARGE_LIGHT,SURCHARGE_LESS,SURCHARGE_GRAVE,SURCHARGE_EXCAV,SURCHARGE_FOUND,SURCHARGE_SUPER2,SURCHARGE_SUPER',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'bp',
                'applicationable_id'   => $application->id,
                'assessment_type'      => 'building',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        if ($r = $this->redirectIfFinalized($assessment, $application)) return $r;

        $feeTypeCode      = $validated['surcharge_type'];
        $feeType          = FeeType::where('code', $feeTypeCode)->first();
        $surchargeCategory = FeeCategory::where('code', 'SURCHARGE')->first();

        if (!$feeType) {
            return back()->with('error', 'Surcharge type not found: ' . $feeTypeCode);
        }

        $schedule = FeeSchedule::where('fee_type_id', $feeType->id)->where('is_active', true)->first();
        if (!$schedule) {
            return back()->with('error', 'No fee schedule found for ' . $feeType->name . '.');
        }

        $totalBPFeeBase  = null;
        $bpCategoryCodes = ['CONST', 'ELEC', 'MECH', 'PLUMB', 'ELECT', 'ACC_BLDG', 'ACC_FEE'];

        if ($feeType->computation_method === 'fixed') {
            $amount  = round((float) $schedule->fixed_fee, 2);
            $unitFee = $amount;
        } else {
            // Percentage: base = sum of amount across the 7 core BP assessment categories
            $bpCategoryIds = FeeCategory::whereIn('code', $bpCategoryCodes)->pluck('id');

            $bpItems = $assessment->assessmentItems()
                ->where('is_active', true)
                ->whereIn('fee_category_id', $bpCategoryIds)
                ->get();

            $totalBPFeeBase = $bpItems->sum('amount');

            $amount  = round($totalBPFeeBase * (float) $schedule->percentage, 2);
            $unitFee = 0;
        }

        AssessmentItem::create([
            'assessment_id'       => $assessment->id,
            'fee_category_id'     => $surchargeCategory->id,
            'fee_type_id'         => $feeType->id,
            'fee_code'            => $feeType->code,
            'description'         => $feeType->name,
            'quantity'            => 1,
            'unit_fee'            => $unitFee,
            'excess_fee'          => 0,
            'inspection_fee'      => 0,
            'amount'              => $amount,
            'computation_details' => [
                'fee_type_code'      => $feeTypeCode,
                'computation_method' => $feeType->computation_method,
                'fixed_fee'          => (float) $schedule->fixed_fee,
                'percentage'         => (float) $schedule->percentage,
                'total_bp_fee_base'  => $totalBPFeeBase,
                'base_categories'    => $bpCategoryCodes,
            ],
            'is_active'           => true,
        ]);

        return redirect()->route('assessments.assess', ['application' => $application->id, 'tab' => 'SURCHARGE'])
            ->with('success', 'Surcharge added.');
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

    // DP add item
    public function addItemDp(Request $request, DemolitionApplication $demolitionApplication)
    {
        return $this->doAddItem($request, $demolitionApplication, 'demolition', 'DP');
    }

    // SGP add item
    public function addItemSgp(Request $request, SignageApplication $signageApplication)
    {
        return $this->doAddItem($request, $signageApplication, 'signage', 'SGP');
    }

    // FP add item (generic fallback, unused now that addFenceItem() covers the only FP_FEE fee types)
    public function addItemFp(Request $request, FencingApplication $fencingApplication)
    {
        return $this->doAddItem($request, $fencingApplication, 'fencing', 'FP');
    }

    // FP fencing fee item — reuses the same ASS_FENCE_MASONRY/ASS_FENCE_INDIG FeeType/FeeSchedule
    // rows and computation logic as addAccFeeItem() (Settings → Fee Schedules → Accessory), but
    // scoped to Fencing Permit applications and stored under the FP_FEE category, not ACC_FEE.
    public function addFenceItem(Request $request, FencingApplication $fencingApplication)
    {
        $validated = $request->validate([
            'fence_fee_type' => ['required', 'string', Rule::in([
                'ASS_FENCE_MASONRY', 'ASS_FENCE_INDIG',
                'ASS_LINE_GRADE',
                'ASS_GP_INSPECT', 'ASS_GP_EXCAV', 'ASS_GP_ISSUANCE',
                'ASS_GP_FOUND', 'ASS_GP_OTHER', 'ASS_GP_ENCROACH',
            ])],
            'unit' => 'required|numeric|min:0.01',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'fp',
                'applicationable_id' => $fencingApplication->id,
                'assessment_type' => 'fencing',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        if ($r = $this->redirectIfFinalized($assessment, $fencingApplication)) return $r;

        $feeTypeCode = $validated['fence_fee_type'];
        $unit = (float) $validated['unit'];

        $feeType = FeeType::where('code', $feeTypeCode)->first();
        if (! $feeType) {
            return back()->with('error', 'Fee type not found: ' . $feeTypeCode);
        }

        $fpFeeCategory = FeeCategory::where('code', 'FP_FEE')->first();
        $isRangeBased = $feeType->computation_method === 'range_based';

        $scheduleQuery = FeeSchedule::where('fee_type_id', $feeType->id)->where('is_active', true);
        if ($isRangeBased) {
            $scheduleQuery->where('range_from', '<=', $unit)->where('range_to', '>=', $unit);
        }
        $schedule = $scheduleQuery->orderBy('id')->first();

        if (! $schedule && $isRangeBased) {
            $schedule = FeeSchedule::where('fee_type_id', $feeType->id)
                ->where('is_active', true)
                ->where('range_from', '<=', $unit)
                ->orderBy('range_from', 'desc')
                ->first();
        }

        if (! $schedule) {
            return back()->with('error', 'No fee schedule found for ' . $feeType->name . '.');
        }

        $excessFee = 0;
        $unitFee = 0;

        switch ($feeType->computation_method) {
            case 'per_unit':
                $unitFee = (float) $schedule->fee_per_unit;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'range_based':
                $threshold = (float) $schedule->excess_threshold;
                if ($threshold > 0 && $unit > $threshold) {
                    $excess = $unit - $threshold;
                    $excessFee = round($excess * (float) $schedule->excess_fee, 2);
                    $baseFee = round((float) $schedule->fixed_fee + $excessFee, 2);
                } elseif ((float) $schedule->fee_per_unit > 0) {
                    $unitFee = (float) $schedule->fee_per_unit;
                    $baseFee = round($unit * $unitFee, 2);
                } else {
                    $baseFee = round((float) $schedule->fixed_fee, 2);
                    $unitFee = 0;
                }
                break;

            case 'fixed':
                $unitFee = (float) $schedule->fixed_fee;
                $baseFee = round($unit * $unitFee, 2);
                break;

            default:
                $baseFee = 0;
        }

        AssessmentItem::create([
            'assessment_id' => $assessment->id,
            'fee_category_id' => $fpFeeCategory->id,
            'fee_type_id' => $feeType->id,
            'fee_code' => $feeType->code,
            'description' => $feeType->name,
            'quantity' => $unit,
            'unit_fee' => $unitFee,
            'excess_fee' => $excessFee,
            'inspection_fee' => 0,
            'amount' => $baseFee,
            'computation_details' => [
                'fee_type_code' => $feeTypeCode,
                'fee_schedule_id' => $schedule->id,
                'input_unit' => $unit,
                'computation_method' => $feeType->computation_method,
                'fixed_fee' => (float) $schedule->fixed_fee,
                'fee_per_unit' => (float) $schedule->fee_per_unit,
                'excess_threshold' => (float) $schedule->excess_threshold,
                'excess_fee_rate' => (float) $schedule->excess_fee,
                'range' => $isRangeBased ? ($schedule->range_from . '–' . $schedule->range_to) : null,
            ],
            'is_active' => true,
        ]);

        return redirect()->route('assessments.assess.fp', ['fencingApplication' => $fencingApplication->id, 'tab' => 'FP_FEE'])
            ->with('success', 'Fencing fee item added.');
    }

    // AI Annual Inspection Fees (NBC schedule) — one method handling all 3 tabs (General/Occupancy/
    // Electrical, Electronics, Mechanical). Reuses the existing AINSP_* FeeType/FeeSchedule rows
    // (Settings > Fee Schedules > Annual Inspection Fees, BP-scoped) by code, tagging the resulting
    // AssessmentItem under the AI-scoped AINSP_GEN/AINSP_ELECTRONICS/AINSP_MECH category instead.
    public function addAnnualInspectionFeeItem(Request $request, AnnualInspectionApplication $annualInspectionApplication)
    {
        $codesByGroup = [
            'GEN' => [
                'AINSP_A', 'AINSP_BI_APPEND', 'AINSP_BI_FLOOR',
                'AINSP_C_FIRST', 'AINSP_C_SECOND', 'AINSP_C_THIRD', 'AINSP_C_GRAND',
                'AINSP_D_PLUMB', 'AINSP_EI_ELEC',
            ],
            'ELECTRONICS' => [
                'AINSP_ELEC_SWITCH', 'AINSP_ELEC_BCAST', 'AINSP_ELEC_ATM', 'AINSP_ELEC_OUTLET',
                'AINSP_ELEC_SECUR', 'AINSP_ELEC_STUDIO', 'AINSP_ELEC_TOWER', 'AINSP_ELEC_SIGN',
                'AINSP_ELEC_POLE', 'AINSP_ELEC_ATTACH', 'AINSP_ELEC_OTHER',
            ],
            'MECH' => [
                'AINSP_FI_REFRIG', 'AINSP_FII_WINAC', 'AINSP_FIII_CENAC',
                'AINSP_FV_ESC', 'AINSP_FV_FUNIC', 'AINSP_FV_FUNIC_LM', 'AINSP_FV_CABLE', 'AINSP_FV_CABLE_LM',
                'AINSP_FVI_PASS', 'AINSP_FVI_FRT', 'AINSP_FVI_DUMB', 'AINSP_FVI_CONST', 'AINSP_FVI_CAR',
                'AINSP_FVII_BOILER', 'AINSP_FVIII_WHT', 'AINSP_FIX_FIRE', 'AINSP_FX_DIESEL', 'AINSP_FXI_INTCOMB',
                'AINSP_FXII_COMP', 'AINSP_FXIII_PIPE', 'AINSP_PUMP_WSS', 'AINSP_FXV_PUMP', 'AINSP_FXVI_PRESS',
                'AINSP_FXVII_PNEU', 'AINSP_FXVIII_WEIGH', 'AINSP_FXIX_CALIB', 'AINSP_FXIX_GASM', 'AINSP_FXX_RIDE',
            ],
        ];

        $categoryCodeByGroup = [
            'GEN' => 'AINSP_GEN',
            'ELECTRONICS' => 'AINSP_ELECTRONICS',
            'MECH' => 'AINSP_MECH',
        ];

        $validated = $request->validate([
            'annual_insp_group' => ['required', 'string', Rule::in(array_keys($codesByGroup))],
            'annual_insp_fee_type' => 'required|string',
            'unit' => 'required|numeric|min:0.01',
            'quantity_count' => 'nullable|integer|min:1',
        ]);

        $group = $validated['annual_insp_group'];

        if (! in_array($validated['annual_insp_fee_type'], $codesByGroup[$group], true)) {
            return back()->with('error', 'Invalid fee type for this inspection group.');
        }

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'ai',
                'applicationable_id' => $annualInspectionApplication->id,
                'assessment_type' => 'mechanical',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        if ($r = $this->redirectIfFinalized($assessment, $annualInspectionApplication)) return $r;

        $feeTypeCode = $validated['annual_insp_fee_type'];
        $unit = (float) $validated['unit'];

        $feeType = FeeType::where('code', $feeTypeCode)->first();
        if (! $feeType) {
            return back()->with('error', 'Fee type not found: ' . $feeTypeCode);
        }

        $inspCategory = FeeCategory::where('code', $categoryCodeByGroup[$group])->first();
        if (! $inspCategory) {
            return back()->with('error', 'Fee category not configured: ' . $categoryCodeByGroup[$group]);
        }

        $isRangeBased = $feeType->computation_method === 'range_based';

        $scheduleQuery = FeeSchedule::where('fee_type_id', $feeType->id)->where('is_active', true);
        if ($isRangeBased) {
            $scheduleQuery->where('range_from', '<=', $unit)->where('range_to', '>=', $unit);
        }
        $schedule = $scheduleQuery->orderBy('id')->first();

        if (! $schedule) {
            return back()->with('error', 'No fee schedule found for ' . $feeType->name . ' at unit ' . number_format($unit, 2) . '.');
        }

        $excessFee = 0;
        $unitFee = 0;

        switch ($feeType->computation_method) {
            case 'per_unit':
                $unitFee = (float) $schedule->fee_per_unit;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'fixed':
                $unitFee = (float) $schedule->fixed_fee;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'percentage':
                $unitFee = (float) $schedule->percentage;
                $baseFee = round($unit * $unitFee, 2);
                break;

            case 'range_based':
                $threshold = (float) $schedule->excess_threshold;
                if ($threshold > 0 && $unit > $threshold) {
                    $excessEvery = max(1, (float) $schedule->excess_every);
                    $excess = $unit - $threshold;
                    $excessUnits = (int) ceil($excess / $excessEvery);
                    $excessFee = round($excessUnits * (float) $schedule->excess_fee, 2);
                    $baseFee = round((float) $schedule->fixed_fee + $excessFee, 2);
                } elseif ((float) $schedule->fee_per_unit > 0) {
                    $unitFee = (float) $schedule->fee_per_unit;
                    $baseFee = round($unit * $unitFee, 2);
                } else {
                    $baseFee = round((float) $schedule->fixed_fee, 2);
                }
                break;

            default:
                $baseFee = 0;
        }

        $quantityCount = max(1, (int) ($validated['quantity_count'] ?? 1));
        $amount = round($baseFee * $quantityCount, 2);

        AssessmentItem::create([
            'assessment_id' => $assessment->id,
            'fee_category_id' => $inspCategory->id,
            'fee_type_id' => $feeType->id,
            'fee_code' => $feeType->code,
            'description' => $feeType->name,
            'quantity' => $unit,
            'unit_fee' => $unitFee,
            'excess_fee' => $excessFee,
            'inspection_fee' => 0,
            'amount' => $amount,
            'computation_details' => [
                'group' => $group,
                'fee_type_code' => $feeTypeCode,
                'fee_schedule_id' => $schedule->id,
                'input_unit' => $unit,
                'computation_method' => $feeType->computation_method,
                'quantity_count' => $quantityCount,
            ],
            'is_active' => true,
        ]);

        return redirect()->route('assessments.assess.ai', ['annualInspectionApplication' => $annualInspectionApplication->id, 'tab' => $categoryCodeByGroup[$group]])
            ->with('success', 'Annual inspection fee item added.');
    }

    // AI Electrical Annual Inspection — reuses the existing BP ELEC_* FeeType/FeeSchedule rows
    // (Total Connected Load / Transformer / UPS / Pole / Misc. Meter & Wiring, Settings > Fee
    // Schedules > Electrical, BP-scoped) by code, mirroring addElectricalItem()'s exact
    // computation logic but tagging the AssessmentItem under the AI-scoped AINSP_ELEC category.
    public function addAnnualInspectionElectricalItem(Request $request, AnnualInspectionApplication $annualInspectionApplication)
    {
        $validated = $request->validate([
            'electrical_fee_type' => 'required|string|in:ELEC_TCL,ELEC_TRANS,ELEC_UPS,ELEC_POLE,ELEC_MISC_METER,ELEC_MISC_WIRING',
            'kva' => 'nullable|numeric|min:0.01',
            'pole_type' => 'nullable|string',
            'occupancy_type' => 'nullable|string',
            'quantity_count' => 'nullable|integer|min:1',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'ai',
                'applicationable_id' => $annualInspectionApplication->id,
                'assessment_type' => 'mechanical',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        if ($r = $this->redirectIfFinalized($assessment, $annualInspectionApplication)) return $r;

        $feeTypeCode = $validated['electrical_fee_type'];
        $feeType = FeeType::where('code', $feeTypeCode)->first();
        if (!$feeType) {
            return back()->with('error', 'Electrical fee type not found: ' . $feeTypeCode);
        }

        $inspCategory = FeeCategory::where('code', 'AINSP_ELEC')->first();
        if (!$inspCategory) {
            return back()->with('error', 'Fee category not configured: AINSP_ELEC');
        }

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

            $quantityCount = max(1, (int) ($validated['quantity_count'] ?? 1));
            $amount = round($baseFee * $quantityCount, 2);

            AssessmentItem::create([
                'assessment_id' => $assessment->id,
                'fee_category_id' => $inspCategory->id,
                'fee_type_id' => $feeType->id,
                'fee_code' => $feeType->code,
                'description' => $feeType->name . ' - ' . number_format($kva, 2) . ' kVA',
                'quantity' => $kva,
                'unit_fee' => $feePerUnit,
                'excess_fee' => 0,
                'inspection_fee' => 0,
                'amount' => $amount,
                'computation_details' => [
                    'fee_type_code' => $feeTypeCode,
                    'fee_schedule_id' => $feeSchedule->id,
                    'input_kva' => $kva,
                    'fixed_fee' => $fixedFee,
                    'fee_per_unit' => $feePerUnit,
                    'range' => $feeSchedule->range_from . ' - ' . $feeSchedule->range_to,
                    'quantity_count' => $quantityCount,
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

            AssessmentItem::create([
                'assessment_id' => $assessment->id,
                'fee_category_id' => $inspCategory->id,
                'fee_type_id' => $feeType->id,
                'fee_code' => $feeType->code,
                'description' => $poleType,
                'quantity' => 1,
                'unit_fee' => 0,
                'excess_fee' => 0,
                'inspection_fee' => 0,
                'amount' => $baseFee,
                'computation_details' => [
                    'fee_type_code' => $feeTypeCode,
                    'fee_schedule_id' => $feeSchedule->id,
                    'pole_type' => $poleType,
                    'fixed_fee' => $baseFee,
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

            AssessmentItem::create([
                'assessment_id' => $assessment->id,
                'fee_category_id' => $inspCategory->id,
                'fee_type_id' => $feeType->id,
                'fee_code' => $feeType->code,
                'description' => $feeType->name . ' - ' . $occupancyType,
                'quantity' => 1,
                'unit_fee' => 0,
                'excess_fee' => 0,
                'inspection_fee' => 0,
                'amount' => $baseFee,
                'computation_details' => [
                    'fee_type_code' => $feeTypeCode,
                    'fee_schedule_id' => $feeSchedule->id,
                    'occupancy_type' => $occupancyType,
                    'fixed_fee' => $baseFee,
                ],
                'is_active' => true,
            ]);
        }

        return redirect()->route('assessments.assess.ai', ['annualInspectionApplication' => $annualInspectionApplication->id, 'tab' => 'AINSP_ELEC'])
            ->with('success', 'Electrical fee item added.');
    }

    public function addDemolitionItem(Request $request, DemolitionApplication $demolitionApplication)
    {
        $allCodes = implode(',', [
            'DEMO_FLOOR_AREA', 'DEMO_MECH_EQUIP', 'DEMO_HAND_INCL_FLOORS',
            'DEMO_HAND_EXCL_FLOORS', 'DEMO_APPENDAGE', 'DEMO_MOVING',
        ]);

        $validated = $request->validate([
            'demolition_fee_type' => 'required|string|in:' . $allCodes,
            'quantity'             => 'required|numeric|min:0.01',
        ]);

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => $this->morphTypeFor('DP'),
                'applicationable_id'   => $demolitionApplication->id,
                'assessment_type'      => 'demolition',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        if ($r = $this->redirectIfFinalized($assessment, $demolitionApplication)) return $r;

        $feeType = FeeType::where('code', $validated['demolition_fee_type'])->first();
        if (!$feeType) {
            return back()->with('error', 'Demolition fee type not found: ' . $validated['demolition_fee_type']);
        }

        $demoCategory = FeeCategory::where('code', 'DEMO_FEE')->first();
        $schedule = FeeSchedule::where('fee_type_id', $feeType->id)->where('is_active', true)->orderBy('id')->first();

        if (!$schedule) {
            return back()->with('error', 'No fee schedule found for ' . $feeType->name . '.');
        }

        $quantity = (float) $validated['quantity'];
        $unitFee  = $feeType->computation_method === 'fixed' ? (float) $schedule->fixed_fee : (float) $schedule->fee_per_unit;
        $amount   = round($quantity * $unitFee, 2);

        AssessmentItem::create([
            'assessment_id'       => $assessment->id,
            'fee_category_id'     => $demoCategory->id,
            'fee_type_id'         => $feeType->id,
            'fee_code'            => $feeType->code,
            'description'         => $feeType->name,
            'quantity'            => $quantity,
            'unit_fee'            => $unitFee,
            'excess_fee'          => 0,
            'inspection_fee'      => 0,
            'amount'              => $amount,
            'computation_details' => [
                'fee_type_code'      => $feeType->code,
                'fee_schedule_id'    => $schedule->id,
                'input_quantity'     => $quantity,
                'computation_method' => $feeType->computation_method,
                'fixed_fee'          => (float) $schedule->fixed_fee,
                'fee_per_unit'       => (float) $schedule->fee_per_unit,
                'unit_label'         => $feeType->unit_label,
            ],
            'is_active'           => true,
        ]);

        return redirect()->route('assessments.assess.dp', ['demolitionApplication' => $demolitionApplication->id, 'tab' => 'DEMO_FEE'])
            ->with('success', 'Demolition fee item added.');
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
                'applicationable_type' => $this->morphTypeFor($permitCode),
                'applicationable_id' => $application->id,
                'assessment_type' => $assessmentType,
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        if ($r = $this->redirectIfFinalized($assessment, $application)) return $r;

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
        $route = match ($permitCode) {
            'OP' => route('assessments.assess.op', ['occupancyApplication' => $application->id, 'tab' => $tabCode]),
            'DP' => route('assessments.assess.dp', ['demolitionApplication' => $application->id, 'tab' => $tabCode]),
            'SGP' => route('assessments.assess.sgp', ['signageApplication' => $application->id, 'tab' => $tabCode]),
            'FP' => route('assessments.assess.fp', ['fencingApplication' => $application->id, 'tab' => $tabCode]),
            'AI' => route('assessments.assess.ai', ['annualInspectionApplication' => $application->id, 'tab' => $tabCode]),
            default => route('assessments.assess', ['application' => $application->id, 'tab' => $tabCode]),
        };

        return redirect($route)->with('success', 'Fee item added.');
    }

    public function removeItem(AssessmentItem $assessmentItem)
    {
        $assessment = $assessmentItem->assessment;
        $application = match ($assessment->applicationable_type) {
            'op' => \App\Models\OccupancyApplication::find($assessment->applicationable_id),
            'dp' => \App\Models\DemolitionApplication::find($assessment->applicationable_id),
            'sgp' => \App\Models\SignageApplication::find($assessment->applicationable_id),
            'fp' => \App\Models\FencingApplication::find($assessment->applicationable_id),
            'ai' => \App\Models\AnnualInspectionApplication::find($assessment->applicationable_id),
            default => \App\Models\Application::find($assessment->applicationable_id),
        };
        if ($r = $this->redirectIfFinalized($assessment, $application)) return $r;

        $feeCategory = \App\Models\FeeCategory::find($assessmentItem->fee_category_id);
        $tab = $feeCategory?->code ?? 'CONST';

        $assessmentItem->update(['is_active' => false]);
        $assessmentItem->delete();

        if ($assessment->applicationable_type === 'op') {
            return redirect()->route('assessments.assess.op', ['occupancyApplication' => $assessment->applicationable_id, 'tab' => $tab])
                ->with('success', 'Fee item removed.');
        }

        if ($assessment->applicationable_type === 'dp') {
            return redirect()->route('assessments.assess.dp', ['demolitionApplication' => $assessment->applicationable_id, 'tab' => $tab])
                ->with('success', 'Fee item removed.');
        }

        if ($assessment->applicationable_type === 'sgp') {
            return redirect()->route('assessments.assess.sgp', ['signageApplication' => $assessment->applicationable_id, 'tab' => $tab])
                ->with('success', 'Fee item removed.');
        }

        if ($assessment->applicationable_type === 'fp') {
            return redirect()->route('assessments.assess.fp', ['fencingApplication' => $assessment->applicationable_id, 'tab' => $tab])
                ->with('success', 'Fee item removed.');
        }

        if ($assessment->applicationable_type === 'ai') {
            return redirect()->route('assessments.assess.ai', ['annualInspectionApplication' => $assessment->applicationable_id, 'tab' => $tab])
                ->with('success', 'Fee item removed.');
        }

        return redirect()->route('assessments.assess', ['application' => $assessment->applicationable_id, 'tab' => $tab])
            ->with('success', 'Fee item removed.');
    }

    // BP summary
    public function summary(Application $application)
    {
        return $this->doSummary($application);
    }

    // OP summary
    public function summaryOp(OccupancyApplication $occupancyApplication)
    {
        return $this->doSummary($occupancyApplication, 'OP');
    }

    // DP summary
    public function summaryDp(DemolitionApplication $demolitionApplication)
    {
        return $this->doSummary($demolitionApplication, 'DP');
    }

    // SGP summary
    public function summarySgp(SignageApplication $signageApplication)
    {
        return $this->doSummary($signageApplication, 'SGP');
    }

    public function summaryFp(FencingApplication $fencingApplication)
    {
        return $this->doSummary($fencingApplication, 'FP');
    }

    public function summaryAi(AnnualInspectionApplication $annualInspectionApplication)
    {
        return $this->doSummary($annualInspectionApplication, 'AI');
    }

    private function doSummary(PermitApplicationContract $application, string $permitCode = 'BP')
    {
        $assessments = $application->assessments()->with('assessmentItems.feeCategory')->get();

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

        $assessmentItems = $assessments
            ->flatMap(fn ($a) => $a->assessmentItems->where('is_active', true))
            ->groupBy(fn ($item) => $item->feeCategory->name ?? 'Other Fees');

        $assessment = $assessments->firstWhere('assessment_type', 'engineering') ?? $assessments->last();

        $isOp = $permitCode === 'OP';
        $isDp = $permitCode === 'DP';
        $isSgp = $permitCode === 'SGP';
        $isFp = $permitCode === 'FP';
        $isAi = $permitCode === 'AI';

        return view('assessments.summary', compact('application', 'summary', 'grandTotal', 'assessmentItems', 'assessment', 'isOp', 'isDp', 'isSgp', 'isFp', 'isAi'));
    }

    // BP finalize
    public function finalize(Request $request, Application $application)
    {
        $request->validate(['password' => 'required|string']);
        if (!Hash::check($request->password, Auth::user()->password)) {
            return redirect()
                ->to(route('assessments.assess', $application) . '?tab=SUMMARY')
                ->with('error', 'Incorrect password. Assessment not finalized.');
        }
        $this->doFinalize($application);
        return redirect()
            ->to(route('assessments.assess', $application) . '?tab=SUMMARY')
            ->with('success', 'Assessment finalized successfully.');
    }

    // BP only — send an application back to Zoning while engineering assessment is still ongoing (not yet finalized).
    // Deletes all non-zoning assessment entries (Assessment + AssessmentItem rows) so Engineering starts fresh if it returns.
    public function returnToZoning(Request $request, Application $application)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return redirect()
                ->to(route('assessments.assess', $application) . '?tab=SUMMARY')
                ->with('error', 'Incorrect password. Please try again.');
        }

        if ($application->status !== 'zoning_assessed') {
            return redirect()
                ->to(route('assessments.assess', $application) . '?tab=SUMMARY')
                ->with('error', 'Only applications currently in Engineering Assessment can be returned to Zoning.');
        }

        if ($application->assessments()->where('assessment_type', '!=', 'zoning')->where('status', 'finalized')->exists()) {
            return redirect()
                ->to(route('assessments.assess', $application) . '?tab=SUMMARY')
                ->with('error', 'Cannot return to Zoning: engineering assessment has already been finalized. Revert the finalization first.');
        }

        DB::transaction(function () use ($application) {
            $zoningAssessment = Assessment::where('applicationable_type', 'bp')
                ->where('applicationable_id', $application->id)
                ->where('assessment_type', 'zoning')
                ->first();

            if ($zoningAssessment) {
                $zoningAssessment->update(['status' => 'draft', 'finalized_at' => null]);
            }

            $application->assessments()->where('assessment_type', '!=', 'zoning')->get()->each(function ($assessment) {
                $assessment->assessmentItems()->delete();
                $assessment->delete();
            });

            $application->update(['status' => 'for_zoning_assessment']);
        });

        activity()->causedBy(Auth::user())->performedOn($application)->log('Application returned to Zoning Assessment from Engineering');

        return redirect()->route('assessments.index')->with('success', 'Application returned to Zoning Assessment.');
    }

    // OP finalize
    public function finalizeOp(Request $request, OccupancyApplication $occupancyApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (!Hash::check($request->password, Auth::user()->password)) {
            return redirect()
                ->to(route('assessments.assess.op', $occupancyApplication) . '?tab=SUMMARY')
                ->with('error', 'Incorrect password. Assessment not finalized.');
        }
        $this->doFinalize($occupancyApplication);
        return redirect()
            ->to(route('assessments.assess.op', $occupancyApplication) . '?tab=SUMMARY')
            ->with('success', 'Assessment finalized successfully.');
    }

    // DP finalize
    public function finalizeDp(Request $request, DemolitionApplication $demolitionApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (!Hash::check($request->password, Auth::user()->password)) {
            return redirect()
                ->to(route('assessments.assess.dp', $demolitionApplication) . '?tab=SUMMARY')
                ->with('error', 'Incorrect password. Assessment not finalized.');
        }
        $this->doFinalize($demolitionApplication);
        return redirect()
            ->to(route('assessments.assess.dp', $demolitionApplication) . '?tab=SUMMARY')
            ->with('success', 'Assessment finalized successfully.');
    }

    // SGP finalize
    public function finalizeSgp(Request $request, SignageApplication $signageApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (!Hash::check($request->password, Auth::user()->password)) {
            return redirect()
                ->to(route('assessments.assess.sgp', $signageApplication) . '?tab=SUMMARY')
                ->with('error', 'Incorrect password. Assessment not finalized.');
        }
        $this->doFinalize($signageApplication);
        return redirect()
            ->to(route('assessments.assess.sgp', $signageApplication) . '?tab=SUMMARY')
            ->with('success', 'Assessment finalized successfully.');
    }

    // FP finalize
    public function finalizeFp(Request $request, FencingApplication $fencingApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (!Hash::check($request->password, Auth::user()->password)) {
            return redirect()
                ->to(route('assessments.assess.fp', $fencingApplication) . '?tab=SUMMARY')
                ->with('error', 'Incorrect password. Assessment not finalized.');
        }
        $this->doFinalize($fencingApplication);
        return redirect()
            ->to(route('assessments.assess.fp', $fencingApplication) . '?tab=SUMMARY')
            ->with('success', 'Assessment finalized successfully.');
    }

    // AI finalize
    public function finalizeAi(Request $request, AnnualInspectionApplication $annualInspectionApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (!Hash::check($request->password, Auth::user()->password)) {
            return redirect()
                ->to(route('assessments.assess.ai', $annualInspectionApplication) . '?tab=SUMMARY')
                ->with('error', 'Incorrect password. Assessment not finalized.');
        }
        $this->doFinalize($annualInspectionApplication);
        return redirect()
            ->to(route('assessments.assess.ai', $annualInspectionApplication) . '?tab=SUMMARY')
            ->with('success', 'Assessment finalized successfully.');
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

        app(\App\Services\BillingService::class)->generateFor($application->refresh());

        if ($application->client_user_id) {
            $application->clientUser->notify(new AssessmentCompleteNotification($application));
        }
    }

    // BP revert engineering finalize
    public function revertEngineering(Request $request, Application $application)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        $error = $this->guardRevertEngineering($application);
        if ($error) {
            return back()->with('error', $error);
        }

        $this->doRevertEngineering($application);

        return redirect()
            ->to(route('assessments.assess', $application) . '?tab=SUMMARY')
            ->with('success', 'Engineering assessment finalization reverted.');
    }

    // OP revert engineering finalize
    public function revertEngineeringOp(Request $request, OccupancyApplication $occupancyApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        $error = $this->guardRevertEngineering($occupancyApplication);
        if ($error) {
            return back()->with('error', $error);
        }

        $this->doRevertEngineering($occupancyApplication);

        return redirect()
            ->to(route('assessments.assess.op', $occupancyApplication) . '?tab=SUMMARY')
            ->with('success', 'Engineering assessment finalization reverted.');
    }

    // DP revert engineering finalize
    public function revertEngineeringDp(Request $request, DemolitionApplication $demolitionApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        $error = $this->guardRevertEngineering($demolitionApplication);
        if ($error) {
            return back()->with('error', $error);
        }

        $this->doRevertEngineering($demolitionApplication);

        return redirect()
            ->to(route('assessments.assess.dp', $demolitionApplication) . '?tab=SUMMARY')
            ->with('success', 'Engineering assessment finalization reverted.');
    }

    // SGP revert engineering finalize
    public function revertEngineeringSgp(Request $request, SignageApplication $signageApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        $error = $this->guardRevertEngineering($signageApplication);
        if ($error) {
            return back()->with('error', $error);
        }

        $this->doRevertEngineering($signageApplication);

        return redirect()
            ->to(route('assessments.assess.sgp', $signageApplication) . '?tab=SUMMARY')
            ->with('success', 'Engineering assessment finalization reverted.');
    }

    // FP revert engineering finalize
    public function revertEngineeringFp(Request $request, FencingApplication $fencingApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        $error = $this->guardRevertEngineering($fencingApplication);
        if ($error) {
            return back()->with('error', $error);
        }

        $this->doRevertEngineering($fencingApplication);

        return redirect()
            ->to(route('assessments.assess.fp', $fencingApplication) . '?tab=SUMMARY')
            ->with('success', 'Engineering assessment finalization reverted.');
    }

    // AI revert engineering finalize
    public function revertEngineeringAi(Request $request, AnnualInspectionApplication $annualInspectionApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        $error = $this->guardRevertEngineering($annualInspectionApplication);
        if ($error) {
            return back()->with('error', $error);
        }

        $this->doRevertEngineering($annualInspectionApplication);

        return redirect()
            ->to(route('assessments.assess.ai', $annualInspectionApplication) . '?tab=SUMMARY')
            ->with('success', 'Engineering assessment finalization reverted.');
    }

    // OP only — revert an in-progress (not yet finalized) occupancy assessment all the way back to draft,
    // deleting all occupancy fee entries entered so far.
    public function revertToDraftOp(Request $request, OccupancyApplication $occupancyApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($occupancyApplication->status !== 'zoning_assessed') {
            return back()->with('error', 'Only applications awaiting occupancy assessment can be reverted to draft.');
        }

        DB::transaction(function () use ($occupancyApplication) {
            $assessment = Assessment::where('applicationable_type', 'op')
                ->where('applicationable_id', $occupancyApplication->id)
                ->where('assessment_type', 'occupancy')
                ->first();

            if ($assessment) {
                $assessment->assessmentItems()->delete();
                $assessment->delete();
            }

            $occupancyApplication->update(['status' => 'draft', 'submitted_at' => null]);
        });

        activity()->causedBy(Auth::user())->performedOn($occupancyApplication)->log('Occupancy assessment reverted to draft — all fee entries deleted');

        return redirect()->route('assessments.occupancy')->with('success', 'Application reverted to draft. All occupancy fee entries were deleted.');
    }

    // DP only — revert an in-progress (not yet finalized) demolition assessment all the way back to draft,
    // deleting all demolition fee entries entered so far.
    public function revertToDraftDp(Request $request, DemolitionApplication $demolitionApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($demolitionApplication->status !== 'submitted') {
            return back()->with('error', 'Only applications awaiting demolition assessment can be reverted to draft.');
        }

        DB::transaction(function () use ($demolitionApplication) {
            $assessment = Assessment::where('applicationable_type', 'dp')
                ->where('applicationable_id', $demolitionApplication->id)
                ->where('assessment_type', 'demolition')
                ->first();

            if ($assessment) {
                $assessment->assessmentItems()->delete();
                $assessment->delete();
            }

            $demolitionApplication->update(['status' => 'draft', 'submitted_at' => null]);
        });

        activity()->causedBy(Auth::user())->performedOn($demolitionApplication)->log('Demolition assessment reverted to draft — all fee entries deleted');

        return redirect()->route('assessments.demolition')->with('success', 'Application reverted to draft. All demolition fee entries were deleted.');
    }

    // SGP only — revert an in-progress (not yet finalized) signage assessment all the way back to draft,
    // deleting all signage fee entries entered so far.
    public function revertToDraftSgp(Request $request, SignageApplication $signageApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($signageApplication->status !== 'submitted') {
            return back()->with('error', 'Only applications awaiting signage assessment can be reverted to draft.');
        }

        DB::transaction(function () use ($signageApplication) {
            $assessment = Assessment::where('applicationable_type', 'sgp')
                ->where('applicationable_id', $signageApplication->id)
                ->where('assessment_type', 'signage')
                ->first();

            if ($assessment) {
                $assessment->assessmentItems()->delete();
                $assessment->delete();
            }

            $signageApplication->update(['status' => 'draft', 'submitted_at' => null]);
        });

        activity()->causedBy(Auth::user())->performedOn($signageApplication)->log('Signage assessment reverted to draft — all fee entries deleted');

        return redirect()->route('assessments.signage')->with('success', 'Application reverted to draft. All signage fee entries were deleted.');
    }

    // FP only — revert an in-progress (not yet finalized) fencing assessment all the way back to draft,
    // deleting all fencing fee entries entered so far.
    public function revertToDraftFp(Request $request, FencingApplication $fencingApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($fencingApplication->status !== 'submitted') {
            return back()->with('error', 'Only applications awaiting fencing assessment can be reverted to draft.');
        }

        DB::transaction(function () use ($fencingApplication) {
            $assessment = Assessment::where('applicationable_type', 'fp')
                ->where('applicationable_id', $fencingApplication->id)
                ->where('assessment_type', 'fencing')
                ->first();

            if ($assessment) {
                $assessment->assessmentItems()->delete();
                $assessment->delete();
            }

            $fencingApplication->update(['status' => 'draft', 'submitted_at' => null]);
        });

        activity()->causedBy(Auth::user())->performedOn($fencingApplication)->log('Fencing assessment reverted to draft — all fee entries deleted');

        return redirect()->route('assessments.fencing')->with('success', 'Application reverted to draft. All fencing fee entries were deleted.');
    }

    // AI only — revert an in-progress (not yet finalized) mechanical assessment all the way back to draft,
    // deleting all mechanical fee entries entered so far.
    public function revertToDraftAi(Request $request, AnnualInspectionApplication $annualInspectionApplication)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if ($annualInspectionApplication->status !== 'submitted') {
            return back()->with('error', 'Only applications awaiting mechanical assessment can be reverted to draft.');
        }

        DB::transaction(function () use ($annualInspectionApplication) {
            $assessment = Assessment::where('applicationable_type', 'ai')
                ->where('applicationable_id', $annualInspectionApplication->id)
                ->where('assessment_type', 'mechanical')
                ->first();

            if ($assessment) {
                $assessment->assessmentItems()->delete();
                $assessment->delete();
            }

            $annualInspectionApplication->update(['status' => 'draft', 'submitted_at' => null]);
        });

        activity()->causedBy(Auth::user())->performedOn($annualInspectionApplication)->log('Annual Inspection assessment reverted to draft — all fee entries deleted');

        return redirect()->route('assessments.annualInspection')->with('success', 'Application reverted to draft. All mechanical fee entries were deleted.');
    }

    private function guardRevertEngineering(PermitApplicationContract $application): ?string
    {
        if (! in_array($application->status, ['engineering_assessed', 'billed'])) {
            return 'Engineering assessment is not finalized or already reverted.';
        }

        if ($application->status === 'billed') {
            $unpaidBilling = $application->billings()->where('status', 'unpaid')->first();

            if (! $unpaidBilling) {
                return 'Cannot revert: this application has already been paid.';
            }

            if ($application->collections()->where('status', 'active')->exists()) {
                return 'Cannot revert: this application has already been paid.';
            }
        }

        return null;
    }

    private function doRevertEngineering(PermitApplicationContract $application): void
    {
        DB::transaction(function () use ($application) {
            $billing = $application->billings()->where('status', 'unpaid')->first();
            if ($billing) {
                $billing->billingItems()->delete();
                $billing->delete();
            }

            $application->assessments()->where('status', 'finalized')->get()->each(function ($assessment) {
                $assessment->update(['status' => 'draft', 'finalized_at' => null, 'assessed_by' => null]);
            });

            // DP/SGP/FP/AI have no zoning stage — their pre-engineering-assessment status is 'submitted', not 'zoning_assessed'.
            $revertStatus = in_array($application->getPermitTypeCode(), ['DP', 'SGP', 'FP', 'AI']) ? 'submitted' : 'zoning_assessed';

            $application->update([
                'status' => $revertStatus,
                'assessed_by' => null,
                'assessed_at' => null,
            ]);
        });

        activity()->causedBy(Auth::user())->performedOn($application)->log('Engineering assessment finalization reverted');
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

    // DP print
    public function printDp(DemolitionApplication $demolitionApplication)
    {
        return $this->doPrint($demolitionApplication);
    }

    // SGP print
    public function printSgp(SignageApplication $signageApplication)
    {
        return $this->doPrint($signageApplication);
    }

    // FP print
    public function printFp(FencingApplication $fencingApplication)
    {
        return $this->doPrint($fencingApplication);
    }

    // AI print
    public function printAi(AnnualInspectionApplication $annualInspectionApplication)
    {
        return $this->doPrint($annualInspectionApplication);
    }

    private function doPrint(PermitApplicationContract $application)
    {
        $settings = Setting::where('group', 'general')->pluck('value', 'key');
        $isOp = $application->getPermitTypeCode() === 'OP';
        $isDp = $application->getPermitTypeCode() === 'DP';
        $isSgp = $application->getPermitTypeCode() === 'SGP';
        $isFp = $application->getPermitTypeCode() === 'FP';
        $isMp = $application->getPermitTypeCode() === 'AI';

        if ($isDp) {
            return $this->doPrintDp($application, $settings);
        }

        if ($isSgp) {
            return $this->doPrintSgp($application, $settings);
        }

        if ($isFp) {
            return $this->doPrintFp($application, $settings);
        }

        if ($isMp) {
            return $this->doPrintAi($application, $settings);
        }

        if ($isOp) {
            return $this->doPrintOp($application, $settings);
        }

        $buildingAssessment = $application->assessments()
            ->where('assessment_type', 'building')
            ->with(['assessmentItems' => fn($q) => $q->where('is_active', true)->with('feeCategory')])
            ->first();

        $zoningAssessment = $application->assessments()
            ->where('assessment_type', 'zoning')
            ->with(['assessmentItems' => fn($q) => $q->where('is_active', true)->with('feeCategory')])
            ->first();

        $itemsByCategory = $buildingAssessment
            ? $buildingAssessment->assessmentItems->groupBy(fn($i) => $i->feeCategory?->code ?? 'OTHER')
            : collect();

        $zoningByCategory = $zoningAssessment
            ? $zoningAssessment->assessmentItems->groupBy(fn($i) => $i->feeCategory?->code ?? 'OTHER')
            : collect();

        $barangayName = '';
        if ($application->building_barangay_id) {
            $barangay = \App\Models\Barangay::find($application->building_barangay_id);
            $barangayName = $barangay?->name ?? '';
        }

        $preparedBy = $buildingAssessment?->assessed_by
            ? \App\Models\User::find($buildingAssessment->assessed_by)
            : null;

        $buildingOfficial = Signatory::where('role', 'building_official')
            ->where('is_active', true)
            ->first();

        $generator    = new BarcodeGeneratorPNG();
        $barcodeImage = base64_encode(
            $generator->getBarcode($application->application_number, $generator::TYPE_CODE_128, 2, 80)
        );

        $sealImage = Setting::imageDataUri($settings, 'general.logo');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.assessment-summary', compact(
            'application', 'settings', 'sealImage', 'buildingAssessment',
            'zoningAssessment', 'itemsByCategory', 'zoningByCategory',
            'barangayName', 'preparedBy', 'buildingOfficial', 'barcodeImage'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("assessment_{$application->application_number}.pdf");
    }

    private function doPrintOp(PermitApplicationContract $application, $settings)
    {
        $occupancyAssessment = $application->assessments()
            ->where('assessment_type', 'occupancy')
            ->with(['assessmentItems' => fn($q) => $q->where('is_active', true)->with('feeCategory')])
            ->first();

        $itemsByCategory = $occupancyAssessment
            ? $occupancyAssessment->assessmentItems->groupBy(fn($i) => $i->feeCategory?->code ?? 'OTHER')
            : collect();

        $barangayName = '';
        if ($application->building_barangay_id) {
            $barangay = \App\Models\Barangay::find($application->building_barangay_id);
            $barangayName = $barangay?->name ?? '';
        }

        $preparedBy = $occupancyAssessment?->assessed_by
            ? \App\Models\User::find($occupancyAssessment->assessed_by)
            : null;

        $buildingOfficial = Signatory::where('role', 'building_official')
            ->where('is_active', true)
            ->first();

        $generator    = new BarcodeGeneratorPNG();
        $barcodeImage = base64_encode(
            $generator->getBarcode($application->application_number, $generator::TYPE_CODE_128, 2, 80)
        );

        $sealImage = Setting::imageDataUri($settings, 'general.logo');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.assessment-summary-op', compact(
            'application', 'settings', 'sealImage', 'occupancyAssessment',
            'itemsByCategory', 'barangayName', 'preparedBy',
            'buildingOfficial', 'barcodeImage'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("assessment_{$application->application_number}.pdf");
    }

    private function doPrintDp(PermitApplicationContract $application, $settings)
    {
        $demolitionAssessment = $application->assessments()
            ->where('assessment_type', 'demolition')
            ->with(['assessmentItems' => fn($q) => $q->where('is_active', true)->with('feeCategory')])
            ->first();

        $itemsByCategory = $demolitionAssessment
            ? $demolitionAssessment->assessmentItems->groupBy(fn($i) => $i->feeCategory?->code ?? 'OTHER')
            : collect();

        $barangayName = '';
        if ($application->demolition_barangay_id) {
            $barangay = \App\Models\Barangay::find($application->demolition_barangay_id);
            $barangayName = $barangay?->name ?? '';
        }

        $preparedBy = $demolitionAssessment?->assessed_by
            ? \App\Models\User::find($demolitionAssessment->assessed_by)
            : null;

        $buildingOfficial = Signatory::where('role', 'building_official')
            ->where('is_active', true)
            ->first();

        $generator    = new BarcodeGeneratorPNG();
        $barcodeImage = base64_encode(
            $generator->getBarcode($application->application_number, $generator::TYPE_CODE_128, 2, 80)
        );

        $sealImage = Setting::imageDataUri($settings, 'general.logo');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.assessment-summary-dp', compact(
            'application', 'settings', 'sealImage', 'demolitionAssessment',
            'itemsByCategory', 'barangayName', 'preparedBy',
            'buildingOfficial', 'barcodeImage'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("assessment_{$application->application_number}.pdf");
    }

    private function doPrintSgp(PermitApplicationContract $application, $settings)
    {
        $signageAssessment = $application->assessments()
            ->where('assessment_type', 'signage')
            ->with(['assessmentItems' => fn($q) => $q->where('is_active', true)->with('feeCategory')])
            ->first();

        $itemsByCategory = $signageAssessment
            ? $signageAssessment->assessmentItems->groupBy(fn($i) => $i->feeCategory?->code ?? 'OTHER')
            : collect();

        $barangayName = $application->applicantBarangay?->name ?? '';

        $preparedBy = $signageAssessment?->assessed_by
            ? \App\Models\User::find($signageAssessment->assessed_by)
            : null;

        $buildingOfficial = Signatory::where('role', 'building_official')
            ->where('is_active', true)
            ->first();

        $generator    = new BarcodeGeneratorPNG();
        $barcodeImage = base64_encode(
            $generator->getBarcode($application->application_number, $generator::TYPE_CODE_128, 2, 80)
        );

        $sealImage = Setting::imageDataUri($settings, 'general.logo');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.assessment-summary-sgp', compact(
            'application', 'settings', 'sealImage', 'signageAssessment',
            'itemsByCategory', 'barangayName', 'preparedBy',
            'buildingOfficial', 'barcodeImage'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("assessment_{$application->application_number}.pdf");
    }

    private function doPrintFp(PermitApplicationContract $application, $settings)
    {
        $fencingAssessment = $application->assessments()
            ->where('assessment_type', 'fencing')
            ->with(['assessmentItems' => fn($q) => $q->where('is_active', true)->with('feeCategory')])
            ->first();

        $itemsByCategory = $fencingAssessment
            ? $fencingAssessment->assessmentItems->groupBy(fn($i) => $i->feeCategory?->code ?? 'OTHER')
            : collect();

        $barangayName = $application->constructionBarangay?->name ?? '';

        $preparedBy = $fencingAssessment?->assessed_by
            ? \App\Models\User::find($fencingAssessment->assessed_by)
            : null;

        $buildingOfficial = Signatory::where('role', 'building_official')
            ->where('is_active', true)
            ->first();

        $generator    = new BarcodeGeneratorPNG();
        $barcodeImage = base64_encode(
            $generator->getBarcode($application->application_number, $generator::TYPE_CODE_128, 2, 80)
        );

        $sealImage = Setting::imageDataUri($settings, 'general.logo');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.assessment-summary-fp', compact(
            'application', 'settings', 'sealImage', 'fencingAssessment',
            'itemsByCategory', 'barangayName', 'preparedBy',
            'buildingOfficial', 'barcodeImage'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("assessment_{$application->application_number}.pdf");
    }

    private function doPrintAi(PermitApplicationContract $application, $settings)
    {
        $mechanicalAssessment = $application->assessments()
            ->where('assessment_type', 'mechanical')
            ->with(['assessmentItems' => fn($q) => $q->where('is_active', true)->with('feeCategory')])
            ->first();

        $itemsByCategory = $mechanicalAssessment
            ? $mechanicalAssessment->assessmentItems->groupBy(fn($i) => $i->feeCategory?->code ?? 'OTHER')
            : collect();

        $barangayName = $application->locationBarangay?->name ?? '';

        $preparedBy = $mechanicalAssessment?->assessed_by
            ? \App\Models\User::find($mechanicalAssessment->assessed_by)
            : null;

        $buildingOfficial = Signatory::where('role', 'building_official')
            ->where('is_active', true)
            ->first();

        $generator    = new BarcodeGeneratorPNG();
        $barcodeImage = base64_encode(
            $generator->getBarcode($application->application_number, $generator::TYPE_CODE_128, 2, 80)
        );

        $sealImage = Setting::imageDataUri($settings, 'general.logo');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.assessment-summary-ai', compact(
            'application', 'settings', 'sealImage', 'mechanicalAssessment',
            'itemsByCategory', 'barangayName', 'preparedBy',
            'buildingOfficial', 'barcodeImage'
        ));
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
