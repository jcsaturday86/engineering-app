<?php

namespace App\Http\Controllers;

use App\Models\FeeSchedule;
use App\Models\FeeType;
use App\Models\LandUseAndZoningFee;
use App\Models\LandUseAndZoningOtherFee;
use App\Models\OccupancyGroup;
use App\Models\OccupancySubGroup;
use Illuminate\Http\Request;

class ZoningFeeController extends Controller
{
    public function index()
    {
        $groups = OccupancyGroup::with(['subGroups' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $lcSchedules = LandUseAndZoningFee::where('is_active', true)
            ->orderBy('occupancy_sub_group_id')
            ->orderBy('range_from')
            ->get()
            ->groupBy('occupancy_sub_group_id');

        $otherFees = LandUseAndZoningOtherFee::where('is_active', true)->get();

        $certFeeType = FeeType::where('code', 'ZONING_CERT_FEE')->first();
        $certRows = collect();
        if ($certFeeType) {
            $certByCode = FeeSchedule::where('fee_type_id', $certFeeType->id)->where('is_active', true)->get()->keyBy('formula');
            $certRows = $groups->map(fn ($g) => (object) ['group' => $g, 'schedule' => $certByCode->get($g->code)])
                ->filter(fn ($row) => $row->schedule !== null)
                ->values();
        }

        return view('settings.zoning-fees', compact('groups', 'lcSchedules', 'otherFees', 'certRows'));
    }

    public function update(Request $request, LandUseAndZoningFee $landUseAndZoningFee)
    {
        $validated = $request->validate([
            'range_from' => 'required|numeric|min:0',
            'range_to' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'excess_of' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0',
        ]);

        $landUseAndZoningFee->update([
            'range_from' => $validated['range_from'],
            'range_to' => $validated['range_to'],
            'amount' => $validated['amount'],
            'excess_of' => $validated['excess_of'] ?? 0,
            'percentage' => $validated['percentage'] ?? 0,
        ]);

        return back()->with('success', 'Fee schedule updated.');
    }

    public function store(Request $request, OccupancySubGroup $occupancySubGroup)
    {
        $validated = $request->validate([
            'range_from' => 'required|numeric|min:0',
            'range_to' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'excess_of' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0',
        ]);

        LandUseAndZoningFee::create([
            'occupancy_sub_group_id' => $occupancySubGroup->id,
            'range_from' => $validated['range_from'],
            'range_to' => $validated['range_to'],
            'amount' => $validated['amount'],
            'excess_of' => $validated['excess_of'] ?? 0,
            'percentage' => $validated['percentage'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Fee schedule row added.');
    }

    public function destroy(LandUseAndZoningFee $landUseAndZoningFee)
    {
        $landUseAndZoningFee->delete();

        return back()->with('success', 'Fee schedule row deleted.');
    }

    public function updateOther(Request $request, LandUseAndZoningOtherFee $landUseAndZoningOtherFee)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $landUseAndZoningOtherFee->update(['amount' => $validated['amount']]);

        return back()->with('success', 'Other zoning fee updated.');
    }

    public function updateCert(Request $request, FeeSchedule $feeSchedule)
    {
        abort_unless($feeSchedule->feeType?->code === 'ZONING_CERT_FEE', 403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $feeSchedule->update(['fixed_fee' => $validated['amount']]);

        return back()->with('success', 'Certification fee updated.');
    }
}
