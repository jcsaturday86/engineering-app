<?php

namespace App\Http\Controllers;

use App\Models\FeeSchedule;
use App\Models\FeeType;
use Illuminate\Http\Request;

class AccessoryFeeController extends Controller
{
    private const SECTIONS = [
        'general' => [
            'label' => 'General',
            'icon'  => 'fa-building',
            'color' => 'blue',
            'codes' => ['ACC_OPEN_PARTS', 'ACC_HEIGHT', 'ACC_VAULT', 'ACC_FIREWALL'],
        ],
        'pools' => [
            'label' => 'Swimming Pools',
            'icon'  => 'fa-swimming-pool',
            'color' => 'cyan',
            'codes' => [
                'ACC_POOL_RES', 'ACC_POOL_COM', 'ACC_POOL_SOC', 'ACC_POOL_INDIG',
                'ACC_POOL_SHR_RES', 'ACC_POOL_SHR_COM', 'ACC_POOL_SHR_SOC',
            ],
        ],
        'towers' => [
            'label' => 'Towers & Structures',
            'icon'  => 'fa-broadcast-tower',
            'color' => 'indigo',
            'codes' => [
                'ACC_TOWER_RES', 'ACC_TOWER_COM_SS', 'ACC_TOWER_COM_TG',
                'ACC_TOWER_EDU_SS', 'ACC_TOWER_EDU_TG',
            ],
        ],
        'industrial' => [
            'label' => 'Industrial',
            'icon'  => 'fa-industry',
            'color' => 'orange',
            'codes' => ['ACC_SILO', 'ACC_SMOKESTACK', 'ACC_CHIMNEY', 'ACC_OVEN', 'ACC_KILN'],
        ],
        'tanks' => [
            'label' => 'Tanks & Storage',
            'icon'  => 'fa-database',
            'color' => 'teal',
            'codes' => [
                'ACC_RC_TANK_AG', 'ACC_RC_TANK_UG', 'ACC_WATER_TREAT',
                'ACC_TANK_AG_SM', 'ACC_TANK_AG_LG',
                'ACC_PULL_UG', 'ACC_PULL_SADDLE', 'ACC_REINST_SM', 'ACC_REINST_LG',
            ],
        ],
        'booths' => [
            'label' => 'Booths & Kiosks',
            'icon'  => 'fa-store',
            'color' => 'purple',
            'codes' => ['ACC_BOOTH_PERM', 'ACC_BOOTH_TEMP', 'ACC_BOOTH_KNOCK'],
        ],
        'cemetery' => [
            'label' => 'Cemetery Structures',
            'icon'  => 'fa-cross',
            'color' => 'gray',
            'codes' => [
                'ACC_CEM_TOMB', 'ACC_CEM_SEMI', 'ACC_CEM_ENCLOSED',
                'ACC_CEM_MULTI', 'ACC_CEM_COLUMB',
            ],
        ],
    ];

    public function index()
    {
        $feeTypes = FeeType::whereHas('feeCategory', fn ($q) => $q->where('code', 'ACC_BLDG'))
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

        return view('settings.accessory-fees', compact('sections'));
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
