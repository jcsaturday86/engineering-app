<?php

namespace App\Http\Controllers;

use App\Models\CertificationZoningFee;
use App\Models\LandUseAndZoningFee;
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

        $certFee = CertificationZoningFee::where('is_active', true)->first();

        return view('settings.zoning-fees', compact('groups', 'lcSchedules', 'certFee'));
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

    public function updateCert(Request $request, CertificationZoningFee $certificationZoningFee)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $certificationZoningFee->update(['amount' => $validated['amount']]);

        return back()->with('success', 'Certification fee updated.');
    }

    public function destroy(LandUseAndZoningFee $landUseAndZoningFee)
    {
        $landUseAndZoningFee->delete();

        return back()->with('success', 'Fee schedule row deleted.');
    }
}
