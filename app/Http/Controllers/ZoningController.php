<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Assessment;
use App\Models\AssessmentItem;
use App\Models\CertificationZoningFee;
use App\Models\LandUseAndZoningFee;
use App\Models\LandUseAndZoningOtherFee;
use App\Models\OccupancyGroup;
use App\Models\OccupancySubGroup;
use App\Models\ZoningAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ZoningController extends Controller
{
    private function zoningAssessmentIsFinalized(Application $application): bool
    {
        return Assessment::where('applicationable_type', 'bp')
            ->where('applicationable_id', $application->id)
            ->where('assessment_type', 'zoning')
            ->where('status', 'finalized')
            ->exists();
    }

    private function abortIfZoningFinalized(Application $application): void
    {
        if ($this->zoningAssessmentIsFinalized($application)) {
            abort(403, 'Zoning assessment is finalized and cannot be modified.');
        }
    }

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

        $occupancyGroups = OccupancyGroup::with(['subGroups' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $certFee = CertificationZoningFee::where('is_active', true)->first();
        $otherFees = LandUseAndZoningOtherFee::where('is_active', true)->get();

        return view('zoning.assess', compact(
            'application', 'zoningAssessment', 'assessment', 'assessmentItems',
            'occupancyGroups', 'certFee', 'otherFees'
        ));
    }

    public function store(Request $request, Application $application)
    {
        $this->abortIfZoningFinalized($application);

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
        $this->abortIfZoningFinalized($application);

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

        $msg = $added > 0
            ? "Auto-computed {$added} zoning fee item(s)."
            : 'No new fee items to compute (already exists or no data).';

        return back()->with('success', $msg);
    }

    public function addItem(Request $request, Application $application)
    {
        $this->abortIfZoningFinalized($application);

        $feeType = $request->input('fee_type');

        $assessment = Assessment::firstOrCreate(
            [
                'applicationable_type' => 'bp',
                'applicationable_id' => $application->id,
                'assessment_type' => 'zoning',
            ],
            ['status' => 'draft', 'assessed_by' => Auth::id()]
        );

        match ($feeType) {
            'lc' => $this->addLocationalClearance($request, $application, $assessment),
            'lc_manual' => $this->addLocationalClearanceManual($request, $assessment),
            'cert' => $this->addCertification($request, $assessment),
            'others' => $this->addOtherFee($request, $assessment),
            default => abort(422, 'Invalid fee type.'),
        };

        $total = $assessment->assessmentItems()->where('is_active', true)->sum('amount');
        $assessment->update(['total_amount' => $total]);

        return back()->with('success', 'Fee item added.');
    }

    private function addLocationalClearance(Request $request, Application $application, Assessment $assessment): void
    {
        $request->validate([
            'occupancy_sub_group_id' => 'required|exists:occupancy_sub_groups,id',
        ]);

        $subGroupId = (int) $request->input('occupancy_sub_group_id');
        $totalCost = (float) $application->total_estimated_cost;

        $feeRow = LandUseAndZoningFee::where('occupancy_sub_group_id', $subGroupId)
            ->where('range_from', '<=', $totalCost)
            ->where('range_to', '>=', $totalCost)
            ->where('is_active', true)
            ->first();

        if (!$feeRow || $feeRow->amount == 0) {
            abort(back()->with('error', 'No fee schedule found for the selected sub-group and project cost.'));
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
    }

    private function addLocationalClearanceManual(Request $request, Assessment $assessment): void
    {
        $request->validate([
            'manual_description' => 'required|string|max:255',
            'manual_amount' => 'required|numeric|min:0.01',
        ]);

        AssessmentItem::create([
            'assessment_id' => $assessment->id,
            'fee_code' => 'ZONING_LC_MANUAL',
            'description' => $request->input('manual_description'),
            'quantity' => 1,
            'unit_fee' => $request->input('manual_amount'),
            'excess_fee' => 0,
            'inspection_fee' => 0,
            'amount' => $request->input('manual_amount'),
            'computation_details' => ['method' => 'manual'],
            'is_active' => true,
        ]);
    }

    private function addCertification(Request $request, Assessment $assessment): void
    {
        $request->validate([
            'cert_sub_group_id' => 'required|exists:occupancy_sub_groups,id',
        ]);

        $certFee = CertificationZoningFee::where('is_active', true)->first();
        if (!$certFee) {
            abort(back()->with('error', 'No certification fee configured.'));
        }

        $subGroup = OccupancySubGroup::with('occupancyGroup')->find($request->input('cert_sub_group_id'));
        $desc = 'Zoning Certification Fee';
        if ($subGroup) {
            $desc .= ' (' . $subGroup->occupancyGroup->code . ': ' . $subGroup->name . ')';
        }

        AssessmentItem::create([
            'assessment_id' => $assessment->id,
            'fee_code' => 'ZONING_CERT_FEE',
            'description' => $desc,
            'quantity' => 1,
            'unit_fee' => $certFee->amount,
            'excess_fee' => 0,
            'inspection_fee' => 0,
            'amount' => $certFee->amount,
            'computation_details' => ['method' => 'fixed'],
            'is_active' => true,
        ]);
    }

    private function addOtherFee(Request $request, Assessment $assessment): void
    {
        $request->validate([
            'other_fee_id' => 'required|exists:land_use_and_zoning_other_fees,id',
        ]);

        $otherFee = LandUseAndZoningOtherFee::findOrFail($request->input('other_fee_id'));

        AssessmentItem::create([
            'assessment_id' => $assessment->id,
            'fee_code' => 'ZONING_OTHER_' . $otherFee->code,
            'description' => $otherFee->name,
            'quantity' => 1,
            'unit_fee' => $otherFee->amount,
            'excess_fee' => 0,
            'inspection_fee' => 0,
            'amount' => $otherFee->amount,
            'computation_details' => ['method' => 'fixed', 'other_fee_id' => $otherFee->id],
            'is_active' => true,
        ]);
    }

    public function removeItems(Request $request, Application $application)
    {
        $this->abortIfZoningFinalized($application);

        $request->validate([
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'exists:assessment_items,id',
        ]);

        $assessment = Assessment::where('applicationable_type', 'bp')
            ->where('applicationable_id', $application->id)
            ->where('assessment_type', 'zoning')
            ->first();

        if ($assessment) {
            AssessmentItem::whereIn('id', $request->input('item_ids'))
                ->where('assessment_id', $assessment->id)
                ->each(function ($item) {
                    $item->update(['is_active' => false]);
                    $item->delete();
                });

            $total = $assessment->assessmentItems()->where('is_active', true)->sum('amount');
            $assessment->update(['total_amount' => $total]);
        }

        $count = count($request->input('item_ids'));
        return back()->with('success', "Removed {$count} fee item(s).");
    }

    public function removeItem(AssessmentItem $assessmentItem)
    {
        $assessment = $assessmentItem->assessment;
        if ($assessment->status === 'finalized') {
            abort(403, 'Zoning assessment is finalized and cannot be modified.');
        }
        $assessmentItem->update(['is_active' => false]);
        $assessmentItem->delete();

        $total = $assessment->assessmentItems()->where('is_active', true)->sum('amount');
        $assessment->update(['total_amount' => $total]);

        return back()->with('success', 'Fee item removed.');
    }

    public function finalize(Request $request, Application $application)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

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
