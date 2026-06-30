<?php

namespace App\Http\Controllers;

use App\Models\FeeSchedule;
use App\Models\FeeType;
use Illuminate\Http\Request;

class MechInspFeeController extends Controller
{
    private const SECTIONS = [
        'A' => [
            'label' => 'A — Refrigeration, Air-Conditioning & Ventilation',
            'icon'  => 'fa-snowflake',
            'color' => 'blue',
            'codes' => ['INSP_REFRIG', 'INSP_ICE', 'INSP_CENTRAL_AC', 'INSP_WINDOW_AC', 'INSP_VENT'],
        ],
        'B' => [
            'label' => 'B — Escalators, Funiculars & Cable Cars',
            'icon'  => 'fa-stream',
            'color' => 'purple',
            'codes' => ['INSP_ESC_KW', 'INSP_ESC_RANGE', 'INSP_FUNIC_KW', 'INSP_FUNIC_LM', 'INSP_CABLE_KW', 'INSP_CABLE_LM'],
        ],
        'C' => [
            'label' => 'C — Elevators',
            'icon'  => 'fa-arrows-alt-v',
            'color' => 'indigo',
            'codes' => ['INSP_ELEV_PASS', 'INSP_ELEV_FRT', 'INSP_ELEV_DUMB', 'INSP_ELEV_CONST', 'INSP_ELEV_CAR'],
        ],
        'D' => [
            'label' => 'D — Boilers',
            'icon'  => 'fa-fire',
            'color' => 'red',
            'codes' => ['INSP_BOILER'],
        ],
        'H' => [
            'label' => 'H — Diesel / Gasoline Engines',
            'icon'  => 'fa-gas-pump',
            'color' => 'yellow',
            'codes' => ['INSP_DIESEL'],
        ],
        'L' => [
            'label' => 'L — Other Internal Combustion Engines',
            'icon'  => 'fa-cog',
            'color' => 'orange',
            'codes' => ['INSP_INT_COMB'],
        ],
        'O' => [
            'label' => 'O — Other Mechanical Equipment',
            'icon'  => 'fa-cogs',
            'color' => 'gray',
            'codes' => [
                'INSP_WATER_HEATER', 'INSP_WATER_PUMP', 'INSP_SPRINKLER',
                'INSP_COMPRESSED', 'INSP_GAS_METER', 'INSP_POWER_PIPE',
                'INSP_PRESSURE_V', 'INSP_OTHER_EQUIP', 'INSP_PNEUMATIC', 'INSP_WEIGH_SCALE',
            ],
        ],
    ];

    public function index()
    {
        $feeTypes = FeeType::whereHas('feeCategory', fn ($q) => $q->where('code', 'MECH_INSP'))
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

        return view('settings.mech-insp-fees', compact('sections'));
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
