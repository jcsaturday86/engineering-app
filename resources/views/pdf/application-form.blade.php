@php
    $selectedOccupancy = $application->applicationOccupancyGroups->pluck('occupancy_sub_group_id')->toArray();
    $othersText = $application->applicationOccupancyGroups->pluck('others_text', 'occupancy_sub_group_id')->toArray();
    $scopeId = $application->scope_of_work_id;
    $scopeDetails = $application->scope_of_work_details;

    // Checkmark helpers: render a mark only when true — the box outline is part of the
    // background form image.
    $ck = function($id) use ($selectedOccupancy) {
        return in_array($id, $selectedOccupancy) ? '&#10004;' : '';
    };
    $ot = function($id) use ($othersText) {
        return $othersText[$id] ?? '';
    };
    $sk = function($id) use ($scopeId) {
        return $scopeId == $id ? '&#10004;' : '';
    };
    $sd = function($id) use ($scopeId, $scopeDetails) {
        return $scopeId == $id && $scopeDetails ? $scopeDetails : '';
    };

    $mi = $application->applicant_middle_name ? mb_substr($application->applicant_middle_name, 0, 1) . '.' : '';
    $complexity = $application->complexity ?? '';
    $appTypeName = $application->applicationType->name ?? '';
    $appliesTo = $application->applies_to ?? '';
    $isOp = $application->permitType->code === 'OP';
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
            body { background: #9e9e9e; padding-top: 52px; }
            .print-page { margin: 10px auto; box-shadow: 1px 1px 3px 1px #333; }
        }
        @media print {
            .print-toolbar { display: none !important; }
            body { background: #fff; padding: 0; }
            .print-page { margin: 0; box-shadow: none; }
            .page-break { page-break-before: always; }
            @page { size: 8.5in 13in; margin: 0; }
        }

        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; color: #000; }

        /* The source form scan is 8.5in x 14in (Legal) with a blank bottom margin;
           printing on 8.5x13 long bond keeps 1:1 scale and crops only blank space. */
        .print-page {
            position: relative;
            width: 8.5in; height: 13in;
            background-color: #fff;
            background-size: 8.5in 14in;
            background-repeat: no-repeat;
            background-position: top left;
            overflow: hidden;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
        .p1 { background-image: url('{{ asset('images/forms/unified-bp-form-p1.png') }}'); }
        {{-- Page 2 source scan is exactly 8.5in x 13in (no blank Legal-size margin to crop) --}}
        .p2 { background-image: url('{{ asset('images/forms/unified-bp-form-p2.png') }}'); background-size: 8.5in 13in; }

        /* Overlay field: absolutely positioned dynamic value */
        .f {
            position: absolute;
            font: 8pt/1.1 Arial, sans-serif;
            white-space: nowrap;
        }
        /* Checkmark inside a pre-printed checkbox */
        .c {
            position: absolute;
            font: bold 9pt/1 Arial, sans-serif;
        }
        .ctr { text-align: center; }
        .sm { font-size: 7pt; }
        .clip { overflow: hidden; text-overflow: ellipsis; }
        .hdr { position: absolute; top:0; left:0; width:8.5in; text-align:center; font: 9.5pt/1.35 Arial, sans-serif; }
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
<div class="print-page p1">

    {{-- Letterhead: Official city seal (left), Republic/City/Province (center), National Government Logo (right) --}}
    @if($sealImage ?? null)
    <img src="{{ $sealImage }}" alt="Official Seal" style="position:absolute; top:0.04in; left:0.35in; width:0.72in; height:0.72in;">
    @endif
    @if($nationalGovtLogo ?? null)
    <img src="{{ $nationalGovtLogo }}" alt="National Government Logo" style="position:absolute; top:0.04in; left:7.43in; width:0.72in; height:0.72in;">
    @endif
    <div class="hdr" style="top:0.08in;">Republic of the Philippines</div>
    <div class="hdr" style="top:0.25in; font-weight:bold;">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>
    <div class="hdr" style="top:0.42in;">Province of {{ $settings['general.province'] ?? 'La Union' }}</div>

    {{-- Simple / Complex --}}
    @if($complexity === 'Simple')<div class="c" style="top:1.01in; left:2.77in;">&#10004;</div>@endif
    @if($complexity === 'Complex')<div class="c" style="top:1.01in; left:4.72in;">&#10004;</div>@endif

    {{-- New / Renewal / Amendatory (BP only — OP has no matching boxes on this form) --}}
    @unless($isOp)
        @if($appTypeName === 'New')<div class="c" style="top:1.24in; left:2.77in;">&#10004;</div>@endif
        @if($appTypeName === 'Renewal')<div class="c" style="top:1.24in; left:3.74in;">&#10004;</div>@endif
        @if($appTypeName === 'Amendatory')<div class="c" style="top:1.24in; left:4.72in;">&#10004;</div>@endif
    @endunless

    {{-- Applies also for --}}
    @if($appliesTo !== 'SKIP_LC')<div class="c" style="top:1.47in; left:2.81in;">&#10004;</div>@endif
    @if($application->fsec_no)<div class="c" style="top:1.47in; left:4.76in;">&#10004;</div>@endif

    {{-- Application No. / Area No. digit boxes --}}
    <div class="f ctr" style="top:1.87in; left:0.40in; width:1.49in; font-size:8pt;">{{ $application->application_number }}</div>
    <div class="f ctr" style="top:1.87in; left:6.63in; width:1.46in; font-size:8pt;">{{ $application->area_number ?: ($settings['general.area_number'] ?? '') }}</div>

    {{-- BOX 1: Owner / Applicant --}}
    <div class="f" style="top:2.36in; left:1.60in;">{{ $application->applicant_last_name }}</div>
    <div class="f" style="top:2.36in; left:3.25in;">{{ $application->applicant_first_name }}</div>
    <div class="f" style="top:2.36in; left:4.95in;">{{ $mi }}</div>
    <div class="f" style="top:2.36in; left:5.50in;">{{ $application->applicant_tin ?? '' }}</div>

    {{-- Form of Ownership. The "FOR CONSTRUCTION OWNED BY AN ENTERPRISE" cell to its left is a
         2-line label that fills its entire cell on the printed form (measured: label glyphs span
         the full cell height/width) — there is no blank space left to overlay the enterprise
         name there without printing over the label text, so it is not rendered on this page. --}}
    <div class="f" style="top:2.72in; left:2.78in;">{{ $application->formOfOwnership?->name ?? '' }}</div>

    {{-- Address row --}}
    <div class="f" style="top:2.98in; left:0.65in; max-width:4.0in;">{{ $application->applicant_street }}, {{ $application->applicantBarangay?->name }}, {{ $application->applicantCity?->name }}</div>
    <div class="f" style="top:2.98in; left:4.80in;">{{ $application->applicant_zip_code ?? '' }}</div>
    <div class="f" style="top:2.98in; left:5.50in;">{{ $application->applicant_contact_no ?? '' }}</div>

    {{-- Location of Construction --}}
    <div class="f" style="top:3.16in; left:2.35in;">{{ $application->lot_no ?? '' }}</div>
    <div class="f" style="top:3.16in; left:3.12in;">{{ $application->block_no ?? '' }}</div>
    <div class="f sm" style="top:3.17in; left:3.92in; max-width:0.95in; overflow:hidden;">{{ $application->tct_no ?? '' }}</div>
    <div class="f" style="top:3.16in; left:5.80in;">{{ $application->tax_dec_no ?? '' }}</div>
    <div class="f" style="top:3.36in; left:0.90in;">{{ $application->building_street ?? '' }}</div>
    <div class="f" style="top:3.36in; left:2.50in;">{{ $application->buildingBarangay?->name ?? '' }}</div>
    <div class="f" style="top:3.36in; left:4.95in;">SAN FERNANDO, LA UNION</div>

    {{-- Scope of Work (col1 / col2 / col3) --}}
    @if($sk(1))<div class="c" style="top:3.68in; left:0.51in;">&#10004;</div>@endif
    @if($sk(11))<div class="c" style="top:3.85in; left:0.51in;">&#10004;</div>@endif
    @if($sk(2))<div class="c" style="top:4.01in; left:0.51in;">&#10004;</div>@endif
    @if($sk(4))<div class="c" style="top:4.18in; left:0.51in;">&#10004;</div>@endif
    <div class="f sm" style="top:3.85in; left:1.05in;">{{ $sd(11) }}</div>
    <div class="f sm" style="top:4.01in; left:1.02in;">{{ $sd(2) }}</div>
    <div class="f sm" style="top:4.18in; left:1.12in;">{{ $sd(4) }}</div>

    @if($sk(3))<div class="c" style="top:3.68in; left:2.33in;">&#10004;</div>@endif
    @if($sk(5))<div class="c" style="top:3.85in; left:2.33in;">&#10004;</div>@endif
    @if($sk(6))<div class="c" style="top:4.01in; left:2.33in;">&#10004;</div>@endif
    @if($sk(8))<div class="c" style="top:4.18in; left:2.33in;">&#10004;</div>@endif
    <div class="f sm" style="top:3.68in; left:3.20in;">{{ $sd(3) }}</div>
    <div class="f sm" style="top:3.85in; left:3.22in;">{{ $sd(5) }}</div>
    <div class="f sm" style="top:4.01in; left:3.05in;">{{ $sd(6) }}</div>
    <div class="f sm" style="top:4.18in; left:3.05in;">{{ $sd(8) }}</div>

    @if($sk(7))<div class="c" style="top:3.68in; left:4.21in;">&#10004;</div>@endif
    @if($sk(10))<div class="c" style="top:3.85in; left:4.21in;">&#10004;</div>@endif
    @if($sk(12))<div class="c" style="top:4.01in; left:4.21in;">&#10004;</div>@endif
    @if($sk(13))<div class="c" style="top:4.18in; left:4.21in;">&#10004;</div>@endif
    <div class="f sm" style="top:3.68in; left:4.85in;">{{ $sd(7) }}</div>
    <div class="f sm" style="top:3.85in; left:6.00in;">{{ $sd(10) }}</div>
    <div class="f sm" style="top:4.01in; left:6.25in;">{{ $sd(12) }}</div>
    <div class="f sm" style="top:4.18in; left:5.30in;">{{ $sd(13) }}</div>

    {{-- Use or Character of Occupancy — Column 1 (Groups A-D) --}}
    @if($ck(1))<div class="c" style="top:4.69in; left:0.65in;">&#10004;</div>@endif
    @if($ck(2))<div class="c" style="top:4.69in; left:1.27in;">&#10004;</div>@endif
    @if($ck(3))<div class="c" style="top:4.69in; left:1.84in;">&#10004;</div>@endif
    @if($ck(4))<div class="c" style="top:4.80in; left:0.65in;">&#10004;</div>@endif
    <div class="f sm" style="top:4.81in; left:1.05in;">{{ $ot(4) }}</div>
    @if($ck(5))<div class="c" style="top:5.02in; left:0.65in;">&#10004;</div>@endif
    @if($ck(6))<div class="c" style="top:5.02in; left:1.13in;">&#10004;</div>@endif
    @if($ck(7))<div class="c" style="top:5.02in; left:1.82in;">&#10004;</div>@endif
    @if($ck(8))<div class="c" style="top:5.14in; left:0.65in;">&#10004;</div>@endif
    @if($ck(9))<div class="c" style="top:5.14in; left:1.82in;">&#10004;</div>@endif
    @if($ck(10))<div class="c" style="top:5.25in; left:0.65in;">&#10004;</div>@endif
    @if($ck(11))<div class="c" style="top:5.37in; left:0.65in;">&#10004;</div>@endif
    <div class="f sm" style="top:5.38in; left:1.05in;">{{ $ot(11) }}</div>
    @if($ck(12))<div class="c" style="top:5.61in; left:0.65in;">&#10004;</div>@endif
    @if($ck(13))<div class="c" style="top:5.58in; left:1.82in;">&#10004;</div>@endif
    @if($ck(14))<div class="c" style="top:5.72in; left:0.65in;">&#10004;</div>@endif
    @if($ck(15))<div class="c" style="top:5.84in; left:0.65in;">&#10004;</div>@endif
    @if($ck(16))<div class="c" style="top:5.81in; left:1.82in;">&#10004;</div>@endif
    @if($ck(17))<div class="c" style="top:5.95in; left:0.65in;">&#10004;</div>@endif
    <div class="f sm" style="top:5.96in; left:1.10in;">{{ $ot(17) }}</div>
    @if($ck(18))<div class="c" style="top:6.19in; left:0.65in;">&#10004;</div>@endif
    @if($ck(19))<div class="c" style="top:6.31in; left:0.65in;">&#10004;</div>@endif
    @if($ck(20))<div class="c" style="top:6.44in; left:0.65in;">&#10004;</div>@endif
    @if($ck(21))<div class="c" style="top:6.56in; left:0.65in;">&#10004;</div>@endif
    <div class="f sm" style="top:6.57in; left:1.05in;">{{ $ot(21) }}</div>

    {{-- Column 2 (Groups E-G) --}}
    @if($ck(22))<div class="c" style="top:4.68in; left:3.03in;">&#10004;</div>@endif
    @if($ck(23))<div class="c" style="top:4.68in; left:3.59in;">&#10004;</div>@endif
    @if($ck(24))<div class="c" style="top:4.68in; left:4.11in;">&#10004;</div>@endif
    @if($ck(25))<div class="c" style="top:4.80in; left:3.03in;">&#10004;</div>@endif
    @if($ck(26))<div class="c" style="top:5.02in; left:3.03in;">&#10004;</div>@endif
    @if($ck(27))<div class="c" style="top:5.25in; left:3.03in;">&#10004;</div>@endif
    <div class="f sm" style="top:5.26in; left:3.45in;">{{ $ot(27) }}</div>
    @if($ck(28))<div class="c" style="top:5.49in; left:3.03in;">&#10004;</div>@endif
    @if($ck(29))<div class="c" style="top:5.72in; left:3.03in;">&#10004;</div>@endif
    <div class="f sm" style="top:5.73in; left:3.45in;">{{ $ot(29) }}</div>
    @if($ck(30))<div class="c" style="top:5.97in; left:3.03in;">&#10004;</div>@endif
    @if($ck(31))<div class="c" style="top:6.20in; left:3.03in;">&#10004;</div>@endif
    @if($ck(32))<div class="c" style="top:6.43in; left:3.03in;">&#10004;</div>@endif
    <div class="f sm" style="top:6.44in; left:3.45in;">{{ $ot(32) }}</div>

    {{-- Column 3 (Groups H-J) --}}
    @if($ck(33))<div class="c" style="top:4.73in; left:5.10in;">&#10004;</div>@endif
    @if($ck(34))<div class="c" style="top:4.96in; left:5.10in;">&#10004;</div>@endif
    <div class="f sm" style="top:4.97in; left:5.52in;">{{ $ot(34) }}</div>
    @if($ck(35))<div class="c" style="top:5.31in; left:5.10in;">&#10004;</div>@endif
    @if($ck(36))<div class="c" style="top:5.58in; left:5.10in;">&#10004;</div>@endif
    <div class="f sm" style="top:5.59in; left:5.52in;">{{ $ot(36) }}</div>
    @if($ck(37))<div class="c" style="top:5.90in; left:5.10in;">&#10004;</div>@endif
    @if($ck(38))<div class="c" style="top:6.07in; left:5.10in;">&#10004;</div>@endif
    <div class="f sm" style="top:6.08in; left:5.52in;">{{ $ot(38) }}</div>
    @if($ck(39))<div class="c" style="top:6.32in; left:5.10in;">&#10004;</div>@endif
    @if($ck(40))<div class="c" style="top:6.63in; left:5.10in;">&#10004;</div>@endif
    <div class="f sm" style="top:6.64in; left:5.52in;">{{ $ot(40) }}</div>

    {{-- Occupancy stats (bottom-left) --}}
    <div class="f" style="top:6.73in; left:1.55in;">{{ $application->occupancy_classified ?? '' }}</div>
    <div class="f" style="top:6.86in; left:1.45in;">{{ $application->no_of_units ?? '' }}</div>
    <div class="f" style="top:6.99in; left:1.45in;">{{ $application->no_of_storeys ?? '' }}</div>
    <div class="f" style="top:7.11in; left:1.35in;">{{ $application->total_floor_area ? number_format($application->total_floor_area, 2) : '' }}</div>
    <div class="f" style="top:7.24in; left:0.95in;">{{ $application->lot_area ? number_format($application->lot_area, 2) : '' }}</div>

    {{-- Estimated costs (bottom-right) --}}
    <div class="f" style="top:6.73in; left:4.30in; font-weight:bold;">{{ number_format($application->total_estimated_cost, 2) }}</div>
    <div class="f" style="top:6.86in; left:3.65in;">{{ number_format($application->building_cost, 2) }}</div>
    <div class="f" style="top:6.99in; left:3.65in;">{{ number_format($application->electrical_cost, 2) }}</div>
    <div class="f" style="top:7.11in; left:3.65in;">{{ number_format($application->mechanical_cost, 2) }}</div>
    <div class="f" style="top:7.24in; left:3.65in;">{{ number_format($application->electronics_cost, 2) }}</div>
    <div class="f" style="top:7.37in; left:3.65in;">{{ number_format($application->plumbing_cost, 2) }}</div>
    <div class="f" style="top:6.99in; left:5.30in;">{{ $application->equipment_cost_1 ? number_format($application->equipment_cost_1, 2) : '' }}</div>
    <div class="f" style="top:7.11in; left:5.30in;">{{ $application->equipment_cost_2 ? number_format($application->equipment_cost_2, 2) : '' }}</div>
    <div class="f" style="top:7.24in; left:5.30in;">{{ $application->equipment_cost_3 ? number_format($application->equipment_cost_3, 2) : '' }}</div>
    <div class="f" style="top:7.37in; left:5.30in;">{{ $application->equipment_cost_4 ? number_format($application->equipment_cost_4, 2) : '' }}</div>

    {{-- Construction dates --}}
    <div class="f" style="top:7.48in; left:2.10in;">{{ $application->proposed_construction_date?->format('F d, Y') ?? '' }}</div>
    <div class="f" style="top:7.48in; left:5.25in;">{{ $application->expected_completion_date?->format('F d, Y') ?? '' }}</div>

    {{-- BOX 2: Full-time Inspector / Supervisor --}}
    <div class="f ctr" style="top:8.29in; left:0.91in; width:2.91in; font-weight:bold;">{{ strtoupper($application->engineer_name ?? '') }}</div>
    <div class="f" style="top:8.72in; left:1.85in;">{{ $application->engineer_date_signed?->format('F d, Y') ?? '' }}</div>
    <div class="f" style="top:8.08in; left:4.95in; max-width:3.0in; overflow:hidden;">{{ $application->engineer_address ?? '' }}</div>
    <div class="f" style="top:8.37in; left:5.00in;">{{ $application->engineer_prc_no ?? '' }}</div>
    <div class="f sm" style="top:8.38in; left:6.80in;">{{ $application->engineer_prc_validity?->format('m/d/Y') ?? '' }}</div>
    <div class="f" style="top:8.54in; left:5.00in;">{{ $application->engineer_ptr_no ?? '' }}</div>
    <div class="f sm" style="top:8.55in; left:6.80in;">{{ $application->engineer_ptr_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:8.71in; left:4.90in; max-width:1.28in; font-size:6.5pt;">{{ $application->engineer_ptr_issued_at ?? '' }}</div>
    <div class="f sm" style="top:8.71in; left:6.55in;">{{ $application->engineer_tin ?? '' }}</div>

    {{-- BOX 3: Applicant --}}
    <div class="f ctr" style="top:9.36in; left:0.66in; width:2.49in; font-weight:bold;">{{ strtoupper(trim($application->applicant_first_name . ' ' . $mi . ' ' . $application->applicant_last_name)) }}</div>
    <div class="f" style="top:9.38in; left:3.58in;">{{ $application->applicant_date_signed?->format('m/d/Y') ?? '' }}</div>
    <div class="f" style="top:9.71in; left:0.85in; max-width:3.4in; overflow:hidden;">{{ $application->applicant_street }}, {{ $application->applicantBarangay?->name }}, {{ $application->applicantCity?->name }}</div>
    <div class="f clip" style="top:9.90in; left:1.25in; max-width:1.15in; font-size:7pt;">{{ $application->applicant_govt_id ?? '' }}</div>
    <div class="f sm" style="top:9.90in; left:2.50in;">{{ $application->applicant_id_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:9.90in; left:3.60in; max-width:0.66in; font-size:6pt;">{{ $application->applicant_id_place_issued ?? '' }}</div>

    {{-- BOX 4: Lot Owner / Authorized Representative --}}
    <div class="f ctr" style="top:9.36in; left:4.55in; width:2.35in; font-weight:bold;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f" style="top:9.38in; left:7.32in;">{{ $application->owner_date_signed?->format('m/d/Y') ?? '' }}</div>
    <div class="f" style="top:9.71in; left:4.75in; max-width:3.3in; overflow:hidden;">{{ $application->owner_address ?? '' }}</div>
    <div class="f clip" style="top:9.90in; left:5.08in; max-width:0.58in; font-size:5.5pt;">{{ $application->owner_govt_id ?? '' }}</div>
    <div class="f sm" style="top:9.90in; left:6.00in;">{{ $application->owner_id_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:9.90in; left:7.33in; max-width:0.72in; font-size:6pt;">{{ $application->owner_id_place_issued ?? '' }}</div>

    {{-- BOX 5 (notarial) is completed by hand — no overlay fields --}}

</div>{{-- end page 1 --}}

{{-- ======================== PAGE 2 ======================== --}}
{{-- Box 6 (assessed fees) and Terms and Conditions are part of the background form image.
     The signature line at the bottom is for the Owner/Applicant (not the Building Official). --}}
<div class="print-page p2 page-break">
    <div class="f ctr" style="top:12.32in; left:4.765in; width:3.225in; font-size:12px; font-weight:bold;">
        {{ strtoupper(trim($application->applicant_first_name . ' ' . $mi . ' ' . $application->applicant_last_name)) }}
    </div>
</div>

</body>
</html>
