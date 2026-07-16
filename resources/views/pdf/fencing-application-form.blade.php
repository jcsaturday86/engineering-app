@php
    $mi = $application->applicant_middle_name ? mb_substr($application->applicant_middle_name, 0, 1) . '.' : '';
    $applicantAddress = trim(collect([
        $application->applicant_street,
        $application->applicantBarangay?->name,
    ])->filter()->implode(', '), ', ');
    $trunc = fn (?string $s, int $len) => $s ? \Illuminate\Support\Str::limit($s, $len, '') : '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Application for Fencing Permit - {{ $application->application_number }}</title>
    <style>
        @page { size: 8.27in 11.69in; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; color: #000; }
        .print-page {
            position: relative;
            width: 8.27in; height: 11.68in;
            background-color: #fff;
            background-size: 8.27in 11.69in;
            background-repeat: no-repeat;
            background-position: top left;
            overflow: hidden;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
        .p1 { background-image: url('{{ public_path('images/forms/fencing-p1.png') }}'); }
        .p2 { background-image: url('{{ public_path('images/forms/fencing-p2.png') }}'); page-break-before: always; }

        .f {
            position: absolute;
            font: 8.5pt/1.15 Arial, sans-serif;
            white-space: nowrap;
        }
        .c {
            position: absolute;
            width: 0.11in;
            height: 0.10in;
            line-height: 0.10in;
            text-align: center;
            font: bold 8pt/1 Arial, sans-serif;
        }
        .ctr { text-align: center; }
        .sm { font-size: 7pt; }
        .clip { overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>

{{-- ======================== PAGE 1 (Boxes 1-5) ======================== --}}
<div class="print-page p1">

    {{-- Top row: Application No. / FP No. / Building Permit No. --}}
    <div class="f ctr" style="top:2.20in; left:0.30in; width:1.75in;">{{ $application->application_number }}</div>

    {{-- BOX 1: Owner/Applicant --}}
    <div class="f clip" style="top:2.90in; left:1.95in; max-width:1.7in;">{{ $application->applicant_last_name }}</div>
    <div class="f clip" style="top:2.90in; left:3.73in; max-width:2.15in;">{{ $application->applicant_first_name }}</div>
    <div class="f clip" style="top:2.90in; left:5.98in; max-width:0.4in;">{{ $mi }}</div>
    <div class="f clip" style="top:2.90in; left:6.45in; max-width:1.4in;">{{ $application->applicant_tin ?? '' }}</div>

    {{-- Enterprise / Form of Ownership --}}
    <div class="f clip" style="top:3.35in; left:0.30in; max-width:2.75in; font-size:6.5pt;">{{ $application->owned_by_enterprise ? $application->enterprise_name : '' }}</div>
    <div class="f clip" style="top:3.28in; left:3.23in; max-width:2.9in;">{{ $application->formOfOwnership?->name ?? '' }}</div>

    {{-- Address --}}
    <div class="f clip sm" style="top:3.64in; left:0.30in; max-width:2.5in;">{{ $trunc(trim(collect([$application->applicant_street, $application->applicantBarangay?->name])->filter()->implode(', ')), 34) }}</div>
    <div class="f clip sm" style="top:3.64in; left:3.23in; max-width:1.9in;">{{ $application->applicantCity?->name ?? '' }}</div>
    <div class="f clip" style="top:3.64in; left:5.4in; max-width:0.75in;">{{ $application->applicant_zip_code ?? '' }}</div>
    <div class="f clip" style="top:3.64in; left:6.45in; max-width:1.4in;">{{ $application->applicant_telephone ?? '' }}</div>

    {{-- Location of Construction --}}
    <div class="f clip sm" style="top:3.90in; left:1.75in; max-width:0.95in;">{{ $application->lot_no ?? '' }}</div>
    <div class="f clip sm" style="top:3.90in; left:3.10in; max-width:1.15in;">{{ $application->block_no ?? '' }}</div>
    <div class="f clip sm" style="top:3.90in; left:4.85in; max-width:1.1in;">{{ $application->tct_no ?? '' }}</div>
    <div class="f clip sm" style="top:3.90in; left:6.65in; max-width:1.4in;">{{ $application->tax_dec_no ?? '' }}</div>
    <div class="f clip sm" style="top:4.12in; left:0.85in; max-width:1.3in;">{{ $trunc($application->construction_street, 24) }}</div>
    <div class="f clip sm" style="top:4.12in; left:2.55in; max-width:2.6in;">{{ $application->constructionBarangay?->name ?? '' }}</div>
    <div class="f clip sm" style="top:4.12in; left:6.15in; max-width:1.9in;">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>

    {{-- Scope of Work --}}
    @if($application->scope_of_work === 'new_construction')<div class="c" style="top:4.51in; left:0.56in;">&#10004;</div>@endif
    @if($application->scope_of_work === 'erection')<div class="c" style="top:4.69in; left:0.56in;">&#10004;</div>@endif
    @if($application->scope_of_work === 'addition')<div class="c" style="top:4.87in; left:0.56in;">&#10004;</div>@endif
    @if($application->scope_of_work === 'repair')<div class="c" style="top:4.51in; left:2.42in;">&#10004;</div>@endif
    <div class="f" style="top:4.51in; left:2.75in; max-width:2.6in;">{{ $trunc($application->scope_of_work === 'repair' ? $application->scope_of_work_detail : '', 40) }}</div>
    @if($application->scope_of_work === 'others')<div class="c" style="top:4.51in; left:4.85in;">&#10004;</div>@endif
    <div class="f" style="top:4.51in; left:5.15in; max-width:2.4in;">{{ $trunc($application->scope_of_work === 'others' ? $application->scope_of_work_detail : '', 38) }}</div>

    {{-- BOX 2: Design Professional / BOX 3: Full-Time Inspector --}}
    <div class="f ctr" style="top:5.85in; left:0.30in; width:3.70in;">{{ strtoupper($application->design_professional_name ?? '') }}</div>
    <div class="f ctr" style="top:5.85in; left:4.30in; width:3.70in;">{{ strtoupper($application->inspector_name ?? '') }}</div>

    <div class="f clip" style="top:6.28in; left:0.75in; max-width:2.3in; font-size:6.5pt;">{{ $trunc($application->design_professional_address, 40) }}</div>
    <div class="f clip" style="top:6.28in; left:4.75in; max-width:2.3in; font-size:6.5pt;">{{ $trunc($application->inspector_address, 40) }}</div>

    <div class="f clip" style="top:6.44in; left:0.90in; max-width:1.4in; font-size:6.5pt;">{{ $application->design_professional_prc_no ?? '' }}</div>
    <div class="f clip" style="top:6.44in; left:2.50in; max-width:1.05in; font-size:6.5pt;">{{ optional($application->design_professional_prc_validity)->format('m/d/Y') }}</div>
    <div class="f clip" style="top:6.44in; left:4.90in; max-width:1.4in; font-size:6.5pt;">{{ $application->inspector_prc_no ?? '' }}</div>
    <div class="f clip" style="top:6.44in; left:6.46in; max-width:1.05in; font-size:6.5pt;">{{ optional($application->inspector_prc_validity)->format('m/d/Y') }}</div>

    <div class="f clip" style="top:6.61in; left:0.90in; max-width:1.4in; font-size:6.5pt;">{{ $application->design_professional_ptr_no ?? '' }}</div>
    <div class="f clip" style="top:6.61in; left:2.80in; max-width:1.05in; font-size:6.5pt;">{{ optional($application->design_professional_ptr_date_issued)->format('m/d/Y') }}</div>
    <div class="f clip" style="top:6.61in; left:4.90in; max-width:1.4in; font-size:6.5pt;">{{ $application->inspector_ptr_no ?? '' }}</div>
    <div class="f clip" style="top:6.61in; left:6.76in; max-width:1.05in; font-size:6.5pt;">{{ optional($application->inspector_ptr_date_issued)->format('m/d/Y') }}</div>

    <div class="f clip" style="top:6.79in; left:1.05in; max-width:1.25in; font-size:6.5pt;">{{ $trunc($application->design_professional_ptr_issued_at, 18) }}</div>
    <div class="f clip" style="top:6.79in; left:2.35in; max-width:1.55in; font-size:6.5pt;">{{ $application->design_professional_tin ?? '' }}</div>
    <div class="f clip" style="top:6.79in; left:5.05in; max-width:1.25in; font-size:6.5pt;">{{ $trunc($application->inspector_ptr_issued_at, 18) }}</div>
    <div class="f clip" style="top:6.79in; left:6.31in; max-width:1.55in; font-size:6.5pt;">{{ $application->inspector_tin ?? '' }}</div>

    {{-- BOX 4: Applicant (left) / With My Consent — Lot Owner (right) --}}
    <div class="f ctr" style="top:7.94in; left:0.30in; width:3.83in;">{{ strtoupper(trim($application->applicant_first_name . ' ' . $mi . ' ' . $application->applicant_last_name)) }}</div>
    <div class="f ctr" style="top:7.94in; left:4.30in; width:3.78in;">{{ strtoupper($application->owner_name ?? '') }}</div>

    <div class="f clip" style="top:8.36in; left:0.85in; max-width:3.2in; font-size:6.5pt;">{{ $trunc($applicantAddress, 55) }}</div>
    <div class="f clip" style="top:8.36in; left:4.85in; max-width:3.15in; font-size:6.5pt;">{{ $trunc($application->owner_address, 55) }}</div>

    {{-- CTC row — Applicant (left) and Lot Owner (right) --}}
    <div class="f clip" style="top:8.55in; left:0.92in; max-width:0.95in; font-size:6.5pt;">{{ $application->applicant_ctc_no ?? '' }}</div>
    <div class="f clip" style="top:8.55in; left:2.15in; max-width:0.95in; font-size:6.5pt;">{{ optional($application->applicant_ctc_date_issued)->format('m/d/Y') }}</div>
    <div class="f clip sm" style="top:8.55in; left:3.35in; max-width:0.9in;">{{ $trunc($application->applicant_ctc_issued_at, 14) }}</div>
    <div class="f clip" style="top:8.55in; left:4.92in; max-width:1.05in; font-size:6.5pt;">{{ $application->owner_ctc_no ?? '' }}</div>
    <div class="f clip" style="top:8.55in; left:6.25in; max-width:0.95in; font-size:6.5pt;">{{ optional($application->owner_ctc_date_issued)->format('m/d/Y') }}</div>
    <div class="f clip sm" style="top:8.55in; left:7.35in; max-width:0.85in;">{{ $trunc($application->owner_ctc_issued_at, 14) }}</div>

    {{-- BOX 5 (Acknowledgment/Notarization) is left entirely blank — wholly manual/notarial, no corresponding data. --}}

    <div class="f ctr" style="top:11.50in; left:0; width:8.27in; font-size:6pt; color:#555;">This is a computer-generated document. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}</div>

</div>{{-- end page 1 --}}

{{-- ======================== PAGE 2 (Boxes 6-8) ======================== --}}
{{-- Boxes 6-7 are staff/Design-Professional-completed fields that only become meaningful
     after assessment/payment/permit generation — the application print shouldn't show them
     as accomplished. Background only, no data overlay, except the Building Official name
     if a permit has already been generated. --}}
<div class="print-page p2">
    <div class="f ctr" style="top:11.24in; left:1.5in; width:5.3in; font-weight:bold;">{{ strtoupper(trim(($boTitle ?? '') . ' ' . ($boName ?? ''))) }}</div>

    <div class="f ctr" style="top:11.50in; left:0; width:8.27in; font-size:6pt; color:#555;">This is a computer-generated document. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}</div>
</div>

</body>
</html>
