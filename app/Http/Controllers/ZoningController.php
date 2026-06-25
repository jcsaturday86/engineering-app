<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Assessment;
use App\Models\AssessmentItem;
use App\Models\CertificationZoningFee;
use App\Models\FeeCategory;
use App\Models\LandUseAndZoningFee;
use App\Models\OccupancySubGroup;
use App\Models\ZoningAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ZoningController extends Controller
{
    public function index()
    {
        $applications = Application::with('permitType')
            ->where('status', 'for_zoning_assessment')
            ->latest()
            ->paginate(20);

        return view('zoning.index', compact('applications'));
    }

    public function assess(Application $application)
    {
        $application->load([
            'applicationType', 'formOfOwnership', 'scopeOfWork', 'landClassification',
            'applicantProvince', 'applicantCity', 'applicantBarangay',
            'buildingBarangay', 'applicationOccupancyGroups.occupancySubGroup.occupancyGroup',
        ]);

        $zoningAssessment = $application->zoningAssessment ?? new ZoningAssessment();

        $assessment = Assessment::where('applicationable_type', 'bp')
            ->where('applicationable_id', $application->id)
            ->where('assessment_type', 'zoning')
            ->first();

        $assessmentItems = $assessment
            ? $assessment->assessmentItems()->where('is_active', true)->get()
            : collect();

        $feeCategories = FeeCategory::with('feeTypes')
            ->whereIn('code', ['ZONING_LC', 'ZONING_CERT'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('zoning.assess', compact(
            'application', 'zoningAssessment', 'assessment', 'assessmentItems', 'feeCategories'
        ));
    }

    public function store(Request $request, Application $application)
    {
        $validated = $request->validate([
            'project_lifespan' => 'required|in:Permanent,Temporary',
            'project_significance' => 'required|in:Regular,Special',
            'project_classification' => 'required|string|max:255',
            'radius_covered' => 'required|in:100m (Regular Project),1km (Special Project)',
            'project_status' => 'required|string|max:255',
            'site_zoning_classification' => 'nullable|string|max:255',
            'right_over_lands' => 'nullable|string|max:255',
            'land_use_radius' => 'nullable|string|max:255',
            'boundary_north' => 'nullable|string|max:255',
            'boundary_south' => 'nullable|string|max:255',
            'boundary_east' => 'nullable|string|max:255',
            'boundary_west' => 'nullable|string|max:255',
            'building_coverage' => 'nullable|string|max:255',
            'secure_ecc' => 'boolean',
            'off_street_parking' => 'boolean',
        ]);

        $validated['application_id'] = $application->id;
        $validated['assessed_by'] = Auth::id();

        ZoningAssessment::updateOrCreate(
            ['application_id' => $application->id],
            $validated
        );

        return back()->with('success', 'Zoning assessment saved.');
    }

    public function autoCompute(Application $application)
    {
        $application->load('applicationOccupancyGroups');

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'bp',
                'applicationable_id' => $application->id,
                'assessment_type' => 'zoning',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        $totalCost = (float) $application->total_estimated_cost;
        $subGroupIds = $application->applicationOccupancyGroups->pluck('occupancy_sub_group_id')->unique();

        $added = 0;

        DB::transaction(function () use ($assessment, $totalCost, $subGroupIds, &$added) {
            if ($totalCost > 0) {
                foreach ($subGroupIds as $subGroupId) {
                    $existing = $assessment->assessmentItems()
                        ->where('fee_code', 'ZONING_LC_FEE')
                        ->where('is_active', true)
                        ->whereJsonContains('computation_details->sub_group_id', $subGroupId)
                        ->exists();

                    if ($existing) {
                        continue;
                    }

                    $feeRow = LandUseAndZoningFee::where('occupancy_sub_group_id', $subGroupId)
                        ->where('range_from', '<=', $totalCost)
                        ->where('range_to', '>=', $totalCost)
                        ->where('is_active', true)
                        ->first();

                    if (!$feeRow || $feeRow->amount == 0) {
                        continue;
                    }

                    $excess = $totalCost - $feeRow->excess_of;
                    $excessAmount = ($feeRow->excess_of > 0 && $excess > 0)
                        ? round($excess * (float) $feeRow->percentage, 2)
                        : 0;
                    $amount = round((float) $feeRow->amount + $excessAmount, 2);

                    $subGroup = OccupancySubGroup::with('occupancyGroup')->find($subGroupId);
                    $desc = 'Locational Clearance Fee';
                    if ($subGroup) {
                        $desc .= ' (' . $subGroup->occupancyGroup->code . ': ' . $subGroup->name . ')';
                    }

                    AssessmentItem::create([
                        'assessment_id' => $assessment->id,
                        'fee_code' => 'ZONING_LC_FEE',
                        'description' => $desc,
                        'quantity' => $totalCost,
                        'unit_fee' => $feeRow->amount,
                        'excess_fee' => $excessAmount,
                        'inspection_fee' => 0,
                        'amount' => $amount,
                        'computation_details' => [
                            'method' => 'range_based',
                            'base_amount' => (float) $feeRow->amount,
                            'excess_amount' => $excessAmount,
                            'sub_group_id' => $subGroupId,
                            'fee_row_id' => $feeRow->id,
                        ],
                        'is_active' => true,
                    ]);
                    $added++;
                }
            }

            $certFee = CertificationZoningFee::where('is_active', true)->first();
            if ($certFee) {
                $certExists = $assessment->assessmentItems()
                    ->where('fee_code', 'ZONING_CERT_FEE')
                    ->where('is_active', true)
                    ->exists();

                if (!$certExists) {
                    AssessmentItem::create([
                        'assessment_id' => $assessment->id,
                        'fee_code' => 'ZONING_CERT_FEE',
                        'description' => 'Zoning Certification Fee',
                        'quantity' => 1,
                        'unit_fee' => $certFee->amount,
                        'excess_fee' => 0,
                        'inspection_fee' => 0,
                        'amount' => $certFee->amount,
                        'computation_details' => ['method' => 'fixed'],
                        'is_active' => true,
                    ]);
                    $added++;
                }
            }

            $total = $assessment->assessmentItems()->where('is_active', true)->sum('amount');
            $assessment->update(['total_amount' => $total]);
        });

        $msg = $added > 0
            ? "Auto-computed {$added} zoning fee item(s)."
            : 'No new fee items to compute (already exists or no data).';

        return back()->with('success', $msg);
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

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'bp',
                'applicationable_id' => $application->id,
                'assessment_type' => 'zoning',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        $feeCategory = FeeCategory::find($validated['fee_category_id']);
        $feeType = $validated['fee_type_id'] ? FeeType::find($validated['fee_type_id']) : null;

        AssessmentItem::create([
            'assessment_id' => $assessment->id,
            'fee_category_id' => $validated['fee_category_id'],
            'fee_type_id' => $validated['fee_type_id'],
            'fee_code' => $feeType->code ?? $feeCategory->code,
            'description' => ($validated['description'] ?? null) ?: ($feeType->name ?? $feeCategory->name),
            'quantity' => $validated['quantity'],
            'unit_fee' => $validated['unit_fee'],
            'excess_fee' => 0,
            'inspection_fee' => 0,
            'amount' => $validated['quantity'] * $validated['unit_fee'],
        ]);

        $total = $assessment->assessmentItems()->where('is_active', true)->sum('amount');
        $assessment->update(['total_amount' => $total]);

        return back()->with('success', 'Fee item added.');
    }

    public function removeItem(AssessmentItem $assessmentItem)
    {
        $assessment = $assessmentItem->assessment;
        $assessmentItem->update(['is_active' => false]);
        $assessmentItem->delete();

        $total = $assessment->assessmentItems()->where('is_active', true)->sum('amount');
        $assessment->update(['total_amount' => $total]);

        return back()->with('success', 'Fee item removed.');
    }

    public function finalize(Application $application)
    {
        $assessment = Assessment::where('applicationable_type', 'bp')
            ->where('applicationable_id', $application->id)
            ->where('assessment_type', 'zoning')
            ->first();

        if ($assessment && $assessment->status !== 'finalized') {
            $total = $assessment->assessmentItems()->where('is_active', true)->sum('amount');
            $assessment->update([
                'total_amount' => $total,
                'status' => 'finalized',
                'finalized_at' => now(),
            ]);
        }

        if ($application->status === 'for_zoning_assessment') {
            $application->update(['status' => 'zoning_assessed']);
            activity()->causedBy(Auth::user())->performedOn($application)->log('Zoning assessment finalized');
        }

        return redirect()->route('zoning.index')->with('success', 'Zoning assessment finalized.');
    }

    public function skip(Application $application)
    {
        if ($application->status === 'for_zoning_assessment') {
            $application->update(['status' => 'zoning_assessed']);
            activity()->causedBy(Auth::user())->performedOn($application)->log('Zoning assessment skipped');
        }

        return redirect()->route('zoning.index')->with('success', 'Zoning assessment skipped.');
    }
}
