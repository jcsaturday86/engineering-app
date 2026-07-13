@php
    $mi = $application->applicant_middle_name ? mb_substr($application->applicant_middle_name, 0, 1) . '.' : '';
    $tc = fn (?string $s) => $s ? mb_convert_case(mb_strtolower($s), MB_CASE_TITLE, 'UTF-8') : '';

    $scopeId = $application->scope_of_work_id;
    $sk = fn ($id) => $scopeId == $id ? '&#10004;' : '';

    // dompdf's overflow:hidden clipping is unreliable on absolutely positioned text, so
    // very tight cells are hard-truncated here instead of relying on CSS to clip them.
    $trunc = fn (?string $s, int $len) => $s ? \Illuminate\Support\Str::limit($s, $len, '') : '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sanitary/Plumbing Permit - {{ $application->application_number }}</title>
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
        .p1 { background-image: url('{{ public_path('images/forms/sanitary-p1.jpg') }}'); }
        .p2 { background-image: url('{{ public_path('images/forms/sanitary-p2.jpg') }}'); }

        .f {
            position: absolute;
            font: 8pt/1.1 Arial, sans-serif;
            white-space: nowrap;
        }
        .c {
            position: absolute;
            width: 0.26in;
            height: 0.13in;
            line-height: 0.13in;
            text-align: center;
            font: bold 9pt/1 'DejaVu Sans', Arial, sans-serif;
        }
        .ctr { text-align: center; }
        .sm { font-size: 7pt; }
        .clip { overflow: hidden; text-overflow: ellipsis; }
        .hdr { position: absolute; top:0; left:0; width:8.5in; text-align:center; font: 8pt/1.1 Arial, sans-serif; }
    </style>
</head>
<body>

{{-- ======================== PAGE 1 ======================== --}}
<div class="print-page p1">

    {{-- Letterhead: sits BELOW "FORM NO. 77-001-S" (y:0.52-0.60) and above
         "OFFICE OF THE BUILDING OFFICIAL" (y:1.18-1.26). --}}
    @if($sealImage ?? null)
    <img src="{{ $sealImage }}" alt="Official Seal" style="display:block; position:absolute; top:0.61in; left:0.35in; width:0.50in; height:0.50in;">
    @endif
    @if($nationalGovtLogo ?? null)
    <img src="{{ $nationalGovtLogo }}" alt="National Government Logo" style="display:block; position:absolute; top:0.61in; left:7.37in; width:0.50in; height:0.50in;">
    @endif
    <div class="hdr" style="top:0.60in;">Republic of the Philippines</div>
    <div class="hdr" style="top:0.76in; font-weight:bold;">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>
    <div class="hdr" style="top:0.92in;">Province of {{ $settings['general.province'] ?? 'La Union' }}</div>

    {{-- Top: Application No. --}}
    <div class="f ctr" style="top:1.55in; left:0.30in; width:1.35in;">{{ $application->application_number }}</div>

    {{-- BOX 1: Owner/Applicant (values sit on the blank line BELOW each label) --}}
    <div class="f clip" style="top:2.58in; left:1.73in; max-width:2.35in; font-size:9pt;">{{ $application->applicant_last_name }}</div>
    <div class="f clip" style="top:2.58in; left:4.21in; max-width:1.65in; font-size:9pt;">{{ $application->applicant_first_name }}</div>
    <div class="f clip" style="top:2.58in; left:5.98in; max-width:0.75in; font-size:9pt;">{{ $mi }}</div>
    <div class="f clip" style="top:2.58in; left:6.86in; max-width:1.05in; font-size:9pt;">{{ $application->applicant_tin ?? '' }}</div>

    {{-- ADDRESS row (below label) --}}
    <div class="f sm" style="top:2.96in; left:1.05in;">{{ $trunc($tc($application->applicant_street), 20) }}</div>
    <div class="f clip sm" style="top:2.96in; left:3.21in; max-width:1.50in;">{{ $tc($application->applicantBarangay?->name) }}</div>
    <div class="f clip sm" style="top:2.96in; left:4.81in; max-width:1.95in;">{{ $tc($application->applicantCity?->name) }}</div>
    <div class="f clip sm" style="top:2.96in; left:6.86in; max-width:1.05in;">{{ $application->applicant_contact_no ?? '' }}</div>

    {{-- LOCATION OF INSTALLATION row (below label) --}}
    <div class="f sm" style="top:3.34in; left:2.01in;">{{ $trunc($application->building_street, 16) }}</div>
    <div class="f clip sm" style="top:3.34in; left:4.21in; max-width:1.20in;">{{ $tc($application->buildingBarangay?->name) }}</div>
    <div class="f clip sm" style="top:3.34in; left:5.49in; max-width:1.30in;">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>

    {{-- Scope of Work — only "NEW INSTALLATION" maps to an existing scope_of_work_id (1 = New Construction). --}}
    @if($sk(1))<div class="c" style="top:3.66in; left:0.53in;">&#10004;</div>@endif

    {{-- Number of Storeys / Total Area / Proposed & Expected Dates / Total Cost of Installation --}}
    <div class="f" style="top:9.68in; left:0.30in;">{{ $application->no_of_storeys ?? '' }}</div>
    <div class="f clip" style="top:9.68in; left:4.48in; max-width:1.40in;">{{ $application->total_floor_area ?? '' }}</div>
    <div class="f clip" style="top:9.97in; left:1.56in; max-width:1.00in; font-size:7pt;">{{ $application->proposed_construction_date?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:9.97in; left:5.44in; max-width:1.00in; font-size:7pt;">{{ $application->plumbing_cost ? number_format($application->plumbing_cost, 2) : '' }}</div>
    <div class="f clip" style="top:10.25in; left:1.12in; max-width:1.00in; font-size:7pt;">{{ $application->expected_completion_date?->format('m/d/Y') ?? '' }}</div>

</div>{{-- end page 1 --}}

{{-- ======================== PAGE 2 ======================== --}}
{{-- Box 6 "SANITARY ENGINEER/MASTER PLUMBER SIGNED AND SEALED PLANS SPECIFICATIONS" and
     Box 7 "...IN-CHARGE OF INSTALLATION" are both left blank — the plans/installation may be
     signed by a professional different from the engineer of record, same rationale as the
     other discipline forms' Design Professional box.
     Box 8 is the Applicant's own signature/CTC block. --}}
<div class="print-page p2 page-break">

    {{-- BOX 8: Applicant signature + CTC — centered within the box's actual width (0.20in-4.82in) --}}
    <div class="f ctr" style="top:9.35in; left:0.20in; width:4.62in; font-weight:bold;">{{ strtoupper(trim($application->applicant_first_name . ' ' . $mi . ' ' . $application->applicant_last_name)) }}</div>
    <div class="f clip" style="top:10.10in; left:0.20in; max-width:1.47in; font-size:7pt;">{{ $application->applicant_govt_id ?? '' }}</div>
    <div class="f clip" style="top:10.10in; left:1.57in; max-width:1.76in; font-size:7pt;">{{ $application->applicant_id_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:10.10in; left:3.42in; max-width:1.35in; font-size:7pt;">{{ $application->applicant_id_place_issued ?? '' }}</div>

</div>

</body>
</html>
