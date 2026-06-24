@php
    $selectedOccupancy = $application->applicationOccupancyGroups->pluck('occupancy_sub_group_id')->toArray();
    $othersText = $application->applicationOccupancyGroups->pluck('others_text', 'occupancy_sub_group_id')->toArray();
    $scopeId = $application->scope_of_work_id;
    $scopeDetails = $application->scope_of_work_details;

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
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Form - {{ $application->application_number }}</title>
    <style>
        /* ===== SCREEN: Print toolbar ===== */
        @media screen {
            .print-toolbar {
                position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
                background: #1e293b; color: #fff; padding: 10px 24px;
                display: flex; align-items: center; justify-content: space-between;
                box-shadow: 0 2px 8px rgba(0,0,0,.3); font-family: Arial, sans-serif;
            }
            .print-toolbar .title { font-size: 14px; font-weight: 600; }
            .print-toolbar button {
                background: #2563eb; color: #fff; border: none; padding: 8px 24px;
                border-radius: 6px; font-size: 14px; cursor: pointer; font-weight: 600;
            }
            .print-toolbar button:hover { background: #1d4ed8; }
            .print-toolbar .btn-close {
                background: #475569; margin-left: 10px;
            }
            .print-toolbar .btn-close:hover { background: #64748b; }
            .print-page { margin-top: 60px; }
        }

        /* ===== PRINT: hide toolbar, reset margins ===== */
        @media print {
            .print-toolbar { display: none !important; }
            .print-page { margin-top: 0; }
            @page { size: legal portrait; margin: 10mm 12mm; }
        }

        /* ===== SHARED STYLES ===== */
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
            line-height: 1.25;
            margin: 0; padding: 0;
            background: #f1f5f9;
        }
        .print-page {
            width: 8.5in;
            min-height: 13in;
            margin: 0 auto;
            background: #fff;
            padding: 10mm 12mm;
            box-sizing: border-box;
        }
        @media print {
            body { background: #fff; }
            .print-page { width: auto; min-height: auto; padding: 0; box-shadow: none; }
        }
        @media screen {
            .print-page { box-shadow: 0 0 10px rgba(0,0,0,.15); margin-bottom: 20px; }
        }

        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; padding: 1px 3px; }

        .header-table td { border: none; padding: 1px 2px; }
        .header-center { text-align: center; }
        .header-center .title { font-size: 12px; font-weight: bold; margin-top: 4px; }
        .header-center div { font-size: 10px; }

        .box { border: 1px solid #000; margin-bottom: 3px; }
        .box-title {
            font-weight: bold; font-size: 8.5px;
            background: #e0e0e0; padding: 2px 4px;
            border-bottom: 1px solid #000;
        }

        .form-table { width: 100%; }
        .form-table td { border: 1px solid #000; padding: 2px 3px; font-size: 8.5px; }
        .form-table .label { font-weight: bold; font-size: 7.5px; text-transform: uppercase; }

        .no-border td { border: none !important; }
        .checkbox-grid td { border: none !important; padding: 1px 3px; font-size: 8px; }

        .occupancy-table { width: 100%; }
        .occupancy-table td { border: 1px solid #000; padding: 2px 3px; font-size: 7.5px; vertical-align: top; }
        .occupancy-table .group-header { font-weight: bold; font-size: 8px; }

        .notary-box { font-size: 8px; line-height: 1.4; padding: 4px 6px; }
        .notary-box p { margin: 2px 0; }

        .footer-copies { font-size: 7.5px; text-align: center; margin-top: 3px; }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .underline { text-decoration: underline; }
        .val { font-size: 8.5px; }
        .section-bg { background: #f0f0f0; }

        .sig-block {
            min-height: 22px; border-bottom: 1px solid #000;
            text-align: center; font-size: 10px; font-weight: bold; padding-top: 12px;
        }
        .sig-caption { font-size: 7px; text-align: center; }

        /* Page break for page 2 */
        .page-break { page-break-before: always; }
        @media screen { .page-break { margin-top: 30px; } }

        /* BOX 6 fees table */
        .fees-table { width: 100%; }
        .fees-table td, .fees-table th {
            border: 1px solid #000; padding: 3px 5px; font-size: 8px;
        }
        .fees-table th {
            background: #e0e0e0; font-weight: bold; font-size: 8px; text-align: center;
        }
        .fees-table .section-header {
            font-weight: bold; font-size: 8.5px; background: #f5f5f5;
        }
        .fees-table .fee-row td { font-size: 8px; }
        .terms { font-size: 8px; line-height: 1.4; padding: 4px 6px; }
        .terms ol { margin: 0; padding-left: 18px; }
        .terms li { margin-bottom: 4px; }
        .consent-box { font-size: 9px; line-height: 1.5; padding: 8px 12px; }
    </style>
</head>
<body>

{{-- ===== PRINT TOOLBAR ===== --}}
<div class="print-toolbar">
    <span class="title">Application Form: {{ $application->application_number }}</span>
    <div>
        <button onclick="window.print()">&#128424; Print</button>
        <button class="btn-close" onclick="window.close()">&#10005; Close</button>
    </div>
</div>

{{-- ===================================================================== --}}
{{-- PAGE 1 --}}
{{-- ===================================================================== --}}
<div class="print-page">

{{-- HEADER --}}
<table class="header-table" style="width:100%;">
    <tr>
        <td style="width:20%;"></td>
        <td style="width:60%;" class="header-center">
            <div>Republic of the Philippines</div>
            <div><b>City of San Fernando</b></div>
            <div>Province of La Union</div>
            <div class="title">UNIFIED APPLICATION FORM FOR BUILDING PERMIT</div>
        </td>
        <td style="width:20%; text-align:right; font-size:8px; vertical-align:top;">
            <div style="margin-bottom:3px;">
                <b>Complexity:</b><br>
                {!! $complexity === 'Simple' ? '&#9745;' : '&#9744;' !!} Simple<br>
                {!! $complexity === 'Complex' ? '&#9745;' : '&#9744;' !!} Complex
            </div>
        </td>
    </tr>
</table>

{{-- Application Type / Applies To --}}
<table class="no-border" style="width:100%; margin-bottom:2px;">
    <tr>
        <td style="font-size:8.5px;">
            <b>Application Type:</b>&nbsp;
            {!! $appTypeId == 1 ? '&#9745;' : '&#9744;' !!} NEW &nbsp;&nbsp;
            {!! $appTypeId == 2 ? '&#9745;' : '&#9744;' !!} RENEWAL &nbsp;&nbsp;
            {!! $appTypeId == 3 ? '&#9745;' : '&#9744;' !!} AMENDATORY
        </td>
    </tr>
    <tr>
        <td style="font-size:8.5px;">
            @php $appliesTo = $application->applies_to ?? ''; @endphp
            <b>THIS APPLIES ALSO FOR:</b>&nbsp;
            {!! $appliesTo === 'SKIP_LC' ? '&#9744;' : '&#9745;' !!} LOCATIONAL CLEARANCE &nbsp;&nbsp;
            {!! $application->fsec_no ? '&#9745;' : '&#9744;' !!} FIRE SAFETY EVALUATION CLEARANCE
        </td>
    </tr>
    <tr>
        <td style="font-size:8.5px;">
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
            <td class="label" style="width:14%;">OWNER / APPLICANT</td>
            <td class="label" style="width:5%;">LAST NAME</td>
            <td style="width:18%;" class="val">{{ $application->applicant_last_name }}</td>
            <td class="label" style="width:8%;">FIRST NAME</td>
            <td style="width:18%;" class="val">{{ $application->applicant_first_name }}</td>
            <td class="label" style="width:4%;">M.I.</td>
            <td style="width:5%;" class="val">{{ $mi }}</td>
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
            <td style="width:50%;" class="val">
                {{ $application->applicant_street }},
                {{ $application->applicantBarangay?->name }},
                {{ $application->applicantCity?->name }}
            </td>
            <td class="label" style="width:8%;">ZIP CODE</td>
            <td style="width:8%;" class="val">{{ $application->applicant_zip_code ?? $blank }}</td>
            <td class="label" style="width:10%;">CONTACT NO.</td>
            <td style="width:17%;" class="val">{{ $application->applicant_contact_no ?? $blank }}</td>
        </tr>
    </table>

    {{-- Location of Construction --}}
    <table class="form-table">
        <tr><td colspan="8" class="label section-bg">LOCATION OF CONSTRUCTION</td></tr>
        <tr>
            <td class="label" style="width:7%;">LOT NO.</td>
            <td style="width:10%;" class="val">{{ $application->lot_no ?? $blank }}</td>
            <td class="label" style="width:7%;">BLK NO.</td>
            <td style="width:10%;" class="val">{{ $application->block_no ?? $blank }}</td>
            <td class="label" style="width:7%;">TCT NO.</td>
            <td style="width:14%;" class="val">{{ $application->tct_no ?? $blank }}</td>
            <td class="label" style="width:16%;">CURRENT TAX DEC. NO.</td>
            <td style="width:29%;" class="val">{{ $application->tax_dec_no ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label" style="width:7%;">STREET</td>
            <td colspan="3" class="val">{{ $application->building_street ?? $blank }}</td>
            <td class="label" style="width:8%;">BARANGAY</td>
            <td class="val">{{ $application->buildingBarangay?->name ?? $blank }}</td>
            <td class="label" style="width:16%;">CITY/MUNICIPALITY OF</td>
            <td class="val">San Fernando</td>
        </tr>
    </table>

    {{-- Scope of Work --}}
    <table class="form-table">
        <tr><td colspan="6" class="label section-bg">SCOPE OF WORK</td></tr>
    </table>
    <table style="width:100%; border-left:1px solid #000; border-right:1px solid #000; border-bottom:1px solid #000;">
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
            <td class="checkbox-grid">{!! $sk(13) !!} OTHERS (Specify) {{ $sd(13) }}</td>
        </tr>
    </table>

    {{-- Use or Character of Occupancy --}}
    <table class="form-table">
        <tr><td colspan="3" class="label section-bg">USE OR CHARACTER OF OCCUPANCY</td></tr>
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
            {{-- Left column --}}
            <td style="width:33%;">
                <table class="no-border" style="width:100%;">
                    <tr><td class="label" style="font-size:7.5px;">OCCUPANCY CLASSIFIED</td><td class="val">{{ $application->occupancy_classified ?? $blank }}</td></tr>
                    <tr><td class="label" style="font-size:7.5px;">NUMBER OF UNITS</td><td class="val">{{ $application->no_of_units ?? $blank }}</td></tr>
                    <tr><td class="label" style="font-size:7.5px;">NUMBER OF STOREY</td><td class="val">{{ $application->no_of_storeys ?? $blank }}</td></tr>
                    <tr><td class="label" style="font-size:7.5px;">TOTAL FLOOR AREA</td><td class="val">{{ $application->total_floor_area ? number_format($application->total_floor_area, 2) . ' SQ.M.' : $blank }}</td></tr>
                    <tr><td class="label" style="font-size:7.5px;">LOT AREA</td><td class="val">{{ $application->lot_area ? number_format($application->lot_area, 2) . ' SQ.M.' : $blank }}</td></tr>
                </table>
            </td>
            {{-- Center column --}}
            <td style="width:34%;">
                <table class="no-border" style="width:100%;">
                    <tr><td class="label bold" style="font-size:8px;">TOTAL ESTIMATED COST:</td><td class="val bold">&#8369; {{ number_format($application->total_estimated_cost, 2) }}</td></tr>
                    <tr><td class="label" style="font-size:7.5px;">BUILDING</td><td class="val">&#8369; {{ number_format($application->building_cost, 2) }}</td></tr>
                    <tr><td class="label" style="font-size:7.5px;">ELECTRICAL</td><td class="val">&#8369; {{ number_format($application->electrical_cost, 2) }}</td></tr>
                    <tr><td class="label" style="font-size:7.5px;">MECHANICAL</td><td class="val">&#8369; {{ number_format($application->mechanical_cost, 2) }}</td></tr>
                    <tr><td class="label" style="font-size:7.5px;">ELECTRONICS</td><td class="val">&#8369; {{ number_format($application->electronics_cost, 2) }}</td></tr>
                    <tr><td class="label" style="font-size:7.5px;">PLUMBING</td><td class="val">&#8369; {{ number_format($application->plumbing_cost, 2) }}</td></tr>
                </table>
            </td>
            {{-- Right column --}}
            <td style="width:33%;">
                <table class="no-border" style="width:100%;">
                    <tr><td colspan="2" class="label" style="font-size:7.5px;">COST OF EQUIPMENT INSTALLED:</td></tr>
                    <tr><td class="val" style="width:15px;">&#8369;</td><td class="val">{{ $application->equipment_cost_1 ? number_format($application->equipment_cost_1, 2) : $blank }}</td></tr>
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
    <div class="box-title">BOX 2 &mdash; FULL-TIME INSPECTOR AND SUPERVISOR OF CONSTRUCTION WORKS (REPRESENTING THE OWNER)</div>
    <table class="form-table">
        <tr>
            <td colspan="4" class="label" style="font-size:7.5px;">ARCHITECT OR CIVIL ENGINEER (Signed and Sealed Over Printed Name)</td>
        </tr>
        <tr>
            <td colspan="4">
                <div class="sig-block">{{ $application->engineer_name ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td class="label" style="width:10%;">Date</td>
            <td style="width:40%;" class="val">{{ $application->engineer_date_signed?->format('F d, Y') ?? $blank }}</td>
            <td class="label" style="width:10%;">Address</td>
            <td style="width:40%;" class="val">{{ $application->engineer_address ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label">PRC No.</td>
            <td class="val">{{ $application->engineer_prc_no ?? $blank }}</td>
            <td class="label">Validity</td>
            <td class="val">{{ $application->engineer_prc_validity?->format('F d, Y') ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label">PTR No.</td>
            <td class="val">{{ $application->engineer_ptr_no ?? $blank }}</td>
            <td class="label">Date Issued</td>
            <td class="val">{{ $application->engineer_ptr_date_issued?->format('F d, Y') ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label">Issued at</td>
            <td class="val">{{ $application->engineer_ptr_issued_at ?? $blank }}</td>
            <td class="label">TIN</td>
            <td class="val">{{ $application->engineer_tin ?? $blank }}</td>
        </tr>
    </table>
</div>

{{-- ==================== BOX 3 ==================== --}}
<div class="box">
    <div class="box-title">BOX 3 &mdash; APPLICANT</div>
    <table class="form-table">
        <tr>
            <td colspan="4">
                <div class="sig-block">{{ $application->applicant_first_name }} {{ $mi }} {{ $application->applicant_last_name }}</div>
                <div class="sig-caption">(Signature Over Printed Name)</div>
            </td>
        </tr>
        <tr>
            <td class="label" style="width:10%;">Date</td>
            <td style="width:40%;" class="val">{{ $application->applicant_date_signed?->format('F d, Y') ?? $blank }}</td>
            <td class="label" style="width:10%;">Address</td>
            <td style="width:40%;" class="val">{{ $application->applicant_street }}, {{ $application->applicantBarangay?->name }}, {{ $application->applicantCity?->name }}</td>
        </tr>
        <tr>
            <td class="label" style="width:15%;">Gov&rsquo;t Issued ID No.</td>
            <td class="val">{{ $application->applicant_govt_id ?? $blank }}</td>
            <td class="label">Date Issued</td>
            <td class="val">{{ $application->applicant_id_date_issued?->format('F d, Y') ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label">Place Issued</td>
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
                <div class="sig-block">{{ $application->owner_name ?? '' }}</div>
                <div class="sig-caption">(Signature Over Printed Name)</div>
            </td>
        </tr>
        <tr>
            <td class="label" style="width:10%;">Date</td>
            <td style="width:40%;" class="val">{{ $application->owner_date_signed?->format('F d, Y') ?? $blank }}</td>
            <td class="label" style="width:10%;">Address</td>
            <td style="width:40%;" class="val">{{ $application->owner_address ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label" style="width:15%;">Gov&rsquo;t Issued ID No.</td>
            <td class="val">{{ $application->owner_govt_id ?? $blank }}</td>
            <td class="label">Date Issued</td>
            <td class="val">{{ $application->owner_id_date_issued?->format('F d, Y') ?? $blank }}</td>
        </tr>
        <tr>
            <td class="label">Place Issued</td>
            <td colspan="3" class="val">{{ $application->owner_id_place_issued ?? $blank }}</td>
        </tr>
    </table>
</div>

{{-- ==================== BOX 5 ==================== --}}
<div class="box">
    <div class="box-title">BOX 5 &mdash; NOTARY</div>
    <div class="notary-box">
        <p>REPUBLIC OF THE PHILIPPINES <span style="margin-left:200px;">)</span></p>
        <p>CITY/MUNICIPALITY OF _________________________________ ) S.S.</p>
        <br>
        <p style="text-indent:30px;">
            BEFORE ME, at the City/Municipality of _________________________________, on _________________________ personally appeared the following:
        </p>
        <br>
        <table class="no-border" style="width:90%; margin:0 auto;">
            <tr>
                <td style="width:40%; font-size:8px; font-weight:bold; border-bottom:1px solid #000; padding-bottom:15px;">&nbsp;</td>
                <td style="width:5%;">&nbsp;</td>
                <td style="width:20%; font-size:8px; font-weight:bold; border-bottom:1px solid #000; padding-bottom:15px;">&nbsp;</td>
                <td style="width:5%;">&nbsp;</td>
                <td style="width:15%; font-size:8px; font-weight:bold; border-bottom:1px solid #000; padding-bottom:15px;">&nbsp;</td>
                <td style="width:5%;">&nbsp;</td>
                <td style="width:30%; font-size:8px; font-weight:bold; border-bottom:1px solid #000; padding-bottom:15px;">&nbsp;</td>
            </tr>
            <tr>
                <td style="border-bottom:1px solid #000; padding-bottom:15px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td style="border-bottom:1px solid #000; padding-bottom:15px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td style="border-bottom:1px solid #000; padding-bottom:15px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td style="border-bottom:1px solid #000; padding-bottom:15px;">&nbsp;</td>
            </tr>
        </table>
        <br>
        <p style="text-indent:30px;">
            whose signatures appear hereinabove, known to me to be the same persons who executed this standard prescribed form and acknowledged to me that the same is their free and voluntary act and deed.
        </p>
        <br>
        <p style="text-indent:30px;">WITNESS MY HAND AND SEAL on the date and place above written.</p>
        <br>
        <table class="no-border" style="width:100%;">
            <tr>
                <td style="width:40%;">
                    <p>Doc. No. ____________</p>
                    <p>Page No. ____________</p>
                    <p>Book No. ____________</p>
                    <p>Series of ____________</p>
                </td>
                <td style="width:60%; text-align:center;">
                    <div style="min-height:35px;">&nbsp;</div>
                    <div style="border-top:1px solid #000; display:inline-block; width:80%; padding-top:2px; font-size:7.5px;">
                        NOTARY PUBLIC (Until December _______________)
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- FOOTER --}}
<div class="footer-copies">
    <b>Copy 1:</b> Owner &nbsp;&nbsp; <b>Copy 2:</b> OBO &nbsp;&nbsp; <b>Copy 3:</b> BFP &nbsp;&nbsp; <b>Copy 4:</b> Philippine Statistics Authority &nbsp;&nbsp; <b>Copy 5:</b> Assessors
</div>
<div style="font-size:7px; text-align:left; margin-top:2px;"><i>*May require additional requirements</i></div>

</div>{{-- end .print-page (page 1) --}}


{{-- ===================================================================== --}}
{{-- PAGE 2 --}}
{{-- ===================================================================== --}}
<div class="print-page page-break">

<div style="text-align:center; margin-bottom:6px;">
    <div style="font-size:9px;">BP No.: <span class="underline">&nbsp;&nbsp;{{ $application->bp_number ?? '___________________________' }}&nbsp;&nbsp;</span></div>
</div>

{{-- ==================== BOX 6 ==================== --}}
<div class="box">
    <div class="box-title">BOX 6 &mdash; TO BE ACCOMPLISHED BY THE PROCESSING AND EVALUATION DIVISION</div>

    <table class="fees-table">
        <thead>
            <tr>
                <th style="width:30%;">ASSESSED FEES</th>
                <th style="width:18%;">ACCOUNT</th>
                <th style="width:18%;">BASIS OF ASSESSMENT</th>
                <th style="width:14%;">AMOUNT DUE</th>
                <th style="width:20%;">ASSESSED BY</th>
            </tr>
        </thead>
        <tbody>
            {{-- Zoning --}}
            <tr class="section-header"><td colspan="5" class="section-header">FOR ZONING (ZONING ADMINISTRATOR):</td></tr>
            <tr class="fee-row"><td>&nbsp;&nbsp; &#9675; LOCATIONAL / ZONING OF LAND</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>

            {{-- Building --}}
            <tr class="section-header"><td colspan="5" class="section-header">FOR BUILDING / STRUCTURE (OBO):</td></tr>
            @foreach(['FILING FEE', 'LINE AND GRADE (Geodetic)', 'FENCING', 'ARCHITECTURAL', 'CIVIL / STRUCTURAL', 'ELECTRICAL', 'MECHANICAL', 'SANITARY', 'PLUMBING', 'ELECTRONICS', 'INTERIOR', 'SURCHARGES', 'PENALTIES'] as $fee)
            <tr class="fee-row"><td>&nbsp;&nbsp; &#9675; {{ $fee }}</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endforeach

            {{-- Fire Safety --}}
            <tr class="section-header"><td colspan="5" class="section-header">FOR FIRE SAFETY (BFP):</td></tr>
            <tr class="fee-row"><td>&nbsp;&nbsp; &#9675; FIRE CODE CONSTRUCTION TAX</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr class="fee-row"><td>&nbsp;&nbsp; &#9675; HOTWORKS</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>

            {{-- Total --}}
            <tr style="font-weight:bold;">
                <td colspan="3" style="text-align:center; border:1px solid #000; padding:4px; font-size:9px;">T O T A L</td>
                <td style="border:1px solid #000;">&nbsp;</td>
                <td style="border:1px solid #000;">&nbsp;</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ==================== TERMS AND CONDITIONS ==================== --}}
<div class="box">
    <div class="box-title">TERMS AND CONDITIONS:</div>
    <div class="terms">
        <ol>
            <li>The Owner/Applicant shall accomplish the prescribed Application Form, with the assistance of the concerned design professional/s and/or the Architect/Civil Engineer, hired/commissioned by him/her as full-time inspector/supervisor of the construction works, by filling up the necessary data / information required thereat.</li>
            <li>The fully accomplished prescribed Application Form, duly notarized, shall be submitted to the concerned Office of the Building Official, accompanied by the various applicable ancillary and accessory permits, plans and specifications signed and sealed by the corresponding design professionals who shall be responsible for the comprehensive and correctness of the plans in compliance to the National Building Code of the Philippines (PD 1096), its Revised IRR and all applicable referral codes and professional regulatory laws, together with the other documentary requirements pursuant to Section 302 of PD 1096 and its Revised IRR.</li>
        </ol>
    </div>
</div>

{{-- ==================== DATA PRIVACY CONSENT ==================== --}}
<div class="box">
    <div class="consent-box">
        <p>I have read this form, understood its contents and consent to the processing of my personal data. I understand that my consent does not preclude the existence of other criteria for lawful processing of personal data, and does not waive any of my rights under the Data Privacy Act of 2012 and other applicable laws.</p>
        <br>
        <div style="text-align:center; margin-top:20px;">
            <div style="border-top:1px solid #000; display:inline-block; width:50%; padding-top:3px; font-size:8px;">
                SIGNATURE OVER PRINTED NAME OF OWNER/APPLICANT
            </div>
        </div>
    </div>
</div>

</div>{{-- end .print-page (page 2) --}}

</body>
</html>
