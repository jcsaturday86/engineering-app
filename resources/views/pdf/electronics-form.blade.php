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
    <title>Electronics Permit - {{ $application->application_number }}</title>
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
        .p1 { background-image: url('{{ public_path('images/forms/electronics-p1.jpg') }}'); }
        .p2 { background-image: url('{{ public_path('images/forms/electronics-p2.jpg') }}'); }

        .f {
            position: absolute;
            font: 8pt/1.1 Arial, sans-serif;
            white-space: nowrap;
        }
        .c {
            position: absolute;
            width: 0.12in;
            height: 0.13in;
            line-height: 0.13in;
            text-align: center;
            font: bold 8pt/1 'DejaVu Sans', Arial, sans-serif;
        }
        .mask { position: absolute; background: #fff; }
        .ctr { text-align: center; }
        .sm { font-size: 7pt; }
        .clip { overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>

{{-- ======================== PAGE 1 ======================== --}}
<div class="print-page p1">

    {{-- Top: Application No. / Building Permit No. (ELP No. left blank — no such column exists) --}}
    <div class="f ctr" style="top:2.28in; left:0.16in; width:1.94in;">{{ $application->application_number }}</div>
    <div class="f ctr" style="top:2.28in; left:6.76in; width:1.56in;">{{ $buildingPermitNo }}</div>

    {{-- BOX 1: Owner/Applicant --}}
    <div class="f clip" style="top:3.10in; left:2.49in; max-width:2.15in;">{{ $application->applicant_last_name }}</div>
    <div class="f clip" style="top:3.10in; left:4.72in; max-width:1.75in;">{{ $application->applicant_first_name }}</div>
    <div class="f" style="top:3.10in; left:6.54in;">{{ $mi }}</div>
    <div class="f clip" style="top:3.10in; left:6.98in; max-width:1.25in;">{{ $application->applicant_tin ?? '' }}</div>

    {{-- Form of Ownership / Use or Character of Occupancy --}}
    <div class="f clip" style="top:3.52in; left:3.32in; max-width:1.4in;">{{ $application->formOfOwnership?->name }}</div>
    <div class="f clip" style="top:3.52in; left:5.76in; max-width:1.25in;">{{ $application->occupancy_classified ?? '' }}</div>

    {{-- Address --}}
    <div class="f clip sm" style="top:3.95in; left:0.85in; max-width:4.7in;">{{ $trunc($tc($application->applicant_street), 25) . ($application->applicantBarangay ? ', ' . $tc($application->applicantBarangay->name) : '') . ($application->applicantCity ? ', ' . $tc($application->applicantCity->name) : '') }}</div>
    <div class="f clip sm" style="top:3.95in; left:5.75in; max-width:0.6in;">{{ $application->applicant_zip_code ?? '' }}</div>
    <div class="f clip sm" style="top:3.95in; left:6.45in; max-width:1.7in;">{{ $application->applicant_contact_no ?? '' }}</div>

    {{-- Location of Construction: Street / Barangay (Lot/Blk/TCT/Tax Dec are not backed by dedicated columns) --}}
    <div class="f clip sm" style="top:4.38in; left:0.75in; max-width:0.85in;">{{ $trunc($application->building_street, 12) }}</div>
    <div class="f clip sm" style="top:4.38in; left:2.35in; max-width:2.8in;">{{ $tc($application->buildingBarangay?->name) }}</div>
    <div class="f clip sm" style="top:4.38in; left:6.45in; max-width:1.75in;">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>

    {{-- Scope of Work — Annual Inspection has no scope_of_works equivalent, left unmapped. --}}
    @if($sk(1))<div class="c" style="top:4.80in; left:0.44in;">&#10004;</div>@endif  {{-- New Installation --}}
    @if($sk(13))<div class="c" style="top:4.80in; left:5.19in;">&#10004;</div>@endif {{-- Others --}}

    {{-- Box 2 (Nature of Installation Works/Equipment System) and Box 3/4 (Design Professional /
         Supervisor-In-Charge of Electronics Works) are left blank — no backing columns exist for
         per-system checkboxes or a dedicated professional-engineer/supervisor data group. --}}

    {{-- BOX 5: Building Owner --}}
    <div class="f ctr" style="top:10.65in; left:0.3in; width:3.6in; font-weight:bold;">{{ strtoupper(trim($application->applicant_first_name . ' ' . $mi . ' ' . $application->applicant_last_name)) }}</div>
    <div class="f" style="top:11.24in; left:1.85in;">{{ optional($application->applicant_date_signed)->format('m/d/Y') }}</div>
    <div class="f clip sm" style="top:11.58in; left:0.3in; max-width:3.6in;">{{ $trunc($tc($application->applicant_street), 45) }}</div>
    <div class="f clip" style="top:11.95in; left:0.19in; max-width:1.28in;">{{ $application->applicant_govt_id ?? '' }}</div>
    <div class="f clip" style="top:11.95in; left:1.55in; max-width:1.15in;">{{ optional($application->applicant_id_date_issued)->format('m/d/Y') }}</div>
    <div class="f clip" style="top:11.95in; left:2.78in; max-width:1.28in;">{{ $application->applicant_id_place_issued ?? '' }}</div>

    {{-- BOX 6: With My Consent — Lot Owner --}}
    <div class="f ctr" style="top:10.65in; left:4.45in; width:3.6in; font-weight:bold;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f" style="top:11.24in; left:6.03in;">{{ optional($application->owner_date_signed)->format('m/d/Y') }}</div>
    <div class="f clip sm" style="top:11.58in; left:4.45in; max-width:3.6in;">{{ $application->owner_address ?? '' }}</div>
    <div class="f clip" style="top:11.95in; left:4.41in; max-width:1.3in;">{{ $application->owner_govt_id ?? '' }}</div>
    <div class="f clip" style="top:11.95in; left:5.77in; max-width:1.15in;">{{ optional($application->owner_id_date_issued)->format('m/d/Y') }}</div>
    <div class="f clip" style="top:11.95in; left:7.0in; max-width:1.28in;">{{ $application->owner_id_place_issued ?? '' }}</div>

</div>{{-- end page 1 --}}

{{-- ======================== PAGE 2 ======================== --}}
{{-- Box 7 (Received By/document checklist) and Box 8 (Progress Flow table) are left blank —
     internal-office-only boxes with no backing data, same convention as the other discipline forms. --}}
<div class="print-page p2 page-break">

    {{-- BOX 9: Permit Issued By — Building Official --}}
    <div class="f ctr" style="top:10.25in; left:2.4in; width:3.6in; font-weight:bold;">{{ strtoupper($boFullName) }}</div>
    <div class="f ctr" style="top:10.45in; left:2.4in; width:3.6in;">{{ strtoupper($boDesignation ?? '') }}</div>

</div>

</body>
</html>
