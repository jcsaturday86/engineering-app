@php
    $mi = $application->applicant_middle_name ? mb_substr($application->applicant_middle_name, 0, 1) . '.' : '';
    $tc = fn (?string $s) => $s ? mb_convert_case(mb_strtolower($s), MB_CASE_TITLE, 'UTF-8') : '';

    $scopeId = $application->scope_of_work_id;
    $sk = fn ($id) => $scopeId == $id ? '&#10004;' : '';

    $primaryOccupancy = $application->applicationOccupancyGroups->first()?->occupancyGroup?->name ?? '';
    $buildingPermitNo = $application->permits->first()?->permit_number ?? '';
    // $boTitle / $boName / $boDesignation come from the controller: the generated Permit's
    // building-official snapshot if one exists, otherwise the currently-active Signatory.
    $boFullName = trim(($boTitle ?? '') . ' ' . ($boName ?? ''));

    // dompdf's overflow:hidden clipping is unreliable on absolutely positioned text, so
    // very tight cells are hard-truncated here instead of relying on CSS to clip them.
    $trunc = fn (?string $s, int $len) => $s ? \Illuminate\Support\Str::limit($s, $len, '') : '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Civil/Structural Permit - {{ $application->application_number }}</title>
    <style>
        @media print {
            @page { size: 8.5in 13in; margin: 0; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; color: #000; }
        .print-page {
            position: relative;
            width: 8.5in; height: 13in;
            background-color: #fff;
            background-size: 8.5in 13in;
            background-repeat: no-repeat;
            background-position: top left;
            overflow: hidden;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
        .p1 { background-image: url('{{ public_path('images/forms/structural-p1.jpg') }}'); }
        .p2 { background-image: url('{{ public_path('images/forms/structural-p2.jpg') }}'); }

        .f {
            position: absolute;
            font: 8pt/1.1 Arial, sans-serif;
            white-space: nowrap;
        }
        .c {
            position: absolute;
            width: 0.25in;
            height: 0.18in;
            line-height: 0.18in;
            text-align: center;
            font: bold 10pt/1 'DejaVu Sans', Arial, sans-serif;
        }
        .ctr { text-align: center; }
        .sm { font-size: 7pt; }
        .clip { overflow: hidden; text-overflow: ellipsis; }
        .hdr { position: absolute; top:0; left:0; width:8.5in; text-align:center; font: 12.5pt/1.3 Arial, sans-serif; }
    </style>
</head>
<body>

{{-- ======================== PAGE 1 ======================== --}}
<div class="print-page p1">

    {{-- Letterhead: Official city seal (left), National Government logo (right).
         Both sit BELOW the pre-printed "NBC FORM NO. A-07" label (y:0.56-0.60in)
         and above the "CIVIL/STRUCTURAL PERMIT" title (starts y:1.50in). --}}
    @if($sealImage ?? null)
    <img src="{{ $sealImage }}" alt="Official Seal" style="position:absolute; top:0.65in; left:0.35in; width:0.75in; height:0.75in;">
    @endif
    @if($nationalGovtLogo ?? null)
    <img src="{{ $nationalGovtLogo }}" alt="National Government Logo" style="position:absolute; top:0.65in; left:7.12in; width:0.75in; height:0.75in;">
    @endif
    <div class="hdr" style="top:0.32in;">Republic of the Philippines</div>
    <div class="hdr" style="top:0.56in; font-weight:bold;">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>
    <div class="hdr" style="top:0.80in;">Province of {{ $settings['general.province'] ?? 'La Union' }}</div>

    {{-- Top: Application No. / C-SP No. / Building Permit No. --}}
    <div class="f ctr" style="top:1.97in; left:0.40in; width:2.35in;">{{ $application->application_number }}</div>
    <div class="f ctr" style="top:1.97in; left:5.26in; width:2.81in;">{{ $buildingPermitNo }}</div>

    {{-- BOX 1: Owner/Applicant (values sit on the blank line BELOW each label) --}}
    <div class="f clip" style="top:2.70in; left:2.11in; max-width:1.90in; font-size:10pt;">{{ $application->applicant_last_name }}</div>
    <div class="f clip" style="top:2.70in; left:4.09in; max-width:1.80in; font-size:10pt;">{{ $application->applicant_first_name }}</div>
    <div class="f clip" style="top:2.70in; left:5.97in; max-width:0.55in; font-size:10pt;">{{ $mi }}</div>
    <div class="f clip" style="top:2.70in; left:6.61in; max-width:1.40in; font-size:10pt;">{{ $application->applicant_tin ?? '' }}</div>

    {{-- Enterprise name / Form of ownership / Use or character of occupancy (line 2 of row 2) --}}
    <div class="f clip sm" style="top:3.36in; left:1.62in; max-width:1.30in;">{{ $application->enterprise_name ?? '' }}</div>
    <div class="f clip" style="top:3.35in; left:3.06in; max-width:2.10in;">{{ $application->formOfOwnership?->name ?? '' }}</div>
    <div class="f clip" style="top:3.35in; left:5.30in; max-width:2.65in;">{{ $primaryOccupancy }}</div>

    {{-- ADDRESS: values sit on the blank line BELOW the "ADDRESS: NO. STREET BARANGAY / CITY.../ ZIP CODE / TEL. NO." labels --}}
    <div class="f" style="top:3.75in; left:1.45in; font-size:9pt;">{{ $trunc($tc($application->applicant_street), 22) }}</div>
    <div class="f clip" style="top:3.75in; left:5.30in; max-width:1.30in; font-size:9pt;">{{ $application->applicant_zip_code ?? '' }}</div>
    <div class="f clip" style="top:3.75in; left:6.61in; max-width:1.40in; font-size:9pt;">{{ $application->applicant_contact_no ?? '' }}</div>

    {{-- Location of Construction (values sit inline on the pre-printed blank line) --}}
    <div class="f clip" style="top:4.24in; left:1.00in; max-width:1.70in;">{{ $application->lot_no ?? '' }}</div>
    <div class="f clip" style="top:4.24in; left:3.60in; max-width:0.65in;">{{ $application->block_no ?? '' }}</div>
    <div class="f clip" style="top:4.24in; left:4.90in; max-width:1.00in;">{{ $application->tct_no ?? '' }}</div>
    <div class="f clip" style="top:4.24in; left:6.85in; max-width:1.15in;">{{ $application->tax_dec_no ?? '' }}</div>
    <div class="f clip" style="top:4.40in; left:1.00in; max-width:0.90in;">{{ $application->building_street ?? '' }}</div>
    <div class="f clip" style="top:4.40in; left:2.85in; max-width:2.10in;">{{ $application->buildingBarangay?->name ?? '' }}</div>

    {{-- Scope of Work (mapped to scope_of_work_id: 1=New Construction, 2=Addition, 3=Renovation, 4=Alteration, 5=Conversion)
         Checkbox rows (col1 x:0.71): New Installation y:4.74 / Erection y:5.00 / Addition y:5.26 / Alteration y:5.515
         Checkbox col2 (x:2.75): Renovation y:4.74 / Conversion y:5.00 --}}
    @if($sk(1))<div class="c" style="top:4.76in; left:0.71in;">&#10004;</div>@endif
    @if($sk(2))<div class="c" style="top:5.28in; left:0.71in;">&#10004;</div>@endif
    @if($sk(4))<div class="c" style="top:5.535in; left:0.71in;">&#10004;</div>@endif
    @if($sk(3))<div class="c" style="top:4.76in; left:2.75in;">&#10004;</div>@endif
    @if($sk(5))<div class="c" style="top:5.02in; left:2.75in;">&#10004;</div>@endif

    {{-- BOX 3: Design Professional (Civil/Structural Engineer) — left blank; the plans may be signed
         and sealed by a professional different from the engineer on record, filled in by hand. --}}

    {{-- BOX 4: Supervision/In-charge of Civil/Structural Works (engineer on record) —
         name sits on the blank line above the pre-printed "CIVIL/STRUCTURAL ENGINEER" caption. --}}
    <div class="f ctr" style="top:7.88in; left:4.27in; width:3.79in; font-weight:bold;">{{ strtoupper($application->engineer_name ?? '') }}</div>
    <div class="f clip" style="top:8.66in; left:4.88in; max-width:3.15in;">{{ $application->engineer_address ?? '' }}</div>
    <div class="f clip" style="top:9.02in; left:4.80in; max-width:1.10in;">{{ $application->engineer_prc_no ?? '' }}</div>
    <div class="f clip" style="top:9.02in; left:6.58in; max-width:1.45in;">{{ $application->engineer_prc_validity?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:9.38in; left:4.80in; max-width:1.10in;">{{ $application->engineer_ptr_no ?? '' }}</div>
    <div class="f clip" style="top:9.38in; left:6.78in; max-width:1.25in;">{{ $application->engineer_ptr_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:9.75in; left:4.93in; max-width:0.95in;">{{ $application->engineer_ptr_issued_at ?? '' }}</div>
    <div class="f clip" style="top:9.75in; left:6.19in; max-width:1.85in;">{{ $application->engineer_tin ?? '' }}</div>

    {{-- BOX 5: Building Owner (Applicant on record) — name sits on the blank line
         above the "(Signature Over Printed Name)" caption. CTC row values sit below
         their labels (no ruled sub-dividers in that row on this form). --}}
    <div class="f ctr" style="top:10.55in; left:0.40in; width:3.65in; font-weight:bold;">{{ strtoupper(trim($application->applicant_first_name . ' ' . $mi . ' ' . $application->applicant_last_name)) }}</div>
    <div class="f clip" style="top:11.18in; left:1.03in; max-width:2.95in;">{{ $trunc($tc($application->applicant_street), 30) }}</div>
    <div class="f clip" style="top:11.72in; left:0.45in; max-width:0.90in; font-size:8pt;">{{ $application->applicant_govt_id ?? '' }}</div>
    <div class="f clip" style="top:11.72in; left:1.48in; max-width:1.15in; font-size:8pt;">{{ $application->applicant_id_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:11.72in; left:2.80in; max-width:1.15in; font-size:8pt;">{{ $application->applicant_id_place_issued ?? '' }}</div>

    {{-- BOX 6: With My Consent — Lot Owner --}}
    <div class="f ctr" style="top:10.55in; left:4.27in; width:3.79in; font-weight:bold;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f clip" style="top:11.18in; left:4.90in; max-width:2.95in;">{{ $trunc($application->owner_address, 30) }}</div>
    <div class="f clip" style="top:11.72in; left:4.32in; max-width:0.90in; font-size:8pt;">{{ $application->owner_govt_id ?? '' }}</div>
    <div class="f clip" style="top:11.72in; left:5.31in; max-width:1.15in; font-size:8pt;">{{ $application->owner_id_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:11.72in; left:6.66in; max-width:1.35in; font-size:8pt;">{{ $application->owner_id_place_issued ?? '' }}</div>

</div>{{-- end page 1 --}}

{{-- ======================== PAGE 2 ======================== --}}
{{-- Internal office-processing checklist (Boxes 7-9) — no application data to overlay,
     except the "PERMIT ISSUED BY:" signatory block (Box 9): the generated Permit's
     building-official snapshot, or the currently-active Signatory if no Permit yet. --}}
<div class="print-page p2 page-break">
    @if($boFullName !== '')
    <div class="f ctr" style="top:9.00in; left:0.7in; width:6.9in; font-weight:bold; font-size:10pt;">{{ strtoupper($boFullName) }}</div>
    <div class="f ctr" style="top:9.25in; left:0.7in; width:6.9in; font-size:9pt;">{{ strtoupper($boDesignation) }}</div>
    @endif
</div>

</body>
</html>
