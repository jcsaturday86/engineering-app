<?php

namespace App\Http\Controllers;

use App\Models\FeeSchedule;
use App\Models\FeeType;
use Illuminate\Http\Request;

class DemolitionFeeController extends Controller
{
    private const SECTIONS = [
        'demolition' => [
            'label' => 'Demolition/Moving of Building/Structures Fees',
            'icon'  => 'fa-hammer',
            'color' => 'red',
            'codes' => [
                'DEMO_FLOOR_AREA', 'DEMO_MECH_EQUIP', 'DEMO_HAND_INCL_FLOORS',
                'DEMO_HAND_EXCL_FLOORS', 'DEMO_APPENDAGE', 'DEMO_MOVING',
            ],
        ],
    ];

    public function index()
    {
        $feeTypes = FeeType::whereHas('feeCategory', fn ($q) => $q->where('code', 'DEMO_FEE'))
            ->with(['feeSchedules' => fn ($q) => $q->orderBy('id')])
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

        return view('settings.demolition-fees', compact('sections'));
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
