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
use App\Models\Signatory;
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

        return view('assessments.assess', compact(
            'application', 'assessment', 'feeCategories', 'tabCategories',
            'totals', 'assessmentItems', 'itemsByCategory', 'activeTab', 'isOp',
            'buildingParts', 'occupancyDivisions'
        ));
    }

    private function redirectIfFinalized(Assessment $assessment, PermitApplicationContract $application): ?\Illuminate\Http\RedirectResponse
    {
        if ($assessment->status === 'finalized') {
            $isOp = $assessment->applicationable_type === 'op';
            $route = $isOp
                ? route('assessments.assess.op', $application) . '?tab=SUMMARY'
                : route('assessments.assess', $application) . '?tab=SUMMARY';
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

        // Inspection fee — reads from MECH_INSP fee_schedules (INSP_* fee types, mirrors BOPMS ann_inspection_f* tables).
        $insp                = $this->resolveInspectionFee($feeTypeCode, $unit);
        $inspFee             = $insp['fee'];
        $inspMethod          = $insp['method'];
        $inspExcessThreshold = $insp['excess_threshold'];
        $inspExcessFee       = $insp['excess_fee'];
        $inspExcessEvery     = $insp['every'];

        switch ($inspMethod) {
            case 'flat':
                // Flat fee for the range band regardless of unit count (A. refrigeration/AC/vent)
                if ($inspExcessThreshold > 0 && $unit > $inspExcessThreshold) {
                    $inspectionFee = round($inspFee + (($unit - $inspExcessThreshold) / $inspExcessEvery) * $inspExcessFee, 2);
                } else {
                    $inspectionFee = round($inspFee, 2);
                }
                break;

            case 'tiered':
                // First N units at insp_fee, remaining at insp_excess_fee (C elevators)
                if ($inspExcessThreshold > 0 && $unit > $inspExcessThreshold) {
                    $inspectionFee = round($inspExcessThreshold * $inspFee + ($unit - $inspExcessThreshold) * $inspExcessFee, 2);
                } else {
                    $inspectionFee = round($unit * $inspFee, 2);
                }
                break;

            default: // per_unit — B escalators, D boiler, H diesel, L ICE, most O
                if ($inspExcessThreshold > 0 && $unit > $inspExcessThreshold) {
                    // Base: threshold × insp_fee; excess: ((unit-threshold)/every) × excess_fee
                    $inspectionFee = round($inspExcessThreshold * $inspFee + (($unit - $inspExcessThreshold) / $inspExcessEvery) * $inspExcessFee, 2);
                } else {
                    $inspectionFee = round($unit * $inspFee, 2);
                }
        }

        // amount = base fee only; inspection_fee is stored separately so the
        // grand total formula (sum(amount) + sum(inspection_fee)) gives the correct total.
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
            'inspection_fee'      => $inspectionFee,
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
                'insp_method'           => $inspMethod,
                'insp_fee'              => $inspFee,
                'insp_excess_threshold' => $inspExcessThreshold,
                'insp_excess_fee'       => $inspExcessFee,
                'insp_excess_every'     => $inspExcessEvery,
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
            // Percentage: base = sum of amount + inspection_fee across the 7 core BP assessment categories
            $bpCategoryIds = FeeCategory::whereIn('code', $bpCategoryCodes)->pluck('id');

            $bpItems = $assessment->assessmentItems()
                ->where('is_active', true)
                ->whereIn('fee_category_id', $bpCategoryIds)
                ->get();

            $totalBPFeeBase = $bpItems->sum('amount') + $bpItems->sum('inspection_fee');

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
        $route = $permitCode === 'OP'
            ? route('assessments.assess.op', ['occupancyApplication' => $application->id, 'tab' => $tabCode])
            : route('assessments.assess', ['application' => $application->id, 'tab' => $tabCode]);

        return redirect($route)->with('success', 'Fee item added.');
    }

    public function removeItem(AssessmentItem $assessmentItem)
    {
        $assessment = $assessmentItem->assessment;
        $application = $assessment->applicationable_type === 'op'
            ? \App\Models\OccupancyApplication::find($assessment->applicationable_id)
            : \App\Models\Application::find($assessment->applicationable_id);
        if ($r = $this->redirectIfFinalized($assessment, $application)) return $r;

        $feeCategory = \App\Models\FeeCategory::find($assessmentItem->fee_category_id);
        $tab = $feeCategory?->code ?? 'CONST';

        $assessmentItem->update(['is_active' => false]);
        $assessmentItem->delete();

        if ($assessment->applicationable_type === 'op') {
            return redirect()->route('assessments.assess.op', ['occupancyApplication' => $assessment->applicationable_id, 'tab' => $tab])
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
        $settings = Setting::where('group', 'general')->pluck('value', 'key');

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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.assessment-summary', compact(
            'application', 'settings', 'buildingAssessment',
            'zoningAssessment', 'itemsByCategory', 'zoningByCategory',
            'barangayName', 'preparedBy', 'buildingOfficial', 'barcodeImage'
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
