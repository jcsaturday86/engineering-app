<?php

namespace App\Http\Controllers;

use App\Models\FeeSchedule;
use App\Models\FeeType;
use Illuminate\Http\Request;

class SurchargeFeeController extends Controller
{
    private const SECTIONS = [
        'violations' => [
            'label' => 'Violation Surcharges',
            'icon'  => 'fa-exclamation-triangle',
            'color' => 'red',
            'codes' => ['SURCHARGE_LIGHT', 'SURCHARGE_LESS', 'SURCHARGE_GRAVE'],
        ],
        'construction' => [
            'label' => 'Construction Stage Surcharges',
            'icon'  => 'fa-hard-hat',
            'color' => 'amber',
            'codes' => ['SURCHARGE_EXCAV', 'SURCHARGE_FOUND', 'SURCHARGE_SUPER2', 'SURCHARGE_SUPER'],
        ],
    ];

    public function index()
    {
        $feeTypes = FeeType::whereHas('feeCategory', fn ($q) => $q->where('code', 'SURCHARGE'))
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

        return view('settings.surcharge-fees', compact('sections'));
    }

    public function updateSchedule(Request $request, FeeSchedule $feeSchedule)
    {
        $data = $request->validate([
            'fixed_fee'  => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:1',
        ]);

        $feeSchedule->update(array_map(fn ($v) => $v ?? 0, $data));

        return back()->with('success', 'Rate updated.');
    }

    public function storeSchedule(Request $request, FeeType $feeType)
    {
        $data = $request->validate([
            'fixed_fee'  => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:1',
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
