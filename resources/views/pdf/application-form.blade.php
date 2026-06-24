@php
    $selectedOccupancy = $application->applicationOccupancyGroups->pluck('occupancy_sub_group_id')->toArray();
    $othersText = $application->applicationOccupancyGroups->pluck('others_text', 'occupancy_sub_group_id')->toArray();
    $scopeId = $application->scope_of_work_id;
    $scopeDetails = $application->scope_of_work_details;

    $scopeNames = [
        1 => 'New Construction', 2 => 'Addition', 3 => 'Renovation', 4 => 'Alteration',
        5 => 'Conversion', 6 => 'Repair', 7 => 'Raising', 8 => 'Moving',
        9 => 'Demolition', 10 => 'Accessory Building/Structure', 11 => 'Erection',
        12 => 'Legalization', 13 => 'Others (Specify)',
    ];

    $ck = function($id) use ($selectedOccupancy) {
        return in_array($id, $selectedOccupancy) ? '&#9745;' : '&#9744;';
    };

    $ot = function($id) use ($othersText) {
        return $othersText[$id] ?? '';
    };

    $sk = function($id) use ($scopeId) {
        return $scopeId == $id ? '&#9745;' : '&#9744;';
    };

    $sd = function($id) use ($scopeId, $scopeDetails) {
        return $scopeId == $id && $scopeDetails ? $scopeDetails : '';
    };

    $blank = '_____';
    $mi = $application->applicant_middle_name ? mb_substr($application->applicant_middle_name, 0, 1) . '.' : '';

    $complexity = $application->complexity ?? '';
    $appTypeId = $application->application_type_id;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: legal portrait; margin: 10mm 12mm; }
        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            color: #000;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; padding: 1px 3px; }

        .header-table td { border: none; padding: 1px 2px; }
        .header-center { text-align: center; }
        .header-center .title { font-size: 11px; font-weight: bold; margin-top: 2px; }
        .header-center div { font-size: 9px; }

        .box { border: 1px solid #000; margin-bottom: 3px; }
        .box-title {
            font-weight: bold;
            font-size: 8px;
            background: #e0e0e0;
            padding: 2px 4px;
            border-bottom: 1px solid #000;
        }
        .form-table { width: 100%; }
        .form-table td { border: 1px solid #000; padding: 2px 3px; font-size: 8px; }
        .form-table .label { font-weight: bold; font-size: 7px; text-transform: uppercase; }

        .no-border td { border: none; }
        .checkbox-grid td { border: none; padding: 1px 2px; font-size: 7.5px; }

        .occupancy-table { width: 100%; }
        .occupancy-table td { border: 1px solid #000; padding: 2px 3px; font-size: 7px; vertical-align: top; }
        .occupancy-table .group-header { font-weight: bold; font-size: 7.5px; }

        .sig-table td { border: 1px solid #000; padding: 3px 5px; font-size: 8px; }
        .sig-line { border-bottom: 1px solid #000; min-height: 25px; margin-bottom: 2px; text-align: center; }
        .sig-label { font-size: 7px; text-align: center; }

        .notary-box { font-size: 7.5px; line-height: 1.4; padding: 4px 6px; }

        .footer { font-size: 7px; text-align: center; margin-top: 3px; }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .underline { text-decoration: underline; }
        .val { font-size: 8px; }
    </style>
</head>
<body>

{{-- ==================== HEADER ==================== --}}
<table class="header-table" style="width:100%;">
    <tr>
        <td style="width:20%;"></td>
        <td style="width:60%;" class="header-center">
            <div>Republic of the Philippines</div>
            <div><b>City of San Fernando</b></div>
            <div>Province of La Union</div>
            <div class="title" style="margin-top:4px;">UNIFIED APPLICATION FORM FOR BUILDING PERMIT</div>
        </td>
        <td style="width:20%; text-align:right; font-size:8px; vertical-align:top;">
            <div style="margin-bottom:3px;">
                <b>Complexity:</b><br>
                {!! $complexity === 'simple' ? '&#9745;' : '&#9744;' !!} Simple<br>
                {!! $complexity === 'complex' ? '&#9745;' : '&#9744;' !!} Complex
            </div>
        </td>
    </tr>
</table>

{{-- Application Type / Applies To --}}
<table class="no-border" style="width:100%; margin-bottom:2px;">
    <tr>
        <td style="font-size:8px;">
            <b>Application Type:</b>&nbsp;
            {!! $appTypeId == 1 ? '&#9745;' : '&#9744;' !!} NEW &nbsp;&nbsp;
            {!! $appTypeId == 2 ? '&#9745;' : '&#9744;' !!} RENEWAL &nbsp;&nbsp;
            {!! $appTypeId == 3 ? '&#9745;' : '&#9744;' !!} AMENDATORY
        </td>
    </tr>
    <tr>
        <td style="font-size:8px;">
            <b>THIS APPLIES ALSO FOR:</b>&nbsp;
            @php $appliesTo = $application->applies_to ?? ''; @endphp
            {!! str_contains($appliesTo, 'locational') ? '&#9745;' : '&#9744;' !!} LOCATIONAL CLEARANCE &nbsp;&nbsp;
            {!! str_contains($appliesTo, 'fire') ? '&#9745;' : '&#9744;' !!} FIRE SAFETY EVALUATION CLEARANCE
        </td>
    </tr>
    <tr>
        <td style="font-size:8px;">
            <table class="no-border" style="width:100%;">
                <tr>
                    <td><b>APPLICATION NO.:</b> <span class="val underline">&nbsp;{{ $application->application_number }}&nbsp;</span></td>
                    <td class="right"><b>AREA NO.:</b> <span class="val underline">&nbsp;{{ $application->area_number ?? $blank }}&nbsp;</span></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ==================== BOX 1 ==================== --}}
<div class="box">
    <div class="box-title">BOX 1 &mdash; TO BE ACCOMPLISHED IN PRINT BY THE APPLICANT</div>

    {{-- Owner/Applicant --}}
    <table class="form-table">
        <tr>
            <td class="label" style="width:7%;">LAST NAME</td>
            <td style="width:23%;" class="val">{{ $application->applicant_last_name }}</td>
            <td class="label" style="width:8%;">FIRST NAME</td>
            <td style="width:22%;" class="val">{{ $application->applicant_first_name }}</td>
            <td class="label" style="width:4%;">M.I.</td>
            <td style="width:8%;" class="val">{{ $mi }}</td>
            <td class="label" style="width:4%;">TIN</td>
            <td style="width:24%;" class="val">{{ $application->applicant_tin ?? $blank }}</td>
        </tr>
    </table>

    {{-- Enterprise --}}
    <table class="form-table">
        <tr>
            <td class="label" style="width:35%;">FOR CONSTRUCTION OWNED BY AN ENTERPRISE</td>
            <td style="width:30%;" class="val">{{ $application->enterprise_name ?? $blank }}</td>
            <td class="label" style="width:15%;">FORM OF OWNERSHIP</td>
            <td style="width:20%;" class="val">{{ $application->formOfOwnership?->name ?? $blank }}</td>
        </tr>
    </table>

    {{-- Address --}}
    <table class="form-table">
        <tr>
            <td class="label" style="width:7%;">ADDRESS</td>
            <td style="width:53%;" class="val">{{ $application->applicant_street }}, {{ $application->applicantBarangay?->name }}, {{ $application->applicantCity?->name }}</td>
            <td class="label" style="width:8%;">ZIP CODE</td>
            <td style="width:10%;" class="val">{{ $application->applicant_zip_code ?? $blank }}</td>
            <td class="label" style="width:9%;">CONTACT NO.</td>
            <td style="width:13%;" class="val">{{ $application->applicant_contact_no ?? $blank }}</td>
        </tr>
    </table>

    {{-- Location of Construction --}}
    <table class="form-table">
        <tr>
            <td colspan="8" class="label" style="background:#f0f0f0;">LOCATION OF CONSTRUCTION</td>
        </tr>
        <tr>
            <td class="label" style="width:7%;">LOT NO.</td>
            <td style="width:13%;" class="val">{{ $application->lot_no ?? $blank }}</td>
            <td class="label" style="width:7%;">BLK NO.</td>
            <td style="width:13%;" class="val">{{ $application->block_no ?? $blank }}</td>
            <td class="label" style="width:7%;">TCT NO.</td>
            <td style="width:13%;" class="val">{{ $application->tct_no ?? $blank }}</td>
            <td class="label" style="width:13%;">CURRENT TAX DEC. NO.</td>
            <td style="width:27%;" class="val">{{ $application->tax_dec_no ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label" style="width:7%;">STREET</td>
            <td colspan="3" class="val">{{ $application->building_street ?? $blank }}</td>
            <td class="label" style="width:7%;">BARANGAY</td>
            <td class="val">{{ $application->buildingBarangay?->name ?? $blank }}</td>
            <td class="label" style="width:13%;">CITY/MUNICIPALITY OF</td>
            <td class="val">San Fernando</td>
        </tr>
    </table>

    {{-- Scope of Work --}}
    <table class="form-table">
        <tr>
            <td colspan="6" class="label" style="background:#f0f0f0;">SCOPE OF WORK</td>
        </tr>
    </table>
    <table style="width:100%; border-left:1px solid #000; border-right:1px solid #000;">
        <tr>
            <td class="checkbox-grid" style="width:33%;">{!! $sk(1) !!} NEW CONSTRUCTION</td>
            <td class="checkbox-grid" style="width:33%;">{!! $sk(3) !!} RENOVATION {{ $sd(3) }}</td>
            <td class="checkbox-grid" style="width:34%;">{!! $sk(7) !!} RAISING {{ $sd(7) }}</td>
        </tr>
        <tr>
            <td class="checkbox-grid">{!! $sk(11) !!} ERECTION {{ $sd(11) }}</td>
            <td class="checkbox-grid">{!! $sk(5) !!} CONVERSION {{ $sd(5) }}</td>
            <td class="checkbox-grid">{!! $sk(10) !!} ACCESSORY BUILDING/STRUCTURE {{ $sd(10) }}</td>
        </tr>
        <tr>
            <td class="checkbox-grid">{!! $sk(2) !!} ADDITION {{ $sd(2) }}</td>
            <td class="checkbox-grid">{!! $sk(6) !!} REPAIR {{ $sd(6) }}</td>
            <td class="checkbox-grid">{!! $sk(12) !!} LEGALIZATION OF EXISTING BUILDING {{ $sd(12) }}</td>
        </tr>
        <tr>
            <td class="checkbox-grid">{!! $sk(4) !!} ALTERATION {{ $sd(4) }}</td>
            <td class="checkbox-grid">{!! $sk(8) !!} MOVING {{ $sd(8) }}</td>
            <td class="checkbox-grid" style="border-bottom:1px solid #000;">{!! $sk(13) !!} OTHERS (Specify) {{ $sd(13) }}</td>
        </tr>
    </table>

    {{-- Use or Character of Occupancy --}}
    <table class="form-table">
        <tr>
            <td colspan="3" class="label" style="background:#f0f0f0;">USE OR CHARACTER OF OCCUPANCY</td>
        </tr>
    </table>
    <table class="occupancy-table">
        <tr>
            {{-- Column 1: Groups A, B, C, D --}}
            <td style="width:33%;">
                <div class="group-header">GROUP A: RESIDENTIAL (DWELLINGS)</div>
                <div>{!! $ck(1) !!} SINGLE</div>
                <div>{!! $ck(2) !!} DUPLEX</div>
                <div>{!! $ck(3) !!} RESIDENTIAL R-1, R-2</div>
                <div>{!! $ck(4) !!} OTHERS {{ $ot(4) }}</div>

                <div class="group-header" style="margin-top:3px;">GROUP B: RESIDENTIAL</div>
                <div>{!! $ck(5) !!} HOTEL</div>
                <div>{!! $ck(6) !!} MOTEL</div>
                <div>{!! $ck(7) !!} TOWNHOUSE</div>
                <div>{!! $ck(8) !!} DORMITORY</div>
                <div>{!! $ck(9) !!} BOARDINGHOUSE, LODGING HOUSE</div>
                <div>{!! $ck(10) !!} RESIDENTIAL R-3, R-4, R-5</div>
                <div>{!! $ck(11) !!} OTHERS {{ $ot(11) }}</div>

                <div class="group-header" style="margin-top:3px;">GROUP C: EDUCATIONAL &amp; RECREATIONAL</div>
                <div>{!! $ck(12) !!} SCHOOL BUILDING</div>
                <div>{!! $ck(13) !!} SCHOOL AUDITORIUM, GYMNASIUM</div>
                <div>{!! $ck(14) !!} CIVIC CENTER</div>
                <div>{!! $ck(15) !!} CLUBHOUSE</div>
                <div>{!! $ck(16) !!} CHURCH, MOSQUE, TEMPLE, CHAPEL</div>
                <div>{!! $ck(17) !!} OTHERS {{ $ot(17) }}</div>

                <div class="group-header" style="margin-top:3px;">GROUP D: INSTITUTIONAL</div>
                <div>{!! $ck(18) !!} HOSPITAL OR SIMILAR STRUCTURE</div>
                <div>{!! $ck(19) !!} HOME FOR THE AGED</div>
                <div>{!! $ck(20) !!} GOVERNMENT OFFICE</div>
                <div>{!! $ck(21) !!} OTHERS {{ $ot(21) }}</div>
            </td>

            {{-- Column 2: Groups E, F, G --}}
            <td style="width:33%;">
                <div class="group-header">GROUP E: COMMERCIAL</div>
                <div>{!! $ck(22) !!} BANK</div>
                <div>{!! $ck(23) !!} STORE</div>
                <div>{!! $ck(24) !!} SHOPPING CENTER/MALL</div>
                <div>{!! $ck(25) !!} DRINKING/DINING ESTABLISHMENT</div>
                <div>{!! $ck(26) !!} SHOP (DRESS SHOP, TAILORING, BARBERSHOP, ETC.)</div>
                <div>{!! $ck(27) !!} OTHERS {{ $ot(27) }}</div>

                <div class="group-header" style="margin-top:3px;">GROUP F: LIGHT INDUSTRIAL</div>
                <div>{!! $ck(28) !!} FACTORY/PLANT (USING INCOMBUSTIBLE/NON-EXPLOSIVE MATERIALS)</div>
                <div>{!! $ck(29) !!} OTHERS {{ $ot(29) }}</div>

                <div class="group-header" style="margin-top:3px;">GROUP G: MEDIUM INDUSTRIAL</div>
                <div>{!! $ck(30) !!} STORAGE/WAREHOUSE (HAZARDOUS)</div>
                <div>{!! $ck(31) !!} FACTORY (HAZARDOUS)</div>
                <div>{!! $ck(32) !!} OTHERS {{ $ot(32) }}</div>
            </td>

            {{-- Column 3: Groups H, I, J --}}
            <td style="width:34%;">
                <div class="group-header">GROUP H: ASSEMBLY (OCCUPANT LOAD LESS THAN 1,000)</div>
                <div>{!! $ck(33) !!} THEATER, AUDITORIUM, CONVENTION HALL, GRANDSTAND/BLEACHER</div>
                <div>{!! $ck(34) !!} OTHERS {{ $ot(34) }}</div>

                <div class="group-header" style="margin-top:3px;">GROUP I: ASSEMBLY (OCCUPANT LOAD 1,000 OR MORE)</div>
                <div>{!! $ck(35) !!} COLISEUM, SPORTS COMPLEX, CONVENTION CENTER</div>
                <div>{!! $ck(36) !!} OTHERS {{ $ot(36) }}</div>

                <div class="group-header" style="margin-top:3px;">GROUP J: (J-1) AGRICULTURAL</div>
                <div>{!! $ck(37) !!} BARN, GRANARY, POULTRY HOUSE, PIGGERY, GRAIN MILL, GRAIN SILO</div>
                <div>{!! $ck(38) !!} OTHERS {{ $ot(38) }}</div>

                <div class="group-header" style="margin-top:3px;">GROUP J: (J-2) ACCESSORIES</div>
                <div>{!! $ck(39) !!} PRIVATE CARPORT/GARAGE, TOWER, SWIMMING POOL, FENCE OVER 1.80m, STEEL/CONCRETE TANK</div>
                <div>{!! $ck(40) !!} OTHERS {{ $ot(40) }}</div>
            </td>
        </tr>
    </table>

    {{-- Building Details & Cost --}}
    <table class="form-table">
        <tr>
            <td colspan="6" class="label" style="background:#f0f0f0;">BUILDING DETAILS &amp; COST</td>
        </tr>
    </table>
    <table class="form-table">
        <tr>
            {{-- Left --}}
            <td style="width:33%; border-right:1px solid #000;" rowspan="5">
                <table class="no-border" style="width:100%;">
                    <tr><td class="label" style="font-size:7px;">OCCUPANCY CLASSIFIED</td><td class="val">{{ $application->occupancy_classified ?? $blank }}</td></tr>
                    <tr><td class="label" style="font-size:7px;">NUMBER OF UNITS</td><td class="val">{{ $application->no_of_units ?? $blank }}</td></tr>
                    <tr><td class="label" style="font-size:7px;">NUMBER OF STOREY</td><td class="val">{{ $application->no_of_storeys ?? $blank }}</td></tr>
                    <tr><td class="label" style="font-size:7px;">TOTAL FLOOR AREA</td><td class="val">{{ $application->total_floor_area ? number_format($application->total_floor_area, 2) . ' SQ.M.' : $blank }}</td></tr>
                    <tr><td class="label" style="font-size:7px;">LOT AREA</td><td class="val">{{ $application->lot_area ? number_format($application->lot_area, 2) . ' SQ.M.' : $blank }}</td></tr>
                </table>
            </td>
            {{-- Center --}}
            <td style="width:34%; border-right:1px solid #000;" rowspan="5">
                <table class="no-border" style="width:100%;">
                    <tr><td class="label bold" style="font-size:7.5px;">TOTAL ESTIMATED COST:</td><td class="val bold">&#8369; {{ number_format($application->total_estimated_cost, 2) }}</td></tr>
                    <tr><td class="label" style="font-size:7px;">BUILDING</td><td class="val">&#8369; {{ number_format($application->building_cost, 2) }}</td></tr>
                    <tr><td class="label" style="font-size:7px;">ELECTRICAL</td><td class="val">&#8369; {{ number_format($application->electrical_cost, 2) }}</td></tr>
                    <tr><td class="label" style="font-size:7px;">MECHANICAL</td><td class="val">&#8369; {{ number_format($application->mechanical_cost, 2) }}</td></tr>
                    <tr><td class="label" style="font-size:7px;">ELECTRONICS</td><td class="val">&#8369; {{ number_format($application->electronics_cost, 2) }}</td></tr>
                    <tr><td class="label" style="font-size:7px;">PLUMBING</td><td class="val">&#8369; {{ number_format($application->plumbing_cost, 2) }}</td></tr>
                </table>
            </td>
            {{-- Right (Equipment Installed) --}}
            <td style="width:33%;" rowspan="5">
                <table class="no-border" style="width:100%;">
                    <tr><td colspan="2" class="label" style="font-size:7px;">COST OF EQUIPMENT INSTALLED</td></tr>
                    <tr><td class="val">&#8369;</td><td class="val">{{ $application->equipment_cost_1 ? number_format($application->equipment_cost_1, 2) : $blank }}</td></tr>
                    <tr><td class="val">&#8369;</td><td class="val">{{ $application->equipment_cost_2 ? number_format($application->equipment_cost_2, 2) : $blank }}</td></tr>
                    <tr><td class="val">&#8369;</td><td class="val">{{ $application->equipment_cost_3 ? number_format($application->equipment_cost_3, 2) : $blank }}</td></tr>
                    <tr><td class="val">&#8369;</td><td class="val">{{ $application->equipment_cost_4 ? number_format($application->equipment_cost_4, 2) : $blank }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Proposed / Expected dates --}}
    <table class="form-table">
        <tr>
            <td class="label" style="width:25%;">PROPOSED DATE OF CONSTRUCTION</td>
            <td style="width:25%;" class="val">{{ $application->proposed_construction_date?->format('F d, Y') ?? $blank }}</td>
            <td class="label" style="width:25%;">EXPECTED DATE OF COMPLETION</td>
            <td style="width:25%;" class="val">{{ $application->expected_completion_date?->format('F d, Y') ?? $blank }}</td>
        </tr>
    </table>
</div>

{{-- ==================== BOX 2 ==================== --}}
<div class="box">
    <div class="box-title">BOX 2 &mdash; FULL-TIME INSPECTOR AND SUPERVISOR OF CONSTRUCTION WORK</div>
    <table class="form-table">
        <tr>
            <td colspan="4" class="label" style="font-size:7px;">ARCHITECT OR CIVIL ENGINEER (Signed and Sealed Over Printed Name)</td>
        </tr>
        <tr>
            <td colspan="4" style="min-height:20px;">
                <div style="min-height:18px; border-bottom:1px solid #000; text-align:center; font-size:9px; font-weight:bold; padding-top:10px;">
                    {{ $application->engineer_name ?? '' }}
                </div>
            </td>
        </tr>
        <tr>
            <td class="label" style="width:10%;">Date</td>
            <td style="width:40%;" class="val">{{ $application->engineer_date_signed?->format('F d, Y') ?? $blank }}</td>
            <td class="label" style="width:10%;">Address</td>
            <td style="width:40%;" class="val">{{ $application->engineer_address ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label" style="width:10%;">PRC No.</td>
            <td style="width:15%;" class="val">{{ $application->engineer_prc_no ?? $blank }}</td>
            <td class="label" style="width:10%;">Validity</td>
            <td style="width:65%;" class="val">{{ $application->engineer_prc_validity?->format('F d, Y') ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label" style="width:10%;">PTR No.</td>
            <td style="width:15%;" class="val">{{ $application->engineer_ptr_no ?? $blank }}</td>
            <td class="label" style="width:10%;">Date Issued</td>
            <td style="width:65%;" class="val">{{ $application->engineer_ptr_date_issued?->format('F d, Y') ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label" style="width:10%;">Issued at</td>
            <td style="width:40%;" class="val">{{ $application->engineer_ptr_issued_at ?? $blank }}</td>
            <td class="label" style="width:10%;">TIN</td>
            <td style="width:40%;" class="val">{{ $application->engineer_tin ?? $blank }}</td>
        </tr>
    </table>
</div>

{{-- ==================== BOX 3 ==================== --}}
<div class="box">
    <div class="box-title">BOX 3 &mdash; APPLICANT</div>
    <table class="form-table">
        <tr>
            <td colspan="4">
                <div style="min-height:18px; border-bottom:1px solid #000; text-align:center; font-size:9px; font-weight:bold; padding-top:10px;">
                    {{ $application->applicant_first_name }} {{ $mi }} {{ $application->applicant_last_name }}
                </div>
                <div style="font-size:7px; text-align:center;">(Signature Over Printed Name)</div>
            </td>
        </tr>
        <tr>
            <td class="label" style="width:10%;">Date</td>
            <td style="width:40%;" class="val">{{ $application->applicant_date_signed?->format('F d, Y') ?? $blank }}</td>
            <td class="label" style="width:10%;">Address</td>
            <td style="width:40%;" class="val">{{ $application->applicant_street }}, {{ $application->applicantBarangay?->name }}, {{ $application->applicantCity?->name }}</td>
        </tr>
        <tr>
            <td class="label" style="width:15%;">Gov't Issued ID No.</td>
            <td style="width:35%;" class="val">{{ $application->applicant_govt_id ?? $blank }}</td>
            <td class="label" style="width:10%;">Date Issued</td>
            <td style="width:40%;" class="val">{{ $application->applicant_id_date_issued?->format('F d, Y') ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label" style="width:15%;">Place Issued</td>
            <td colspan="3" class="val">{{ $application->applicant_id_place_issued ?? $blank }}</td>
        </tr>
    </table>
</div>

{{-- ==================== BOX 4 ==================== --}}
<div class="box">
    <div class="box-title">BOX 4 &mdash; WITH MY CONSENT: LOT OWNER / AUTHORIZED REPRESENTATIVE</div>
    <table class="form-table">
        <tr>
            <td colspan="4">
                <div style="min-height:18px; border-bottom:1px solid #000; text-align:center; font-size:9px; font-weight:bold; padding-top:10px;">
                    {{ $application->owner_name ?? '' }}
                </div>
                <div style="font-size:7px; text-align:center;">(Signature Over Printed Name)</div>
            </td>
        </tr>
        <tr>
            <td class="label" style="width:10%;">Date</td>
            <td style="width:40%;" class="val">{{ $application->owner_date_signed?->format('F d, Y') ?? $blank }}</td>
            <td class="label" style="width:10%;">Address</td>
            <td style="width:40%;" class="val">{{ $application->owner_address ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label" style="width:15%;">Gov't Issued ID No.</td>
            <td style="width:35%;" class="val">{{ $application->owner_govt_id ?? $blank }}</td>
            <td class="label" style="width:10%;">Date Issued</td>
            <td style="width:40%;" class="val">{{ $application->owner_id_date_issued?->format('F d, Y') ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label" style="width:15%;">Place Issued</td>
            <td colspan="3" class="val">{{ $application->owner_id_place_issued ?? $blank }}</td>
        </tr>
    </table>
</div>

{{-- ==================== BOX 5 ==================== --}}
<div class="box">
    <div class="box-title">BOX 5 &mdash; NOTARY</div>
    <div class="notary-box">
        <p>REPUBLIC OF THE PHILIPPINES )</p>
        <p>CITY/MUNICIPALITY OF _________________ ) S.S.</p>
        <br>
        <p style="text-indent:30px;">
            BEFORE ME, a Notary Public for and in the City/Municipality of _________________, personally appeared:
        </p>
        <br>
        <table class="no-border" style="width:90%; margin:0 auto;">
            <tr>
                <td style="width:40%; font-size:7.5px; font-weight:bold;">NAME</td>
                <td style="width:30%; font-size:7.5px; font-weight:bold;">GOV'T ISSUED ID</td>
                <td style="width:30%; font-size:7.5px; font-weight:bold;">DATE/PLACE ISSUED</td>
            </tr>
            <tr>
                <td style="border-bottom:1px solid #000; min-height:15px;">&nbsp;</td>
                <td style="border-bottom:1px solid #000; min-height:15px;">&nbsp;</td>
                <td style="border-bottom:1px solid #000; min-height:15px;">&nbsp;</td>
            </tr>
            <tr>
                <td style="border-bottom:1px solid #000; min-height:15px;">&nbsp;</td>
                <td style="border-bottom:1px solid #000; min-height:15px;">&nbsp;</td>
                <td style="border-bottom:1px solid #000; min-height:15px;">&nbsp;</td>
            </tr>
        </table>
        <br>
        <p style="text-indent:30px;">
            known to me and to me known to be the same person/s who executed the foregoing instrument and acknowledged to me
            that the same is their free act and voluntary deed.
        </p>
        <br>
        <p style="text-indent:30px;">
            IN WITNESS WHEREOF, I have hereunto set my hand and seal, this _____ day of _______________, 20___
            at _______________________, Philippines.
        </p>
        <br>
        <table class="no-border" style="width:100%;">
            <tr>
                <td style="width:50%;">
                    <p>Doc. No. _______</p>
                    <p>Page No. _______</p>
                    <p>Book No. _______</p>
                    <p>Series of _______</p>
                </td>
                <td style="width:50%; text-align:center;">
                    <div style="min-height:30px;">&nbsp;</div>
                    <div style="border-top:1px solid #000; display:inline-block; width:70%; padding-top:2px; font-size:7px;">
                        NOTARY PUBLIC
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- ==================== FOOTER ==================== --}}
<div class="footer">
    <p>Copy 1: Owner &nbsp;|&nbsp; Copy 2: OBO &nbsp;|&nbsp; Copy 3: BFP &nbsp;|&nbsp; Copy 4: Philippine Statistics Authority &nbsp;|&nbsp; Copy 5: Assessors</p>
    <p><i>*May require additional requirements</i></p>
</div>

</body>
</html>
