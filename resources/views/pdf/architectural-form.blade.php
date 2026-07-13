@php
    $mi = $application->applicant_middle_name ? mb_substr($application->applicant_middle_name, 0, 1) . '.' : '';
    $tc = fn (?string $s) => $s ? mb_convert_case(mb_strtolower($s), MB_CASE_TITLE, 'UTF-8') : '';
    $applicantAddress = trim(collect([
        $tc($application->applicant_street),
        $tc($application->applicantBarangay?->name),
        $tc($application->applicantCity?->name),
    ])->filter()->implode(', '), ', ');

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
    <title>Architectural Permit - {{ $application->application_number }}</title>
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
        .p1 { background-image: url('{{ public_path('images/forms/architectural-p1.jpg') }}'); }
        .p2 { background-image: url('{{ public_path('images/forms/architectural-p2.jpg') }}'); }

        .f {
            position: absolute;
            font: 8pt/1.1 Arial, sans-serif;
            white-space: nowrap;
        }
        .c {
            position: absolute;
            width: 0.17in;
            height: 0.15in;
            line-height: 0.15in;
            text-align: center;
            font: bold 9pt/1 'DejaVu Sans', Arial, sans-serif;
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
         Both sit BELOW the pre-printed "NBC FORM NO. A-01" label (x:0.37-1.5in, y:0.29-0.37in)
         and above the "APPLICATION NO." row (starts y:1.52in) — the title/subtitle in between
         is horizontally centered, so it doesn't intersect either side margin. --}}
    @if($sealImage ?? null)
    <img src="{{ $sealImage }}" alt="Official Seal" style="position:absolute; top:0.45in; left:0.35in; width:0.825in; height:0.825in;">
    @endif
    @if($nationalGovtLogo ?? null)
    <img src="{{ $nationalGovtLogo }}" alt="National Government Logo" style="position:absolute; top:0.45in; left:7.02in; width:0.825in; height:0.825in;">
    @endif
    <div class="hdr" style="top:0.08in;">Republic of the Philippines</div>
    <div class="hdr" style="top:0.32in; font-weight:bold;">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>
    <div class="hdr" style="top:0.56in;">Province of {{ $settings['general.province'] ?? 'La Union' }}</div>

    {{-- Top: Application No. / AP No. / Building Permit No. --}}
    <div class="f ctr" style="top:1.72in; left:0.40in; width:2.0in;">{{ $application->application_number }}</div>
    <div class="f ctr" style="top:1.72in; left:5.55in; width:2.55in;">{{ $buildingPermitNo }}</div>

    {{-- BOX 1: Owner/Applicant (values sit on the blank line BELOW each label) --}}
    <div class="f clip" style="top:2.46in; left:2.04in; max-width:1.95in; font-size:10pt;">{{ $application->applicant_last_name }}</div>
    <div class="f clip" style="top:2.46in; left:4.12in; max-width:2.05in; font-size:10pt;">{{ $application->applicant_first_name }}</div>
    <div class="f clip" style="top:2.46in; left:6.32in; max-width:0.30in; font-size:10pt;">{{ $mi }}</div>
    <div class="f clip" style="top:2.46in; left:6.66in; max-width:1.45in; font-size:10pt;">{{ $application->applicant_tin ?? '' }}</div>

    {{-- Enterprise name / Form of ownership / Use or character of occupancy (line 2 of each cell) --}}
    <div class="f clip sm" style="top:2.96in; left:1.55in; max-width:1.15in;">{{ $application->enterprise_name ?? '' }}</div>
    <div class="f clip" style="top:2.95in; left:2.8in; max-width:2.6in;">{{ $application->formOfOwnership?->name ?? '' }}</div>
    <div class="f clip" style="top:2.95in; left:5.55in; max-width:2.55in;">{{ $primaryOccupancy }}</div>

    {{-- ADDRESS: values sit on the blank line BELOW the "ADDRESS: NO. STREET BARANGAY / CITY.../ ZIP CODE / TEL. NO." labels --}}
    <div class="f" style="top:3.27in; left:1.40in; font-size:9pt;">{{ $trunc($tc($application->applicant_street), 22) }}</div>
    <div class="f clip" style="top:3.27in; left:5.54in; max-width:1.05in; font-size:9pt;">{{ $application->applicant_zip_code ?? '' }}</div>
    <div class="f clip" style="top:3.27in; left:6.66in; max-width:1.45in; font-size:9pt;">{{ $application->applicant_contact_no ?? '' }}</div>

    {{-- Location of Construction --}}
    <div class="f clip" style="top:3.65in; left:0.90in; max-width:0.85in;">{{ $application->lot_no ?? '' }}</div>
    <div class="f clip" style="top:3.65in; left:3.00in; max-width:0.45in;">{{ $application->block_no ?? '' }}</div>
    <div class="f clip" style="top:3.65in; left:4.28in; max-width:1.0in;">{{ $application->tct_no ?? '' }}</div>
    <div class="f clip" style="top:3.65in; left:6.43in; max-width:1.5in;">{{ $application->tax_dec_no ?? '' }}</div>
    <div class="f clip" style="top:3.87in; left:0.95in; max-width:0.65in;">{{ $application->building_street ?? '' }}</div>
    <div class="f clip" style="top:3.87in; left:2.81in; max-width:1.9in;">{{ $application->buildingBarangay?->name ?? '' }}</div>

    {{-- Scope of Work (mapped to scope_of_work_id: 1=New Construction, 2=Addition, 3=Renovation, 4=Alteration, 5=Conversion) --}}
    @if($sk(1))<div class="c" style="top:4.24in; left:0.64in;">&#10004;</div>@endif
    @if($sk(2))<div class="c" style="top:4.75in; left:0.64in;">&#10004;</div>@endif
    @if($sk(4))<div class="c" style="top:5.0in; left:0.64in;">&#10004;</div>@endif
    @if($sk(3))<div class="c" style="top:4.24in; left:2.8in;">&#10004;</div>@endif
    @if($sk(5))<div class="c" style="top:4.5in; left:2.8in;">&#10004;</div>@endif

    {{-- BOX 3: Design Professional (Architect) — left blank; the plans may be signed and sealed
         by a different architect than the engineer on record, so this is filled in by hand. --}}

    {{-- BOX 4: Supervision/In-charge of Civil-Structural Works (Architect — same professional on record) --}}
    <div class="f ctr" style="top:9.28in; left:4.35in; width:3.6in; font-weight:bold;">{{ strtoupper($application->engineer_name ?? '') }}</div>
    <div class="f clip" style="top:9.98in; left:4.92in; max-width:1.3in;">{{ $application->engineer_address ?? '' }}</div>
    <div class="f clip" style="top:10.16in; left:4.85in; max-width:1.35in;">{{ $application->engineer_prc_no ?? '' }}</div>
    <div class="f clip" style="top:10.16in; left:6.94in; max-width:1.15in;">{{ $application->engineer_prc_validity?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:10.345in; left:4.85in; max-width:1.35in;">{{ $application->engineer_ptr_no ?? '' }}</div>
    <div class="f clip" style="top:10.345in; left:7.15in; max-width:0.9in;">{{ $application->engineer_ptr_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:10.525in; left:5.01in; max-width:1.2in;">{{ $application->engineer_ptr_issued_at ?? '' }}</div>
    <div class="f clip" style="top:10.525in; left:6.55in; max-width:1.55in;">{{ $application->engineer_tin ?? '' }}</div>

    {{-- BOX 5: Building Owner (Applicant on record) --}}
    <div class="f ctr" style="top:11.10in; left:0.40in; width:3.6in; font-weight:bold;">{{ strtoupper(trim($application->applicant_first_name . ' ' . $mi . ' ' . $application->applicant_last_name)) }}</div>
    <div class="f clip" style="top:11.62in; left:0.97in; max-width:2.9in;">{{ $applicantAddress }}</div>
    <div class="f clip sm" style="top:11.80in; left:0.90in; max-width:0.45in;">{{ $application->applicant_govt_id ?? '' }}</div>
    <div class="f clip sm" style="top:11.80in; left:2.24in; max-width:0.21in;">{{ $application->applicant_id_date_issued?->format('m/d/y') ?? '' }}</div>
    <div class="f clip sm" style="top:11.80in; left:3.39in; max-width:0.66in;">{{ $application->applicant_id_place_issued ?? '' }}</div>

    {{-- BOX 6: With My Consent — Lot Owner --}}
    <div class="f ctr" style="top:11.10in; left:4.35in; width:3.6in; font-weight:bold;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f clip" style="top:11.62in; left:4.92in; max-width:2.9in;">{{ $application->owner_address ?? '' }}</div>
    {{-- CTC No. / Date Issued / Place Issued sit on the blank line BELOW their labels to make room for longer values --}}
    <div class="f clip" style="top:11.97in; left:4.40in; max-width:1.00in; font-size:8pt;">{{ $application->owner_govt_id ?? '' }}</div>
    <div class="f clip" style="top:11.97in; left:5.54in; max-width:1.02in; font-size:8pt;">{{ $application->owner_id_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:11.97in; left:6.70in; max-width:1.40in; font-size:8pt;">{{ $application->owner_id_place_issued ?? '' }}</div>

</div>{{-- end page 1 --}}

{{-- ======================== PAGE 2 ======================== --}}
{{-- Internal office-processing checklist (Boxes 7-9) — no application data to overlay,
     except the "PERMIT ISSUED BY:" signatory block (Box 9): the generated Permit's
     building-official snapshot, or the currently-active Signatory if no Permit yet. --}}
<div class="print-page p2 page-break">
    @if($boFullName !== '')
    <div class="f ctr" style="top:11.05in; left:0.7in; width:6.9in; font-weight:bold; font-size:10pt;">{{ strtoupper($boFullName) }}</div>
    <div class="f ctr" style="top:11.28in; left:0.7in; width:6.9in; font-size:9pt;">{{ strtoupper($boDesignation) }}</div>
    @endif
</div>

</body>
</html>
