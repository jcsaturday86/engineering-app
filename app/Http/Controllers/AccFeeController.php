<?php

namespace App\Http\Controllers;

use App\Models\FeeSchedule;
use App\Models\FeeType;
use Illuminate\Http\Request;

class AccFeeController extends Controller
{
    private const SECTIONS = [
        'line_grade' => [
            'label' => 'Line & Grade',
            'icon'  => 'fa-ruler',
            'color' => 'blue',
            'codes' => ['ASS_LINE_GRADE'],
        ],
        'ground_prep' => [
            'label' => 'Ground Preparation & Excavation',
            'icon'  => 'fa-hard-hat',
            'color' => 'amber',
            'codes' => [
                'ASS_GP_INSPECT', 'ASS_GP_EXCAV', 'ASS_GP_ISSUANCE',
                'ASS_GP_FOUND', 'ASS_GP_OTHER', 'ASS_GP_ENCROACH',
            ],
        ],
        'fencing' => [
            'label' => 'Fencing',
            'icon'  => 'fa-border-all',
            'color' => 'green',
            'codes' => ['ASS_FENCE_MASONRY', 'ASS_FENCE_INDIG'],
        ],
        'pavement' => [
            'label' => 'Pavement, Streets & Scaffolding',
            'icon'  => 'fa-road',
            'color' => 'slate',
            'codes' => ['ASS_PAVEMENT', 'ASS_SIDEWALK', 'ASS_SCAFFOLD'],
        ],
        'signs' => [
            'label' => 'Signs',
            'icon'  => 'fa-sign',
            'color' => 'indigo',
            'codes' => ['ASS_SIGN_ERECT', 'ASS_SIGN_INSTALL', 'ASS_SIGN_RENEW'],
        ],
        'repairs' => [
            'label' => 'Repairs & Renovations',
            'icon'  => 'fa-tools',
            'color' => 'orange',
            'codes' => ['ASS_REPAIR_VERT', 'ASS_REPAIR_HORIZ', 'ASS_REPAIR_COST'],
        ],
        'demolition' => [
            'label' => 'Demolition',
            'icon'  => 'fa-hammer',
            'color' => 'red',
            'codes' => [
                'ASS_DEMO_BLDG', 'ASS_DEMO_FRAME', 'ASS_DEMO_MOVE',
                'ASS_DEMO_STRUCT', 'ASS_DEMO_APPEND',
            ],
        ],
    ];

    public function index()
    {
        $feeTypes = FeeType::whereHas('feeCategory', fn ($q) => $q->where('code', 'ACC_FEE'))
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

        return view('settings.acc-fees', compact('sections'));
    }

    public function updateSchedule(Request $request, FeeSchedule $feeSchedule)
    {
        $data = $request->validate([
            'range_from'       => 'nullable|numeric|min:0',
            'range_to'         => 'nullable|numeric|min:0',
            'fixed_fee'        => 'nullable|numeric|min:0',
            'fee_per_unit'     => 'nullable|numeric|min:0',
            'percentage'       => 'nullable|numeric|min:0',
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
            'percentage'       => 'nullable|numeric|min:0',
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
