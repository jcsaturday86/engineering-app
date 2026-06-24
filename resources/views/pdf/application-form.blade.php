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

    $blank = '________________________________';
    $mi = $application->applicant_middle_name ? mb_substr($application->applicant_middle_name, 0, 1) . '.' : '';
    $complexity = $application->complexity ?? '';
    $appTypeId = $application->application_type_id;
    $appliesTo = $application->applies_to ?? '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Application Form - {{ $application->application_number }}</title>
    <style>
        @media screen {
            .print-toolbar {
                position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
                background: #1e293b; color: #fff; padding: 8px 20px;
                display: flex; align-items: center; justify-content: space-between;
                box-shadow: 0 2px 8px rgba(0,0,0,.3); font-family: sans-serif;
            }
            .print-toolbar .title { font-size: 13px; font-weight: 600; }
            .print-toolbar button {
                background: #2563eb; color: #fff; border: none; padding: 7px 20px;
                border-radius: 5px; font-size: 13px; cursor: pointer; font-weight: 600;
            }
            .print-toolbar button:hover { background: #1d4ed8; }
            .print-toolbar .btn-close { background: #475569; margin-left: 8px; }
            .print-toolbar .btn-close:hover { background: #64748b; }
            .print-page { margin-top: 52px; }
        }
        @media print {
            .print-toolbar { display: none !important; }
            .print-page { margin-top: 0; }
            @page { size: legal portrait; margin: 8mm 10mm; }
        }

        * { box-sizing: border-box; }
        body {
            font-family: sans-serif;
            font-size: 7.5pt;
            color: #000;
            line-height: 1.15;
            margin: 0; padding: 0;
            background: #9e9e9e;
        }
        .print-page {
            width: 612px; /* 8.5in at 72dpi */
            margin: 10px auto;
            background: #fff;
            padding: 12px 14px;
            position: relative;
        }
        @media print {
            body { background: #fff; }
            .print-page { width: auto; padding: 0; margin: 0; box-shadow: none; }
        }
        @media screen {
            .print-page { box-shadow: 1px 1px 3px 1px #333; }
        }

        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; padding: 1px 2px; font-size: 7.5pt; }

        /* Main bordered box */
        .bx { border: 1px solid #000; position: relative; }
        .bx + .bx { margin-top: -1px; }

        /* Form cells */
        .fc td { border: 1px solid #000; padding: 1px 3px; font-size: 7pt; }
        .fc .lb { font-weight: bold; font-size: 6.5pt; text-transform: uppercase; }

        /* No border helper */
        .nb td, .nb th { border: none !important; padding: 1px 2px; }

        /* Checkbox items */
        .ck { font-size: 6.5pt; padding: 0 2px; line-height: 1.3; }
        .gh { font-weight: bold; font-size: 7pt; padding-top: 2px; }

        /* Signature */
        .sig { min-height: 18px; border-bottom: 1px solid #000; text-align: center; font-size: 8pt; font-weight: bold; padding-top: 10px; }
        .sigcap { font-size: 6pt; text-align: center; }

        .bold { font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .uline { text-decoration: underline; }
        .small { font-size: 6pt; }
        .xsmall { font-size: 5.5pt; }

        /* Page 2 */
        .page-break { page-break-before: always; }
        @media screen { .page-break { margin-top: 20px; } }

        .ft td, .ft th { border: 1px solid #000; padding: 2px 3px; font-size: 7pt; }
        .ft th { background: #e0e0e0; font-weight: bold; text-align: center; }
        .ft .sh { font-weight: bold; font-size: 7pt; background: #f5f5f5; }
    </style>
</head>
<body>

<div class="print-toolbar">
    <span class="title">{{ $application->application_number }}</span>
    <div>
        <button onclick="window.print()">&#128424; Print</button>
        <button class="btn-close" onclick="window.close()">&#10005; Close</button>
    </div>
</div>

{{-- ======================== PAGE 1 ======================== --}}
<div class="print-page">

{{-- HEADER --}}
<table class="nb" style="margin-bottom:2px;">
    <tr>
        <td style="width:15%;"></td>
        <td style="width:50%; text-align:center;">
            <div style="font-size:8pt;">Republic of the Philippines</div>
            <div style="font-size:9pt;"><b>City of San Fernando</b></div>
            <div style="font-size:9pt;">Province of La Union</div>
            <div style="font-size:11pt; font-weight:bold; margin-top:3px;">UNIFIED APPLICATION FORM FOR BUILDING PERMIT</div>
        </td>
        <td style="width:35%; text-align:right; vertical-align:top;">
            <table class="nb" style="float:right; width:auto;">
                <tr><td class="bold" style="font-size:7pt; text-align:left;">APPLICATION NO.</td></tr>
                <tr><td style="border-bottom:1px solid #000!important; min-width:120px; font-size:7.5pt; text-align:center;">{{ $application->application_number }}</td></tr>
                <tr><td class="bold" style="font-size:7pt; text-align:left; padding-top:3px;">AREA NO.</td></tr>
                <tr><td style="border-bottom:1px solid #000!important; font-size:7.5pt; text-align:center;">{{ $application->area_number ?? '' }}</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- Application Type row --}}
<table class="nb" style="margin-bottom:1px;">
    <tr>
        <td style="font-size:7pt;">
            {!! $appTypeId == 1 ? '&#9745;' : '&#9744;' !!} <span class="bold">NEW</span> &nbsp;
            {!! $appTypeId == 2 ? '&#9745;' : '&#9744;' !!} <span class="bold">RENEWAL</span> &nbsp;
            {!! $appTypeId == 3 ? '&#9745;' : '&#9744;' !!} <span class="bold">AMENDATORY</span>
        </td>
        <td style="text-align:right; font-size:7pt;">
            {!! $complexity === 'Simple' ? '&#9745;' : '&#9744;' !!} <span class="bold">SIMPLE</span> &nbsp;
            {!! $complexity === 'Complex' ? '&#9745;' : '&#9744;' !!} <span class="bold">COMPLEX*</span>
        </td>
    </tr>
</table>

{{-- Applies To --}}
<div class="bx" style="padding:2px 4px; font-size:7pt;">
    THIS APPLIES ALSO FOR :&nbsp;&nbsp;
    {!! $appliesTo === 'SKIP_LC' ? '&#9744;' : '&#9745;' !!} LOCATIONAL CLEARANCE
    &nbsp;&nbsp;&nbsp;&nbsp;
    {!! $application->fsec_no ? '&#9745;' : '&#9744;' !!} FIRE SAFETY EVALUATION CLEARANCE
</div>

{{-- ==================== BOX 1 ==================== --}}
<div class="bx">
    <div style="font-weight:bold; font-size:7pt; background:#e0e0e0; padding:1px 3px; border-bottom:1px solid #000;">BOX 1 (TO BE ACCOMPLISHED IN PRINT BY THE APPLICANT)</div>

    {{-- Owner/Applicant --}}
    <table class="fc">
        <tr>
            <td class="lb" style="width:12%;">OWNER / APPLICANT</td>
            <td class="lb" style="width:8%;">LAST NAME</td>
            <td style="width:18%;">{{ $application->applicant_last_name }}</td>
            <td class="lb" style="width:8%;">FIRST NAME</td>
            <td style="width:18%;">{{ $application->applicant_first_name }}</td>
            <td class="lb" style="width:3%;">M.I.</td>
            <td style="width:5%;">{{ $mi }}</td>
            <td class="lb" style="width:3%;">TIN</td>
            <td style="width:25%;">{{ $application->applicant_tin ?? '' }}</td>
        </tr>
    </table>
    <table class="fc">
        <tr>
            <td class="lb" style="width:30%;">FOR CONSTRUCTION OWNED BY AN ENTERPRISE</td>
            <td style="width:35%;">{{ $application->enterprise_name ?? '' }}</td>
            <td class="lb" style="width:15%;">FORM OF OWNERSHIP</td>
            <td style="width:20%;">{{ $application->formOfOwnership?->name ?? '' }}</td>
        </tr>
    </table>
    <table class="fc">
        <tr>
            <td class="lb" style="width:6%;">ADDRESS:</td>
            <td style="width:47%;">{{ $application->applicant_street }}, {{ $application->applicantBarangay?->name }}, {{ $application->applicantCity?->name }}</td>
            <td class="lb" style="width:7%;">ZIP CODE</td>
            <td style="width:8%;">{{ $application->applicant_zip_code ?? '' }}</td>
            <td class="lb" style="width:9%;">CONTACT NO.</td>
            <td style="width:23%;">{{ $application->applicant_contact_no ?? '' }}</td>
        </tr>
    </table>

    {{-- Location --}}
    <table class="fc">
        <tr>
            <td class="lb" style="width:14%;">LOCATION OF CONSTRUCTION:</td>
            <td class="lb" style="width:5%;">LOT NO.</td>
            <td style="width:8%;">{{ $application->lot_no ?? '' }}</td>
            <td class="lb" style="width:5%;">BLK NO.</td>
            <td style="width:8%;">{{ $application->block_no ?? '' }}</td>
            <td class="lb" style="width:5%;">TCT NO.</td>
            <td style="width:12%;">{{ $application->tct_no ?? '' }}</td>
            <td class="lb" style="width:14%;">CURRENT TAX DEC. NO.</td>
            <td style="width:29%;">{{ $application->tax_dec_no ?? '' }}</td>
        </tr>
        <tr>
            <td class="lb" colspan="2">STREET</td>
            <td colspan="3">{{ $application->building_street ?? '' }}</td>
            <td class="lb">BARANGAY</td>
            <td colspan="2">{{ $application->buildingBarangay?->name ?? '' }}</td>
            <td>San Fernando</td>
        </tr>
    </table>

    {{-- Scope of Work --}}
    <table class="fc"><tr><td class="lb bold" colspan="6" style="background:#e8e8e8;">SCOPE OF WORK</td></tr></table>
    <table style="border-left:1px solid #000; border-right:1px solid #000; border-bottom:1px solid #000;">
        <tr>
            <td class="ck" style="width:25%;">{!! $sk(1) !!} NEW CONSTRUCTION</td>
            <td class="ck" style="width:20%;">{!! $sk(3) !!} RENOVATION {{ $sd(3) }}</td>
            <td class="ck" style="width:20%;">{!! $sk(7) !!} RAISING {{ $sd(7) }}</td>
            <td class="ck" style="width:35%;">{!! $sk(10) !!} ACCESSORY BUILDING/STRUCTURE {{ $sd(10) }}</td>
        </tr>
        <tr>
            <td class="ck">{!! $sk(11) !!} ERECTION {{ $sd(11) }}</td>
            <td class="ck">{!! $sk(5) !!} CONVERSION {{ $sd(5) }}</td>
            <td class="ck">{!! $sk(8) !!} MOVING {{ $sd(8) }}</td>
            <td class="ck">{!! $sk(12) !!} LEGALIZATION OF EXISTING BUILDING {{ $sd(12) }}</td>
        </tr>
        <tr>
            <td class="ck">{!! $sk(2) !!} ADDITION {{ $sd(2) }}</td>
            <td class="ck">{!! $sk(6) !!} REPAIR {{ $sd(6) }}</td>
            <td class="ck">{!! $sk(4) !!} ALTERATION {{ $sd(4) }}</td>
            <td class="ck">{!! $sk(13) !!} OTHERS (Specify) {{ $sd(13) }}</td>
        </tr>
    </table>

    {{-- Use or Character of Occupancy --}}
    <table class="fc"><tr><td class="lb bold" colspan="3" style="background:#e8e8e8;">USE OR CHARACTER OF OCCUPANCY</td></tr></table>
    <table style="width:100%;">
        <tr>
            <td style="width:33%; border:1px solid #000; padding:2px 3px; vertical-align:top;">
                <div class="gh">GROUP A : RESIDENTIAL (DWELLINGS)</div>
                <div class="ck">{!! $ck(1) !!} SINGLE &nbsp; {!! $ck(2) !!} DUPLEX &nbsp; {!! $ck(3) !!} RESIDENTIAL R-1, R-2</div>
                <div class="ck">{!! $ck(4) !!} OTHERS {{ $ot(4) }}</div>
                <div class="gh">GROUP B : RESIDENTIAL</div>
                <div class="ck">{!! $ck(5) !!} HOTEL &nbsp; {!! $ck(6) !!} MOTEL &nbsp; {!! $ck(7) !!} TOWNHOUSE</div>
                <div class="ck">{!! $ck(8) !!} DORMITORY &nbsp; {!! $ck(9) !!} BOARDINGHOUSE, LODGING HOUSE</div>
                <div class="ck">{!! $ck(10) !!} RESIDENTIAL R-3, R-4, R-5</div>
                <div class="ck">{!! $ck(11) !!} OTHERS {{ $ot(11) }}</div>
                <div class="gh">GROUP C : EDUCATIONAL &amp; RECREATIONAL</div>
                <div class="ck">{!! $ck(12) !!} SCHOOL BUILDING &nbsp; {!! $ck(13) !!} SCHOOL AUDITORIUM, GYMNASIUM</div>
                <div class="ck">{!! $ck(14) !!} CIVIC CENTER &nbsp; {!! $ck(15) !!} CLUBHOUSE</div>
                <div class="ck">{!! $ck(16) !!} CHURCH, MOSQUE, TEMPLE, CHAPEL</div>
                <div class="ck">{!! $ck(17) !!} OTHERS {{ $ot(17) }}</div>
                <div class="gh">GROUP D : INSTITUTIONAL</div>
                <div class="ck">{!! $ck(18) !!} HOSPITAL OR SIMILAR STRUCTURE</div>
                <div class="ck">{!! $ck(19) !!} HOME FOR THE AGED &nbsp; {!! $ck(20) !!} GOVERNMENT OFFICE</div>
                <div class="ck">{!! $ck(21) !!} OTHERS {{ $ot(21) }}</div>
            </td>
            <td style="width:33%; border:1px solid #000; padding:2px 3px; vertical-align:top;">
                <div class="gh">GROUP E : COMMERCIAL</div>
                <div class="ck">{!! $ck(22) !!} BANK &nbsp; {!! $ck(23) !!} STORE</div>
                <div class="ck">{!! $ck(24) !!} SHOPPING CENTER / MALL</div>
                <div class="ck">{!! $ck(25) !!} DRINKING / DINING ESTABLISHMENT</div>
                <div class="ck">{!! $ck(26) !!} SHOP (i.e. DRESS SHOP, TAILORING, BARBERSHOP, etc.)</div>
                <div class="ck">{!! $ck(27) !!} OTHERS {{ $ot(27) }}</div>
                <div class="gh">GROUP F : LIGHT INDUSTRIAL</div>
                <div class="ck">{!! $ck(28) !!} FACTORY / PLANT (USING INCOMBUSTIBLE/ NON-EXPLOSIVE MATERIALS</div>
                <div class="ck">{!! $ck(29) !!} OTHERS {{ $ot(29) }}</div>
                <div class="gh">GROUP G : MEDIUM INDUSTRIAL</div>
                <div class="ck">{!! $ck(30) !!} STORAGE / WAREHOUSE (FOR HAZARDOUS/ HIGHLY FLAMMABLE MATERIALS</div>
                <div class="ck">{!! $ck(31) !!} FACTORY (FOR HAZARDOUS/ HIGHLY FLAMMABLE MATERIALS</div>
                <div class="ck">{!! $ck(32) !!} OTHERS {{ $ot(32) }}</div>
            </td>
            <td style="width:34%; border:1px solid #000; padding:2px 3px; vertical-align:top;">
                <div class="gh">GROUP H : ASSEMBLY (OCCUPANT LOAD LESS THAN 1,000)</div>
                <div class="ck">{!! $ck(33) !!} THEATER, AUDITORIUM, CONVENTION HALL, GRANDSTAND/ BLEACHER</div>
                <div class="ck">{!! $ck(34) !!} OTHERS {{ $ot(34) }}</div>
                <div class="gh">GROUP I : ASSEMBLY (OCCUPANT LOAD 1,000 OR MORE)</div>
                <div class="ck">{!! $ck(35) !!} COLISEUM, SPORTS COMPLEX, CONVENTION CENTER AND SIMILAR STRUCTURE</div>
                <div class="ck">{!! $ck(36) !!} OTHERS {{ $ot(36) }}</div>
                <div class="gh">GROUP J : (J-1) AGRICULTURAL</div>
                <div class="ck">{!! $ck(37) !!} BARN, GRANARY, POULTRY HOUSE, PIGGERY, GRAIN MILL, GRAIN SILO</div>
                <div class="ck">{!! $ck(38) !!} OTHERS {{ $ot(38) }}</div>
                <div class="gh">GROUP J : (J-2) ACCESSORIES</div>
                <div class="ck">{!! $ck(39) !!} PRIVATE CARPORT / GARAGE, TOWER, SWIMMING POOL, FENCE OVER 1.80m, STEEL / CONCRETE TANK</div>
                <div class="ck">{!! $ck(40) !!} OTHERS {{ $ot(40) }}</div>
            </td>
        </tr>
    </table>

    {{-- Cost / Details side-by-side --}}
    <table style="width:100%; border-top:1px solid #000;">
        <tr>
            <td style="width:35%; border-right:1px solid #000; border-bottom:1px solid #000; padding:2px 3px; vertical-align:top; font-size:7pt;">
                OCCUPANCY CLASSIFIED {{ $application->occupancy_classified ?? '' }}<br>
                NUMBER OF UNITS {{ $application->no_of_units ?? '' }}<br>
                NUMBER OF STOREY {{ $application->no_of_storeys ?? '' }}<br>
                TOTAL FLOOR AREA {{ $application->total_floor_area ? number_format($application->total_floor_area, 2) . ' SQ. M.' : '' }}<br>
                LOT AREA {{ $application->lot_area ? number_format($application->lot_area, 2) . ' SQ. M' : '' }}
            </td>
            <td style="width:65%; border-bottom:1px solid #000; padding:2px 3px; vertical-align:top; font-size:7pt;">
                <b>TOTAL ESTIMATED COST:</b> <b>P</b> {{ number_format($application->total_estimated_cost, 2) }}<br>
                BUILDING {{ number_format($application->building_cost, 2) }}
                <span style="float:right;">COST OF EQUIPMENT INSTALLED:</span><br>
                ELECTRICAL {{ number_format($application->electrical_cost, 2) }}
                <span style="float:right;">P {{ $application->equipment_cost_1 ? number_format($application->equipment_cost_1, 2) : '' }}</span><br>
                MECHANICAL {{ number_format($application->mechanical_cost, 2) }}
                <span style="float:right;">P {{ $application->equipment_cost_2 ? number_format($application->equipment_cost_2, 2) : '' }}</span><br>
                ELECTRONICS {{ number_format($application->electronics_cost, 2) }}
                <span style="float:right;">P {{ $application->equipment_cost_3 ? number_format($application->equipment_cost_3, 2) : '' }}</span><br>
                PLUMBING {{ number_format($application->plumbing_cost, 2) }}
                <span style="float:right;">P {{ $application->equipment_cost_4 ? number_format($application->equipment_cost_4, 2) : '' }}</span>
            </td>
        </tr>
    </table>

    {{-- Dates --}}
    <div style="border-top:0; padding:2px 3px; font-size:7pt; border-bottom:1px solid #000;">
        PROPOSED DATE OF CONSTRUCTION: {{ $application->proposed_construction_date?->format('F d, Y') ?? '' }}
        <span style="margin-left:40px;">EXPECTED DATE OF COMPLETION: {{ $application->expected_completion_date?->format('F d, Y') ?? '' }}</span>
    </div>
</div>

{{-- ==================== BOX 2 ==================== --}}
<div class="bx" style="margin-top:-1px;">
    <div style="font-weight:bold; font-size:7pt; background:#e0e0e0; padding:1px 3px; border-bottom:1px solid #000;">FULL-TIME INSPECTOR AND SUPERVISOR OF CONSTRUCTION WORKS (REPRESENTING THE OWNER)</div>
    <table class="fc">
        <tr><td colspan="4" class="lb xsmall">LICENSED ARCHITECT OR CIVIL ENGINEER<br><span style="font-weight:normal;">(Full-Time Inspector and Supervisor of Construction Works)</span></td></tr>
        <tr><td colspan="4"><div class="sig">{{ $application->engineer_name ?? '' }}</div></td></tr>
        <tr>
            <td class="lb" style="width:10%;">Date</td><td style="width:40%;">{{ $application->engineer_date_signed?->format('F d, Y') ?? '' }}</td>
            <td class="lb" style="width:10%;">Address</td><td style="width:40%;">{{ $application->engineer_address ?? '' }}</td>
        </tr>
        <tr>
            <td class="lb">PRC No.</td><td>{{ $application->engineer_prc_no ?? '' }}</td>
            <td class="lb">Validity</td><td>{{ $application->engineer_prc_validity?->format('F d, Y') ?? '' }}</td>
        </tr>
        <tr>
            <td class="lb">PTR No.</td><td>{{ $application->engineer_ptr_no ?? '' }}</td>
            <td class="lb">Date Issued</td><td>{{ $application->engineer_ptr_date_issued?->format('F d, Y') ?? '' }}</td>
        </tr>
        <tr>
            <td class="lb">Issued at</td><td>{{ $application->engineer_ptr_issued_at ?? '' }}</td>
            <td class="lb">TIN</td><td>{{ $application->engineer_tin ?? '' }}</td>
        </tr>
    </table>
</div>

{{-- ==================== BOX 3 ==================== --}}
<div class="bx" style="margin-top:-1px;">
    <div style="font-weight:bold; font-size:7pt; background:#e0e0e0; padding:1px 3px; border-bottom:1px solid #000;">BOX 3 &mdash; APPLICANT</div>
    <table class="fc">
        <tr><td colspan="4"><div class="sig">{{ $application->applicant_first_name }} {{ $mi }} {{ $application->applicant_last_name }}</div><div class="sigcap">(Signature Over Printed Name)</div></td></tr>
        <tr>
            <td class="lb" style="width:10%;">Date</td><td style="width:40%;">{{ $application->applicant_date_signed?->format('F d, Y') ?? '' }}</td>
            <td class="lb" style="width:10%;">Address</td><td style="width:40%;">{{ $application->applicant_street }}, {{ $application->applicantBarangay?->name }}, {{ $application->applicantCity?->name }}</td>
        </tr>
        <tr>
            <td class="lb">Gov&rsquo;t Issued ID No.</td><td>{{ $application->applicant_govt_id ?? '' }}</td>
            <td class="lb">Date Issued</td><td>{{ $application->applicant_id_date_issued?->format('F d, Y') ?? '' }}</td>
        </tr>
        <tr><td class="lb">Place Issued</td><td colspan="3">{{ $application->applicant_id_place_issued ?? '' }}</td></tr>
    </table>
</div>

{{-- ==================== BOX 4 ==================== --}}
<div class="bx" style="margin-top:-1px;">
    <div style="font-weight:bold; font-size:7pt; background:#e0e0e0; padding:1px 3px; border-bottom:1px solid #000;">WITH MY CONSENT: LOT OWNER / AUTHORIZED REPRESENTATIVE</div>
    <table class="fc">
        <tr><td colspan="4"><div class="sig">{{ $application->owner_name ?? '' }}</div><div class="sigcap">(Signature Over Printed Name)</div></td></tr>
        <tr>
            <td class="lb" style="width:10%;">Date</td><td style="width:40%;">{{ $application->owner_date_signed?->format('F d, Y') ?? '' }}</td>
            <td class="lb" style="width:10%;">Address</td><td style="width:40%;">{{ $application->owner_address ?? '' }}</td>
        </tr>
        <tr>
            <td class="lb">Gov&rsquo;t Issued ID No.</td><td>{{ $application->owner_govt_id ?? '' }}</td>
            <td class="lb">Date Issued</td><td>{{ $application->owner_id_date_issued?->format('F d, Y') ?? '' }}</td>
        </tr>
        <tr><td class="lb">Place Issued</td><td colspan="3">{{ $application->owner_id_place_issued ?? '' }}</td></tr>
    </table>
</div>

{{-- ==================== BOX 5 ==================== --}}
<div class="bx" style="margin-top:-1px;">
    <div style="font-weight:bold; font-size:7pt; background:#e0e0e0; padding:1px 3px; border-bottom:1px solid #000;">BOX 5</div>
    <div style="font-size:7pt; line-height:1.3; padding:3px 5px;">
        <p style="margin:1px 0;">REPUBLIC OF THE PHILIPPINES <span style="margin-left:150px;">)</span></p>
        <p style="margin:1px 0;">CITY/MUNICIPALITY OF _________________________ ) S.S.</p>
        <p style="margin:3px 0; text-indent:20px;">BEFORE ME, at the City/Municipality of _________________________, on _____________________ personally appeared the following:</p>
        <p style="margin:3px 0;">&nbsp;</p>
        <table class="nb" style="width:95%; margin:0 auto;">
            <tr>
                <td style="border-bottom:1px solid #000!important; padding-bottom:10px; width:40%;">&nbsp;</td>
                <td style="width:3%;">&nbsp;</td>
                <td style="border-bottom:1px solid #000!important; padding-bottom:10px; width:20%;">&nbsp;</td>
                <td style="width:3%;">&nbsp;</td>
                <td style="border-bottom:1px solid #000!important; padding-bottom:10px; width:14%;">&nbsp;</td>
                <td style="width:3%;">&nbsp;</td>
                <td style="border-bottom:1px solid #000!important; padding-bottom:10px; width:27%;">&nbsp;</td>
            </tr>
            <tr>
                <td style="border-bottom:1px solid #000!important; padding-bottom:10px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td style="border-bottom:1px solid #000!important; padding-bottom:10px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td style="border-bottom:1px solid #000!important; padding-bottom:10px;">&nbsp;</td>
                <td>&nbsp;</td>
                <td style="border-bottom:1px solid #000!important; padding-bottom:10px;">&nbsp;</td>
            </tr>
        </table>
        <p style="margin:3px 0; text-indent:20px;">whose signatures appear hereinabove, known to me to be the same persons who executed this standard prescribed form and acknowledged to me that the same is their free and voluntary act and deed.</p>
        <p style="margin:3px 0; text-indent:20px;">WITNESS MY HAND AND SEAL on the date and place above written.</p>
        <table class="nb" style="width:100%; margin-top:3px;">
            <tr>
                <td style="width:35%; font-size:7pt;">
                    Doc. No. ________<br>Page No. ________<br>Book No. ________<br>Series of ________
                </td>
                <td style="width:65%; text-align:center; vertical-align:bottom;">
                    <div style="border-top:1px solid #000; display:inline-block; width:75%; padding-top:2px; font-size:6.5pt;">
                        NOTARY PUBLIC (Until December ___________)
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- FOOTER --}}
<div style="font-size:6.5pt; text-align:center; margin-top:2px; font-weight:bold;">
    Copy 1: Owner &nbsp;&nbsp;&nbsp; Copy 2: OBO &nbsp;&nbsp;&nbsp; Copy 3: BFP &nbsp;&nbsp;&nbsp; Copy 4: Philippine Statistics Authority &nbsp;&nbsp;&nbsp; Copy 5: Assessors
</div>
<div style="font-size:6pt; margin-top:1px;"><i>*May require additional requirements</i></div>

</div>{{-- end page 1 --}}

{{-- ======================== PAGE 2 ======================== --}}
<div class="print-page page-break">

<div style="text-align:left; margin-bottom:4px; font-size:8pt;">
    BP No.: <span class="uline">&nbsp;{{ $application->bp_number ?? '___________________________' }}&nbsp;</span>
</div>

<div class="bx">
    <div style="font-weight:bold; font-size:7.5pt; background:#e0e0e0; padding:2px 4px; border-bottom:1px solid #000;">BOX 6 (TO BE ACCOMPLISHED BY THE PROCESSING AND EVALUATION DIVISION)</div>

    <table class="ft">
        <thead>
            <tr>
                <th style="width:28%;">ASSESSED FEES</th>
                <th style="width:16%;">ACCOUNT</th>
                <th style="width:18%;">BASIS OF ASSESSMENT</th>
                <th style="width:14%;">AMOUNT DUE</th>
                <th style="width:24%;">ASSESSED BY</th>
            </tr>
        </thead>
        <tbody>
            <tr class="sh"><td colspan="5" class="sh">FOR ZONING (ZONING ADMINISTRATOR):</td></tr>
            <tr><td>&nbsp;&nbsp;&#9675; LOCATIONAL / ZONING OF LAND</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr class="sh"><td colspan="5" class="sh">FOR BUILDING / STRUCTURE (OBO):</td></tr>
            @foreach(['FILING FEE','LINE AND GRADE (Geodetic)','FENCING','ARCHITECTURAL','CIVIL / STRUCTURAL','ELECTRICAL','MECHANICAL','SANITARY','PLUMBING','ELECTRONICS','INTERIOR','SURCHARGES','PENALTIES'] as $fee)
            <tr><td>&nbsp;&nbsp;&#9675; {{ $fee }}</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endforeach
            <tr class="sh"><td colspan="5" class="sh">FOR FIRE SAFETY (BFP):</td></tr>
            <tr><td>&nbsp;&nbsp;&#9675; FIRE CODE CONSTRUCTION TAX</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;&nbsp;&#9675; HOTWORKS</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr style="font-weight:bold;">
                <td colspan="3" style="text-align:center; padding:3px; font-size:8pt;">T O T A L</td>
                <td>&nbsp;</td><td>&nbsp;</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Terms --}}
<div class="bx" style="margin-top:-1px;">
    <div style="font-weight:bold; font-size:7.5pt; padding:2px 4px; border-bottom:1px solid #000;">TERMS AND CONDITIONS:</div>
    <div style="font-size:7pt; line-height:1.35; padding:3px 5px;">
        <p style="margin:2px 0;">1. &nbsp; The Owner/Applicant shall accomplish the prescribed Application Form, with the assistance of the concerned design professional/s and/or the Architect/Civil Engineer, hired/commissioned by him/her as full-time inspector/supervisor of the construction works, by filling up the necessary data / information required thereat.</p>
        <p style="margin:2px 0;">2. &nbsp; The fully accomplished prescribed Application Form, duly notarized, shall be submitted to the concerned Office of the Building Official, accompanied by the various applicable ancillary and accessory permits, plans and specifications signed and sealed by the corresponding design professionals who shall be responsible for the comprehensive and correctness of the plans in compliance to the National Building Code of the Philippines (PD 1096), its Revised IRR and all applicable referral codes and professional regulatory laws, together with the other documentary requirements pursuant to Section 302 of PD 1096 and its Revised IRR.</p>
    </div>
</div>

{{-- Data Privacy --}}
<div class="bx" style="margin-top:-1px;">
    <div style="font-size:7.5pt; line-height:1.4; padding:6px 8px;">
        <p style="margin:2px 0;">I have read this form, understood its contents and consent to the processing of my personal data. I understand that my consent does not preclude the existence of other criteria for lawful processing of personal data, and does not waive any of my rights under the Data Privacy Act of 2012 and other applicable laws.</p>
        <div style="text-align:center; margin-top:18px;">
            <div style="border-top:1px solid #000; display:inline-block; width:55%; padding-top:2px; font-size:7pt;">
                SIGNATURE OVER PRINTED NAME OF OWNER/APPLICANT
            </div>
        </div>
    </div>
</div>

</div>{{-- end page 2 --}}

</body>
</html>
