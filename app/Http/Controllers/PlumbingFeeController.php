<?php

namespace App\Http\Controllers;

use App\Models\FeeSchedule;
use App\Models\FeeType;
use Illuminate\Http\Request;

class PlumbingFeeController extends Controller
{
    private const SECTIONS = [
        'install' => [
            'label' => 'Installation Fee',
            'icon'  => 'fa-wrench',
            'color' => 'blue',
            'codes' => ['PLUMB_INSTALL'],
        ],
        'fixture' => [
            'label' => 'Every Fixture Fees',
            'icon'  => 'fa-faucet',
            'color' => 'teal',
            'codes' => ['PLUMB_FIX_WC', 'PLUMB_FIX_FD', 'PLUMB_FIX_SINK', 'PLUMB_FIX_LAV', 'PLUMB_FIX_FAUCET', 'PLUMB_FIX_SHOWER'],
        ],
        'special' => [
            'label' => 'Special Plumbing Fixtures',
            'icon'  => 'fa-toilet',
            'color' => 'indigo',
            'codes' => [
                'PLUMB_SP_SLOP', 'PLUMB_SP_URINAL', 'PLUMB_SP_BATH', 'PLUMB_SP_GREASE',
                'PLUMB_SP_GARAGE', 'PLUMB_SP_BIDET', 'PLUMB_SP_DENTAL', 'PLUMB_SP_GWH',
                'PLUMB_SP_DRINK', 'PLUMB_SP_BAR', 'PLUMB_SP_LAUNDRY', 'PLUMB_SP_LAB', 'PLUMB_SP_STERIL',
            ],
        ],
        'range' => [
            'label' => 'Range-Based Fees',
            'icon'  => 'fa-ruler',
            'color' => 'orange',
            'codes' => ['PLUMB_WATER_METER', 'PLUMB_SEPTIC'],
        ],
    ];

    public function index()
    {
        $feeTypes = FeeType::whereHas('feeCategory', fn ($q) => $q->where('code', 'PLUMB'))
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

        return view('settings.plumbing-fees', compact('sections'));
    }

    public function updateSchedule(Request $request, FeeSchedule $feeSchedule)
    {
        $data = $request->validate([
            'range_from'       => 'nullable|numeric|min:0',
            'range_to'         => 'nullable|numeric|min:0',
            'fixed_fee'        => 'nullable|numeric|min:0',
            'fee_per_unit'     => 'nullable|numeric|min:0',
            'excess_threshold' => 'nullable|numeric|min:0',
            'excess_fee'       => 'nullable|numeric|min:0',
            'excess_every'     => 'nullable|numeric|min:0.01',
        ]);

        $feeSchedule->update(array_map(fn ($v) => $v ?? 0, $data));

        return back()->with('success', 'Rate updated.');
    }

    public function storeSchedule(Request $request, FeeType $feeType)
    {
        $data = $request->validate([
            'range_from'       => 'nullable|numeric|min:0',
            'range_to'         => 'nullable|numeric|min:0',
            'fixed_fee'        => 'nullable|numeric|min:0',
            'fee_per_unit'     => 'nullable|numeric|min:0',
            'excess_threshold' => 'nullable|numeric|min:0',
            'excess_fee'       => 'nullable|numeric|min:0',
            'excess_every'     => 'nullable|numeric|min:0.01',
        ]);

        FeeSchedule::create(array_merge(
            ['fee_type_id' => $feeType->id, 'is_active' => true, 'excess_every' => 1],
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
