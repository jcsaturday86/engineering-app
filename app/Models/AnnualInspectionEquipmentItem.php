<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualInspectionEquipmentItem extends Model
{
    protected $fillable = [
        'annual_inspection_application_id',
        'fee_code',
        'quantity',
        'specification',
        'sort_order',
    ];

    // Equipment-count fee codes from the Annual Inspection fee schedule (Electronics/Mechanical
    // tabs), grouped for the application form's "Equipment / Items to be Inspected" checklist.
    // Excludes General-tab measurement/service codes (Floor Area, Building Inspection, etc.) and
    // Electrical-tab capacity-based codes (TCL/Transformer/UPS/Pole) — those aren't equipment counts.
    public const CATEGORIES = [
        'Elevators' => [
            'AINSP_FVI_PASS' => 'Passenger Elevator',
            'AINSP_FVI_FRT' => 'Freight Elevator',
            'AINSP_FVI_DUMB' => 'Motor Driven Dumbwaiter',
            'AINSP_FVI_CONST' => 'Construction Elevator for Materials',
            'AINSP_FVI_CAR' => 'Car Elevator',
        ],
        'Escalators, Funiculars & Cable Cars' => [
            'AINSP_FV_ESC' => 'Escalator/Moving Walk',
            'AINSP_FV_FUNIC' => 'Funicular (by kW)',
            'AINSP_FV_FUNIC_LM' => 'Funicular (per lineal meter)',
            'AINSP_FV_CABLE' => 'Cable Car (by kW)',
            'AINSP_FV_CABLE_LM' => 'Cable Car (per lineal meter)',
        ],
        'Air Conditioning & Refrigeration' => [
            'AINSP_FI_REFRIG' => 'Refrigeration/AC (by ton)',
            'AINSP_FII_WINAC' => 'Window Type AC',
            'AINSP_FIII_CENAC' => 'Centralized AC (by ton)',
        ],
        'Other Machinery' => [
            'AINSP_FVII_BOILER' => 'Boiler',
            'AINSP_FVIII_WHT' => 'Pressurized Water Heater',
            'AINSP_FIX_FIRE' => 'Fire Extinguisher (per sprinkler head)',
            'AINSP_FX_DIESEL' => 'Diesel/Gasoline Generating Unit',
            'AINSP_FXI_INTCOMB' => 'Internal Combustion Engine',
            'AINSP_FXII_COMP' => 'Compressed Air/Gases (per outlet)',
            'AINSP_FXIII_PIPE' => 'Power Piping',
            'AINSP_PUMP_WSS' => 'Water/Sump/Sewage Pump',
            'AINSP_FXV_PUMP' => 'Other Machinery',
            'AINSP_FXVI_PRESS' => 'Pressure Vessel',
            'AINSP_FXVII_PNEU' => 'Pneumatic Tube/Conveyor',
            'AINSP_FXVIII_WEIGH' => 'Weighing Scale',
            'AINSP_FXIX_CALIB' => 'Pressure Gauge (for calibration)',
            'AINSP_FXIX_GASM' => 'Gas Meter',
            'AINSP_FXX_RIDE' => 'Mechanical Ride',
        ],
        'Electronics Equipment' => [
            'AINSP_ELEC_SWITCH' => 'Switching/Communications Equipment',
            'AINSP_ELEC_BCAST' => 'Broadcast Station/Cell Site',
            'AINSP_ELEC_ATM' => 'ATM/Vending/Medical Equipment',
            'AINSP_ELEC_OUTLET' => 'Communications Outlet',
            'AINSP_ELEC_SECUR' => 'Security/Alarm System',
            'AINSP_ELEC_STUDIO' => 'Studio/Auditorium',
            'AINSP_ELEC_TOWER' => 'Antenna Tower/Mast',
            'AINSP_ELEC_SIGN' => 'Electronic Signage',
            'AINSP_ELEC_POLE' => 'Pole',
            'AINSP_ELEC_ATTACH' => 'Attachment',
            'AINSP_ELEC_OTHER' => 'Other Electronics Device',
        ],
    ];

    public static function labelFor(string $feeCode): string
    {
        foreach (self::CATEGORIES as $group) {
            if (isset($group[$feeCode])) {
                return $group[$feeCode];
            }
        }

        return $feeCode;
    }

    public static function allCodes(): array
    {
        return collect(self::CATEGORIES)->flatMap(fn ($group) => array_keys($group))->all();
    }

    public function annualInspectionApplication(): BelongsTo
    {
        return $this->belongsTo(AnnualInspectionApplication::class);
    }
}
