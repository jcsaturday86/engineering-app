@php
    $mi = $application->applicant_middle_name ? mb_substr($application->applicant_middle_name, 0, 1) . '.' : '';
    $applicantAddress = trim(collect([
        $application->applicant_street,
        $application->applicantBarangay?->name,
        $application->applicantCity?->name,
    ])->filter()->implode(', '), ', ');
    $occupancy = $application->applicationOccupancyGroups->map(fn ($og) => $og->occupancySubGroup?->name ?? $og->occupancyGroup?->name)->filter()->unique()->implode(', ');
    $trunc = fn (?string $s, int $len) => $s ? \Illuminate\Support\Str::limit($s, $len, '') : '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Application for Demolition Permit - {{ $application->application_number }}</title>
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
        .p1 { background-image: url('{{ public_path('images/forms/demolition-p1.jpg') }}'); }
        .p2 { background-image: url('{{ public_path('images/forms/demolition-p2.jpg') }}'); }

        .f {
            position: absolute;
            font: 9pt/1.15 Arial, sans-serif;
            white-space: nowrap;
        }
        .c {
            position: absolute;
            width: 0.17in;
            height: 0.15in;
            line-height: 0.15in;
            text-align: center;
            font: bold 10pt/1 'DejaVu Sans', Arial, sans-serif;
        }
        .ctr { text-align: center; }
        .sm { font-size: 8pt; }
        .clip { overflow: hidden; text-overflow: ellipsis; }
        .hdr { position: absolute; top:0; left:0; width:8.5in; text-align:center; font: 12pt/1.3 Arial, sans-serif; }
    </style>
</head>
<body>

{{-- ======================== PAGE 1 ======================== --}}
<div class="print-page p1">

    {{-- Letterhead: sits in the blank space BELOW "NBC FORM NO. B-08" (y:0.32-0.43in)
         and ABOVE "OFFICE OF THE BUILDING OFFICIAL" (y:1.28-1.39in). --}}
    @if($sealImage ?? null)
    <img src="{{ $sealImage }}" alt="Official Seal" style="position:absolute; top:0.46in; left:0.35in; width:0.72in; height:0.72in;">
    @endif
    @if($nationalGovtLogo ?? null)
    <img src="{{ $nationalGovtLogo }}" alt="National Government Logo" style="position:absolute; top:0.46in; left:7.43in; width:0.72in; height:0.72in;">
    @endif
    <div class="hdr" style="top:0.46in;">Republic of the Philippines</div>
    <div class="hdr" style="top:0.66in; font-weight:bold;">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>
    <div class="hdr" style="top:0.86in;">Province of {{ $settings['general.province'] ?? 'La Union' }}</div>

    {{-- Application No. box (top row: APPLICATION NO. / DP NO. / BUILDING PERMIT NO. — the latter two
         are only assigned at permit-generation time, so they stay blank here). --}}
    <div class="f ctr" style="top:2.14in; left:0.24in; width:2.4in;">{{ $application->application_number }}</div>

    {{-- BOX 1: Owner/Applicant (values sit on the blank line BELOW the label row) --}}
    <div class="f clip" style="top:3.00in; left:2.02in; max-width:1.5in;">{{ $application->applicant_last_name }}</div>
    <div class="f clip" style="top:3.00in; left:3.58in; max-width:2.1in;">{{ $application->applicant_first_name }}</div>
    <div class="f clip" style="top:3.00in; left:5.76in; max-width:0.65in;">{{ $mi }}</div>
    <div class="f clip" style="top:3.00in; left:6.52in; max-width:1.55in;">{{ $application->applicant_tin ?? '' }}</div>

    {{-- Enterprise / Form of Ownership / Use or Character of Occupancy --}}
    <div class="f clip" style="top:3.50in; left:1.42in; max-width:1.85in; font-size:7pt;">{{ $application->owned_by_enterprise ? $application->enterprise_name : '' }}</div>
    <div class="f clip" style="top:3.44in; left:3.40in; max-width:1.7in;">{{ $application->formOfOwnership?->name ?? '' }}</div>
    <div class="f clip" style="top:3.44in; left:5.19in; max-width:2.9in;">{{ $trunc($occupancy, 40) }}</div>

    {{-- Address: No./Street/Barangay, City/Municipality Of, Zip Code, Telephone No. --}}
    <div class="f clip" style="top:3.86in; left:0.24in; max-width:3.3in;">{{ $trunc(trim(collect([$application->applicant_street, $application->applicantBarangay?->name])->filter()->implode(', ')), 40) }}</div>
    <div class="f clip sm" style="top:3.86in; left:3.58in; max-width:1.55in;">{{ $application->applicantCity?->name ?? '' }}</div>
    <div class="f clip" style="top:3.86in; left:5.19in; max-width:1.3in;">{{ $application->applicant_zip_code ?? '' }}</div>
    <div class="f clip" style="top:3.86in; left:6.52in; max-width:1.6in;">{{ $application->applicant_telephone ?? '' }}</div>

    {{-- Location of Demolition Works --}}
    <div class="f clip sm" style="top:4.14in; left:2.90in; max-width:0.75in;">{{ $application->lot_no ?? '' }}</div>
    <div class="f clip sm" style="top:4.14in; left:3.98in; max-width:0.75in;">{{ $application->block_no ?? '' }}</div>
    <div class="f clip sm" style="top:4.14in; left:5.08in; max-width:1.25in;">{{ $application->tct_no ?? '' }}</div>
    <div class="f clip sm" style="top:4.14in; left:6.70in; max-width:1.5in;">{{ $application->tax_dec_no ?? '' }}</div>
    <div class="f clip sm" style="top:4.36in; left:0.77in; max-width:1.35in;">{{ $trunc($application->demolition_street, 22) }}</div>
    <div class="f clip sm" style="top:4.36in; left:2.38in; max-width:2.35in;">{{ $application->demolitionBarangay?->name ?? '' }}</div>
    <div class="f clip sm" style="top:4.36in; left:5.35in; max-width:2.85in;">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>

    {{-- Scope of Work --}}
    @if($application->scope_of_work === 'demolition')<div class="c" style="top:4.735in; left:0.40in;">&#10004;</div>@endif
    <div class="f" style="top:4.72in; left:1.4in; max-width:1.4in;">{{ $trunc($application->scope_of_work === 'demolition' ? $application->scope_of_work_detail : '', 30) }}</div>
    @if($application->scope_of_work === 'others')<div class="c" style="top:4.74in; left:4.65in;">&#10004;</div>@endif
    <div class="f" style="top:4.965in; left:4.60in; max-width:3.2in;">{{ $trunc($application->scope_of_work === 'others' ? $application->scope_of_work_detail : '', 55) }}</div>

    {{-- BOX 2: Full-time Inspector and Supervisor of Demolition Works --}}
    <div class="f ctr" style="top:5.78in; left:0.2in; width:4.23in; font-weight:bold;">{{ strtoupper($application->inspector_name ?? '') }}</div>
    <div class="f clip" style="top:5.835in; left:4.5in; max-width:1.95in; font-size:6pt;">{{ $trunc($application->inspector_address, 55) }}</div>
    <div class="f clip" style="top:5.835in; left:6.55in; max-width:1.6in;">{{ $application->inspector_telephone ?? '' }}</div>
    <div class="f clip" style="top:6.10in; left:5.3in; max-width:1.1in;">{{ $application->inspector_prc_no ?? '' }}</div>
    <div class="f clip" style="top:6.10in; left:7.3in; max-width:0.9in;">{{ $application->inspector_prc_validity?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:6.32in; left:5.3in; max-width:1.1in;">{{ $application->inspector_ptr_no ?? '' }}</div>
    <div class="f clip" style="top:6.32in; left:7.3in; max-width:0.9in;">{{ $application->inspector_ptr_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip" style="top:6.54in; left:5.3in; max-width:1.1in;">{{ $application->inspector_ptr_issued_at ?? '' }}</div>
    <div class="f clip" style="top:6.54in; left:7.3in; max-width:0.9in;">{{ $application->inspector_tin ?? '' }}</div>

    {{-- BOX 3: Applicant (left) / With My Consent — Lot Owner (right) --}}
    <div class="f ctr" style="top:7.26in; left:0.2in; width:4.23in; font-weight:bold;">{{ strtoupper(trim($application->applicant_first_name . ' ' . $mi . ' ' . $application->applicant_last_name)) }}</div>
    <div class="f ctr" style="top:7.26in; left:4.43in; width:3.85in; font-weight:bold;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f clip" style="top:8.03in; left:1.0in; max-width:3.3in;">{{ $trunc($applicantAddress, 45) }}</div>
    {{-- CTC No. / Date Issued / Place Issued — Applicant (left) and Lot Owner (right) --}}
    <div class="f clip" style="top:8.42in; left:0.3in; max-width:1.2in;">{{ $application->applicant_ctc_no ?? '' }}</div>
    <div class="f clip" style="top:8.42in; left:1.7in; max-width:1.5in;">{{ $application->applicant_ctc_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip sm" style="top:8.42in; left:3.45in; max-width:0.95in;">{{ $application->applicant_ctc_place_issued ?? '' }}</div>
    <div class="f clip" style="top:8.42in; left:4.55in; max-width:0.85in;">{{ $application->owner_ctc_no ?? '' }}</div>
    <div class="f clip" style="top:8.42in; left:5.55in; max-width:1.05in;">{{ $application->owner_ctc_date_issued?->format('m/d/Y') ?? '' }}</div>
    <div class="f clip sm" style="top:8.42in; left:6.75in; max-width:1.45in;">{{ $application->owner_ctc_place_issued ?? '' }}</div>

    {{-- BOX 4 (Notarization) is left entirely blank — wholly manual/notarial, no corresponding data. --}}

    <div class="f ctr" style="bottom:0.12in; left:0; width:8.5in; font-size:6pt; color:#555;">This is a computer-generated document. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}</div>

</div>{{-- end page 1 --}}

{{-- ======================== PAGE 2 ======================== --}}
{{-- Box 5 (Processing/Evaluation fee) and Box 6 (Building Official action-taken) are staff-only
     fields that only become meaningful after payment and permit generation — the application
     print shouldn't show an unissued permit as "issued." Background only, no data overlay. --}}
<div class="print-page p2 page-break">
    {{-- Permit Issued By: Building Official, placed above "(Signature Over Printed Name)" --}}
    <div class="f ctr" style="top:9.70in; left:0.7in; width:6.9in; font-weight:bold;">{{ strtoupper(trim(($boTitle ?? '') . ' ' . ($boName ?? ''))) }}</div>
    <div class="f ctr" style="top:9.90in; left:0.7in; width:6.9in;">{{ strtoupper($boDesignation ?? '') }}</div>

    <div class="f ctr" style="bottom:0.12in; left:0; width:8.5in; font-size:6pt; color:#555;">This is a computer-generated document. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}</div>
</div>

</body>
</html>
