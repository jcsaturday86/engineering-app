<?php

namespace App\Http\Controllers;

use App\Models\FeeSchedule;
use App\Models\FeeType;
use Illuminate\Http\Request;

class ElectronicsFeeController extends Controller
{
    private const SECTIONS = [
        'comms' => [
            'label' => 'Communications & Broadcasting',
            'icon'  => 'fa-broadcast-tower',
            'color' => 'blue',
            'codes' => ['ELECT_SWITCH', 'ELECT_BROADCAST', 'ELECT_STUDIO', 'ELECT_TOWER'],
        ],
        'devices' => [
            'label' => 'Devices, Outlets & Systems',
            'icon'  => 'fa-microchip',
            'color' => 'indigo',
            'codes' => ['ELECT_ATM', 'ELECT_OUTLET', 'ELECT_SECURITY', 'ELECT_SIGNAGE'],
        ],
        'pole' => [
            'label' => 'Pole & Attachment Fees',
            'icon'  => 'fa-arrows-alt-v',
            'color' => 'teal',
            'codes' => ['ELECT_POLE', 'ELECT_ATTACH'],
        ],
        'other' => [
            'label' => 'Other Electronics',
            'icon'  => 'fa-cog',
            'color' => 'gray',
            'codes' => ['ELECT_OTHER'],
        ],
    ];

    public function index()
    {
        $feeTypes = FeeType::whereHas('feeCategory', fn ($q) => $q->where('code', 'ELECT'))
            ->with(['feeSchedules' => fn ($q) => $q->orderBy('range_from')->orderBy('id')])
            ->orderBy('sort_order')
            ->get()
            ->keyBy('code');

        $sections = collect(self::SECTIONS)->map(function ($section) use ($feeTypes) {
            $section['types'] = collect($section['codes'])
                ->map(fn ($code) => $feeTypes->get($code))
                ->filter()
                ->values();
            return $section;
        });

        return view('settings.electronics-fees', compact('sections'));
    }

    public function updateSchedule(Request $request, FeeSchedule $feeSchedule)
    {
        $data = $request->validate([
            'fixed_fee'    => 'nullable|numeric|min:0',
            'fee_per_unit' => 'nullable|numeric|min:0',
        ]);

        $feeSchedule->update(array_map(fn ($v) => $v ?? 0, $data));

        return back()->with('success', 'Rate updated.');
    }

    public function storeSchedule(Request $request, FeeType $feeType)
    {
        $data = $request->validate([
            'fixed_fee'    => 'nullable|numeric|min:0',
            'fee_per_unit' => 'nullable|numeric|min:0',
        ]);

        FeeSchedule::create(array_merge(
            ['fee_type_id' => $feeType->id, 'is_active' => true],
            array_map(fn ($v) => $v ?? 0, $data)
        ));

        return back()->with('success', 'Row added.');
    }

    public function destroySchedule(FeeSchedule $feeSchedule)
    {
        $feeSchedule->delete();
        return back()->with('success', 'Row deleted.');
    }
}
