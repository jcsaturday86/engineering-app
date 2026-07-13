@php
    $mi = $application->applicant_middle_name ? mb_substr($application->applicant_middle_name, 0, 1) . '.' : '';
    $tc = fn (?string $s) => $s ? mb_convert_case(mb_strtolower($s), MB_CASE_TITLE, 'UTF-8') : '';

    $scopeId = $application->scope_of_work_id;
    $sk = fn ($id) => $scopeId == $id ? '&#10004;' : '';

    $buildingPermitNo = $application->permits->first()?->permit_number ?? '';
    // $boTitle / $boName / $boDesignation come from the controller: the generated Permit's
    // building-official snapshot if one exists, otherwise the currently-active Signatory.
    $boFullName = trim(($boTitle ?? '') . ' ' . ($boName ?? ''));

    // dompdf's overflow:hidden clipping is unreliable on absolutely positioned text, so
    // very tight cells are hard-truncated here instead of relying on CSS to clip them.
    $trunc = fn (?string $s, int $len) => $s ? \Illuminate\Support\Str::limit($s, $len, '') : '';

    $kva = fn ($v) => $v !== null ? rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.') : '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Electrical Permit - {{ $application->application_number }}</title>
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
        .p1 { background-image: url('{{ public_path('images/forms/electrical-p1.jpg') }}'); }
        .p2 { background-image: url('{{ public_path('images/forms/electrical-p2.jpg') }}'); }

        .f {
            position: absolute;
            font: 8pt/1.1 Arial, sans-serif;
            white-space: nowrap;
        }
        .c {
            position: absolute;
            width: 0.20in;
            height: 0.16in;
            line-height: 0.16in;
            text-align: center;
            font: bold 9pt/1 'DejaVu Sans', Arial, sans-serif;
        }
        .ctr { text-align: center; }
        .sm { font-size: 7pt; }
        .clip { overflow: hidden; text-overflow: ellipsis; }
        .hdr { position: absolute; top:0; left:0; width:8.5in; text-align:center; font: 9pt/1.25 Arial, sans-serif; }
    </style>
</head>
<body>

{{-- ======================== PAGE 1 ======================== --}}
<div class="print-page p1">

    {{-- Letterhead: sits BELOW the pre-printed "FORM NO. 77-001-S" label (y:0.52-0.60)
         and above "OFFICE OF THE BUILDING OFFICIAL" (y:1.30-1.40). --}}
    @if($sealImage ?? null)
    <img src="{{ $sealImage }}" alt="Official Seal" style="display:block; position:absolute; top:0.64in; left:0.35in; width:0.55in; height:0.55in;">
    @endif
    @if($nationalGovtLogo ?? null)
    <img src="{{ $nationalGovtLogo }}" alt="National Government Logo" style="display:block; position:absolute; top:0.64in; left:7.32in; width:0.55in; height:0.55in;">
    @endif
    <div class="hdr" style="top:0.64in;">Republic of the Philippines</div>
    <div class="hdr" style="top:0.83in; font-weight:bold;">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>
    <div class="hdr" style="top:1.02in;">Province of {{ $settings['general.province'] ?? 'La Union' }}</div>

    {{-- Top: Application No. / Electrical Permit No. / Building Permit No. --}}
    <div class="f ctr" style="top:1.70in; left:0.40in; width:1.74in;">{{ $application->application_number }}</div>
    <div class="f ctr" style="top:1.70in; left:5.89in; width:2.15in;">{{ $buildingPermitNo }}</div>

    {{-- BOX 1: Owner/Applicant (values sit on the blank line BELOW each label) --}}
    <div class="f clip" style="top:2.72in; left:1.72in; max-width:1.60in; font-size:10pt;">{{ $application->applicant_last_name }}</div>
    <div class="f clip" style="top:2.72in; left:3.40in; max-width:1.75in; font-size:10pt;">{{ $application->applicant_first_name }}</div>
    <div class="f clip" style="top:2.72in; left:5.23in; max-width:0.85in; font-size:10pt;">{{ $mi }}</div>
    <div class="f clip" style="top:2.72in; left:6.16in; max-width:1.80in; font-size:10pt;">{{ $application->applicant_tin ?? '' }}</div>

    {{-- Enterprise name / Form of ownership (line 2 of row 2; occupancy has no equivalent field mapped here) --}}
    <div class="f clip sm" style="top:3.28in; left:1.62in; max-width:1.45in;">{{ $application->enterprise_name ?? '' }}</div>
    <div class="f clip" style="top:3.28in; left:3.09in; max-width:1.30in;">{{ $application->formOfOwnership?->name ?? '' }}</div>

    {{-- ADDRESS: value sits on the blank line BELOW the label (this form has no ZIP CODE column) --}}
    <div class="f" style="top:3.60in; left:1.30in; font-size:9pt;">{{ $trunc($tc($application->applicant_street), 20) }}</div>
    <div class="f clip" style="top:3.60in; left:6.48in; max-width:1.55in; font-size:9pt;">{{ $application->applicant_contact_no ?? '' }}</div>

    {{-- Location of Construction (values sit inline on the pre-printed blank line;
         CITY/MUNICIPALITY here is pre-printed static text, not a field) --}}
    <div class="f clip" style="top:3.86in; left:2.68in; max-width:0.40in;">{{ $application->lot_no ?? '' }}</div>
    <div class="f clip" style="top:3.86in; left:3.62in; max-width:0.35in;">{{ $application->block_no ?? '' }}</div>
    <div class="f clip" style="top:3.86in; left:4.46in; max-width:0.90in;">{{ $application->tct_no ?? '' }}</div>
    <div class="f clip" style="top:3.86in; left:6.20in; max-width:1.75in;">{{ $application->tax_dec_no ?? '' }}</div>
    <div class="f clip" style="top:4.20in; left:0.90in; max-width:1.50in;">{{ $application->building_street ?? '' }}</div>
    <div class="f clip" style="top:4.20in; left:3.15in; max-width:1.95in;">{{ $application->buildingBarangay?->name ?? '' }}</div>

    {{-- Scope of Work — only "NEW INSTALLATION" maps to an existing scope_of_work_id (1 = New Construction);
         Reconnection/Relocation/Annual Inspection/Separation/Temporary/Upgrading have no equivalent field. --}}
    @if($sk(1))<div class="c" style="top:4.61in; left:0.58in;">&#10004;</div>@endif

    {{-- Summary of Electrical Loads/Capacities --}}
    <div class="f ctr" style="top:5.86in; left:0.60in; width:1.90in; font-size:9pt;">{{ $kva($application->total_connected_load) }}</div>
    <div class="f ctr" style="top:5.86in; left:2.75in; width:1.90in; font-size:9pt;">{{ $kva($application->total_transformer_capacity) }}</div>
    <div class="f ctr" style="top:5.86in; left:5.75in; width:1.95in; font-size:9pt;">{{ $kva($application->total_generator_capacity) }}</div>

    {{-- BOX 2: Design Professional — Professional Electrical Engineer (pee_* fields).
         Name sits on the blank line above the "PROFESSIONAL ELECTRICAL ENGINEER" caption. --}}
    <div class="f ctr" style="top:6.95in; left:0.40in; width:3.65in; font-weight:bold;">{{ strtoupper($application->pee_name ?? '') }}</div>
    <div class="f clip" style="top:7.08in; left:4.75in; max-width:3.25in;">{{ $application->pee_address ?? '' }}</div>
    <div class="f clip" style="top:7.24in; left:4.68in; max-width:1.40in;">{{ $application->pee_prc_no ?? '' }}</div>
    <div class="f clip" style="top:7.24in; left:6.78in; max-width:1.25in;">{{ $application->pee_prc_validity?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:7.40in; left:4.68in; max-width:1.40in;">{{ $application->pee_ptr_no ?? '' }}</div>
    <div class="f clip" style="top:7.40in; left:7.03in; max-width:1.00in;">{{ $application->pee_ptr_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:7.56in; left:4.84in; max-width:1.25in;">{{ $application->pee_ptr_issued_at ?? '' }}</div>
    <div class="f clip" style="top:7.56in; left:6.43in; max-width:1.60in;">{{ $application->pee_tin ?? '' }}</div>

    {{-- BOX 3: Supervisor of Electrical Works — reuses the generic engineer_* fields
         (no dedicated supervisor data exists separate from the PEE above), matching the
         same "supervision/in-charge" convention used on the Architectural/Structural forms.
         Name sits on the blank line above the "(Signature Over Printed Name)" caption. --}}
    <div class="f ctr" style="top:8.60in; left:0.40in; width:7.65in; font-weight:bold;">{{ strtoupper($application->engineer_name ?? '') }}</div>
    <div class="f clip" style="top:9.05in; left:1.03in; max-width:3.00in;">{{ $application->engineer_address ?? '' }}</div>
    <div class="f clip" style="top:9.21in; left:0.96in; max-width:3.05in;">{{ $application->engineer_prc_no ?? '' }}</div>
    <div class="f clip" style="top:9.21in; left:4.76in; max-width:3.20in;">{{ $application->engineer_prc_validity?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:9.37in; left:0.96in; max-width:3.05in;">{{ $application->engineer_ptr_no ?? '' }}</div>
    <div class="f clip" style="top:9.37in; left:5.01in; max-width:2.95in;">{{ $application->engineer_ptr_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:9.54in; left:1.12in; max-width:2.90in;">{{ $application->engineer_ptr_issued_at ?? '' }}</div>
    <div class="f clip" style="top:9.54in; left:4.41in; max-width:3.55in;">{{ $application->engineer_tin ?? '' }}</div>

    {{-- BOX 4: Building Owner/Applicant --}}
    <div class="f ctr" style="top:10.50in; left:0.39in; width:3.55in; font-weight:bold;">{{ strtoupper(trim($application->applicant_first_name . ' ' . $mi . ' ' . $application->applicant_last_name)) }}</div>
    <div class="f clip" style="top:11.21in; left:0.95in; max-width:2.90in;">{{ $trunc($tc($application->applicant_street), 30) }}</div>
    <div class="f clip" style="top:11.55in; left:0.44in; max-width:0.80in; font-size:8pt;">{{ $application->applicant_govt_id ?? '' }}</div>
    <div class="f clip" style="top:11.55in; left:1.35in; max-width:1.00in; font-size:8pt;">{{ $application->applicant_id_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:11.55in; left:2.47in; max-width:1.40in; font-size:8pt;">{{ $application->applicant_id_place_issued ?? '' }}</div>

    {{-- BOX 5: With My Consent — Lot Owner --}}
    <div class="f ctr" style="top:10.50in; left:4.13in; width:3.90in; font-weight:bold;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f clip" style="top:11.21in; left:4.69in; max-width:3.30in;">{{ $trunc($application->owner_address, 30) }}</div>
    <div class="f clip" style="top:11.55in; left:4.18in; max-width:1.15in; font-size:8pt;">{{ $application->owner_govt_id ?? '' }}</div>
    <div class="f clip" style="top:11.55in; left:5.43in; max-width:1.25in; font-size:8pt;">{{ $application->owner_id_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:11.55in; left:6.77in; max-width:1.25in; font-size:8pt;">{{ $application->owner_id_place_issued ?? '' }}</div>

</div>{{-- end page 1 --}}

{{-- ======================== PAGE 2 ======================== --}}
{{-- Internal office-processing checklist (Boxes 6-8) — no application data to overlay,
     except the "PERMIT ISSUED BY:" signatory block (Box 8): the generated Permit's
     building-official snapshot, or the currently-active Signatory if no Permit yet. --}}
<div class="print-page p2 page-break">
    @if($boFullName !== '')
    <div class="f ctr" style="top:10.05in; left:0.7in; width:6.9in; font-weight:bold; font-size:10pt;">{{ strtoupper($boFullName) }}</div>
    <div class="f ctr" style="top:10.30in; left:0.7in; width:6.9in; font-size:9pt;">{{ strtoupper($boDesignation) }}</div>
    @endif
</div>

</body>
</html>
