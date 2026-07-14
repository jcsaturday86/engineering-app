@php
    $mi = $application->applicant_middle_name ? mb_substr($application->applicant_middle_name, 0, 1) . '.' : '';
    $tc = fn (?string $s) => $s ? mb_convert_case(mb_strtolower($s), MB_CASE_TITLE, 'UTF-8') : '';

    $scopeId = $application->scope_of_work_id;
    $sk = fn ($id) => $scopeId == $id ? '&#10004;' : '';

    // dompdf's overflow:hidden clipping is unreliable on absolutely positioned text, so
    // very tight cells are hard-truncated here instead of relying on CSS to clip them.
    $trunc = fn (?string $s, int $len) => $s ? \Illuminate\Support\Str::limit($s, $len, '') : '';

    $buildingPermitNo = $application->permits->first()?->permit_number;
    $boFullName = trim(($boTitle ?? '') . ' ' . ($boName ?? ''));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Mechanical Permit - {{ $application->application_number }}</title>
    <style>
        @media print {
            @page { size: 8.5in 14in; margin: 0; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; color: #000; }
        .print-page {
            position: relative;
            width: 8.5in; height: 14in;
            background-color: #fff;
            background-size: 8.5in 14in;
            background-repeat: no-repeat;
            background-position: top left;
            overflow: hidden;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
        .p1 { background-image: url('{{ public_path('images/forms/mechanical-p1.jpg') }}'); }
        .p2 { background-image: url('{{ public_path('images/forms/mechanical-p2.jpg') }}'); }

        .f {
            position: absolute;
            font: 8pt/1.1 Arial, sans-serif;
            white-space: nowrap;
        }
        .c {
            position: absolute;
            width: 0.18in;
            height: 0.19in;
            line-height: 0.19in;
            text-align: center;
            font: bold 8pt/1 'DejaVu Sans', Arial, sans-serif;
        }
        .mask { position: absolute; background: #fff; }
        .ctr { text-align: center; }
        .sm { font-size: 7pt; }
        .clip { overflow: hidden; text-overflow: ellipsis; }
        .hdr { position: absolute; top:0; left:0; width:8.5in; text-align:center; font: 8pt/1.1 Arial, sans-serif; }
    </style>
</head>
<body>

{{-- ======================== PAGE 1 ======================== --}}
<div class="print-page p1">

    {{-- Letterhead: sits BELOW "NBC FORM NO. A-04" (y:0.31-0.41) and above
         "OFFICE OF THE BUILDING OFFICIAL" (y:1.27-1.37). --}}
    @if($sealImage ?? null)
    <img src="{{ $sealImage }}" alt="Official Seal" style="display:block; position:absolute; top:0.50in; left:0.35in; width:1.0in; height:1.0in;">
    @endif
    @if($nationalGovtLogo ?? null)
    <img src="{{ $nationalGovtLogo }}" alt="National Government Logo" style="display:block; position:absolute; top:0.50in; left:7.15in; width:1.0in; height:1.0in;">
    @endif
    <div class="f ctr" style="top:0.60in; left:0; width:8.5in; font-size:11pt;">Republic of the Philippines</div>
    <div class="f ctr" style="top:0.79in; left:0; width:8.5in; font-size:11pt; font-weight:bold;">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>
    <div class="f ctr" style="top:0.98in; left:0; width:8.5in; font-size:11pt;">Province of {{ $settings['general.province'] ?? 'La Union' }}</div>

    {{-- Top: Application No. / Building Permit No. (MP No. left blank — no such column exists).
         A white mask sits behind the value to hide the pre-printed per-digit cell dividers,
         since the values don't align to individual digit boxes. --}}
    <div class="mask" style="top:2.08in; left:0.22in; width:1.76in; height:0.20in;"></div>
    <div class="f ctr" style="top:2.13in; left:0.22in; width:1.76in;">{{ $application->application_number }}</div>
    <div class="mask" style="top:2.08in; left:3.46in; width:1.8in; height:0.20in;"></div>
    <div class="mask" style="top:2.08in; left:6.51in; width:1.76in; height:0.20in;"></div>
    <div class="f ctr" style="top:2.13in; left:6.51in; width:1.76in;">{{ $buildingPermitNo }}</div>

    {{-- BOX 1: Owner/Applicant --}}
    <div class="f clip" style="top:3.05in; left:2.0in; max-width:1.9in;">{{ $application->applicant_last_name }}</div>
    <div class="f clip" style="top:3.05in; left:4.0in; max-width:2.1in;">{{ $application->applicant_first_name }}</div>
    <div class="f" style="top:3.05in; left:6.18in;">{{ $mi }}</div>
    <div class="f clip" style="top:3.05in; left:6.6in; max-width:1.6in;">{{ $application->applicant_tin ?? '' }}</div>

    {{-- Form of Ownership / Use or Character of Occupancy --}}
    <div class="f clip" style="top:3.51in; left:2.35in; max-width:2.3in;">{{ $application->formOfOwnership?->name }}</div>
    <div class="f clip" style="top:3.51in; left:4.8in; max-width:3.4in;">{{ $application->occupancy_classified ?? '' }}</div>

    {{-- Address --}}
    <div class="f clip sm" style="top:3.85in; left:0.75in; max-width:4.9in;">{{ $trunc($tc($application->applicant_street), 30) . ($application->applicantBarangay ? ', ' . $tc($application->applicantBarangay->name) : '') }}</div>
    <div class="f clip sm" style="top:3.85in; left:5.75in; max-width:0.65in;">{{ $application->applicant_zip_code ?? '' }}</div>
    <div class="f clip sm" style="top:3.85in; left:6.6in; max-width:1.6in;">{{ $application->applicant_contact_no ?? '' }}</div>

    {{-- Location of Construction: Street / Barangay (Lot/Blk/TCT/Tax Dec and City are not backed by dedicated columns / are pre-printed) --}}
    <div class="f clip sm" style="top:4.36in; left:0.75in; max-width:0.85in;">{{ $trunc($application->building_street, 16) }}</div>
    <div class="f clip sm" style="top:4.36in; left:2.35in; max-width:1.95in;">{{ $tc($application->buildingBarangay?->name) }}</div>

    {{-- Scope of Work checkboxes (Box 1) — mapped against the seeded scope_of_works table --}}
    @if($sk(1))<div class="c" style="top:4.74in; left:0.39in;">&#10004;</div>@endif   {{-- New Construction --}}
    @if($sk(3))<div class="c" style="top:4.74in; left:2.30in;">&#10004;</div>@endif   {{-- Renovation --}}
    @if($sk(7))<div class="c" style="top:4.74in; left:5.44in;">&#10004;</div>@endif   {{-- Raising --}}
    @if($sk(11))<div class="c" style="top:5.03in; left:0.39in;">&#10004;</div>@endif  {{-- Erection --}}
    @if($sk(5))<div class="c" style="top:5.03in; left:2.30in;">&#10004;</div>@endif   {{-- Conversion --}}
    @if($sk(9))<div class="c" style="top:5.03in; left:5.44in;">&#10004;</div>@endif   {{-- Demolition --}}
    @if($sk(2))<div class="c" style="top:5.32in; left:0.39in;">&#10004;</div>@endif   {{-- Addition --}}
    @if($sk(6))<div class="c" style="top:5.32in; left:2.30in;">&#10004;</div>@endif   {{-- Repair --}}
    @if($sk(10))<div class="c" style="top:5.32in; left:5.44in;">&#10004;</div>@endif  {{-- Accessory Structure --}}
    @if($sk(4))<div class="c" style="top:5.61in; left:0.39in;">&#10004;</div>@endif   {{-- Alteration --}}
    @if($sk(8))<div class="c" style="top:5.61in; left:2.30in;">&#10004;</div>@endif   {{-- Moving --}}
    @if($sk(13))<div class="c" style="top:5.61in; left:5.44in;">&#10004;</div>@endif  {{-- Others --}}

    {{-- Box 2 (Installation and Operation of...) and Box 3/4 (Design Professional / Supervisor of Mechanical Works)
         are left blank — no backing columns exist for per-installation-type checkboxes, a Professional
         Mechanical Engineer block, or a Supervisor/In-Charge of Mechanical Works block. --}}

    {{-- BOX 5: Building Owner --}}
    <div class="f ctr" style="top:12.10in; left:0.3in; width:3.5in; font-weight:bold;">{{ strtoupper(trim($application->applicant_first_name . ' ' . $mi . ' ' . $application->applicant_last_name)) }}</div>
    <div class="f" style="top:12.66in; left:1.65in;">{{ optional($application->applicant_date_signed)->format('m/d/Y') }}</div>
    <div class="f clip" style="top:13.15in; left:0.3in; max-width:3.5in;">{{ $trunc($tc($application->applicant_street), 45) }}</div>
    <div class="f clip" style="top:13.60in; left:0.25in; max-width:1.0in;">{{ $application->applicant_govt_id ?? '' }}</div>
    <div class="f clip" style="top:13.60in; left:1.32in; max-width:1.1in;">{{ optional($application->applicant_id_date_issued)->format('m/d/Y') }}</div>
    <div class="f clip" style="top:13.60in; left:2.51in; max-width:1.45in;">{{ $application->applicant_id_place_issued ?? '' }}</div>

    {{-- BOX 6: With My Consent — Lot Owner --}}
    <div class="f ctr" style="top:12.10in; left:4.8in; width:3.4in; font-weight:bold;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f" style="top:12.66in; left:6.2in;">{{ optional($application->owner_date_signed)->format('m/d/Y') }}</div>
    <div class="f clip" style="top:13.15in; left:4.8in; max-width:3.4in;">{{ $application->owner_address ?? '' }}</div>
    <div class="f clip" style="top:13.60in; left:4.77in; max-width:0.95in;">{{ $application->owner_govt_id ?? '' }}</div>
    <div class="f clip" style="top:13.60in; left:5.84in; max-width:1.0in;">{{ optional($application->owner_id_date_issued)->format('m/d/Y') }}</div>
    <div class="f clip" style="top:13.60in; left:6.91in; max-width:1.35in;">{{ $application->owner_id_place_issued ?? '' }}</div>

</div>{{-- end page 1 --}}

{{-- ======================== PAGE 2 ======================== --}}
{{-- Box 7 (Received By/document checklist) and Box 8 (Progress Flow table) are left blank —
     internal-office-only boxes with no backing data, same convention as the other discipline forms. --}}
<div class="print-page p2 page-break">

    {{-- BOX 9: Permit Issued By — Building Official --}}
    <div class="f ctr" style="top:10.55in; left:2.37in; width:3.5in; font-weight:bold;">{{ strtoupper($boFullName) }}</div>
    <div class="f ctr" style="top:10.75in; left:2.37in; width:3.5in;">{{ strtoupper($boDesignation ?? '') }}</div>

</div>

</body>
</html>
