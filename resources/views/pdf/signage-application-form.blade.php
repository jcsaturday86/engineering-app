@php
    $applicantName = trim(collect([
        $application->applicant_first_name,
        $application->applicant_middle_name,
        $application->applicant_last_name,
    ])->filter()->implode(' '));
    $applicantAddress = trim(collect([
        $application->applicant_street,
        $application->applicantBarangay?->name,
        $application->applicantCity?->name,
        $application->applicantProvince?->name,
    ])->filter()->implode(', '), ', ');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <meta charset="utf-8">
    <title>Application for Signage Permit - {{ $application->application_number }}</title>
    <style>
        @page { size: a4 portrait; margin: 0.7in 0.75in; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; color: #000; font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; }
        .hdr { text-align: center; }
        .hdr img { height: 0.75in; }
        .hdr-text h2 { margin: 0; font-size: 12pt; }
        .hdr-text p { margin: 1px 0; font-size: 9.5pt; }
        .title { text-align: center; font-size: 13pt; font-weight: bold; margin: 0.25in 0 0.05in; text-transform: uppercase; }
        .subtitle { text-align: center; font-size: 9.5pt; color: #444; margin-bottom: 0.25in; }
        .section { margin-top: 0.2in; }
        .section h3 { font-size: 10.5pt; border-bottom: 1px solid #999; padding-bottom: 3px; margin: 0 0 8px; }
        table.fields { width: 100%; border-collapse: collapse; }
        table.fields td { padding: 4px 6px; vertical-align: top; font-size: 10pt; }
        table.fields td.label { color: #555; width: 35%; }
        table.fields td.value { font-weight: bold; border-bottom: 1px solid #ccc; }
        .check { font-weight: bold; }
        .sig-block { margin-top: 0.6in; text-align: center; }
        .sig-line { border-top: 1px solid #000; width: 3.2in; margin: 0.5in auto 0.05in; }
        .sig-name { font-weight: bold; text-transform: uppercase; }
        .footer { position: fixed; bottom: 0.2in; left: 0; right: 0; text-align: center; font-size: 6.5pt; color: #666; }
    </style>
</head>
<body>
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:0.9in; text-align:left;">@if($sealImage ?? null)<img src="{{ $sealImage }}" style="height:0.75in;">@endif</td>
            <td class="hdr-text">
                <p>Republic of the Philippines</p>
                <h2>{{ $settings['general.city'] ?? 'City of San Fernando' }}</h2>
                <p>Province of {{ $settings['general.province'] ?? 'La Union' }}</p>
                <p>Office of the Building Official</p>
            </td>
            <td style="width:0.9in; text-align:right;">@if($nationalGovtLogo ?? null)<img src="{{ $nationalGovtLogo }}" style="height:0.75in;">@endif</td>
        </tr>
    </table>

    <div class="title">Application for Signage Permit</div>
    <div class="subtitle">Application No. {{ $application->application_number }}</div>

    <div class="section">
        <h3>Applicant Information</h3>
        <table class="fields">
            <tr>
                <td class="label">Name of Applicant</td>
                <td class="value">{{ strtoupper($applicantName) }}</td>
            </tr>
            <tr>
                <td class="label">Address</td>
                <td class="value">{{ $applicantAddress ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Zip Code</td>
                <td class="value">{{ $application->applicant_zip_code ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Scope of Work</h3>
        <table class="fields">
            <tr>
                <td class="label"><span class="check">{{ $application->install ? '[X]' : '[ ]' }}</span> Install</td>
                <td class="value">{{ $application->install ? ($application->install_detail ?: '-') : '' }}</td>
            </tr>
            <tr>
                <td class="label"><span class="check">{{ $application->attach ? '[X]' : '[ ]' }}</span> Attach</td>
                <td class="value">{{ $application->attach ? ($application->attach_detail ?: '-') : '' }}</td>
            </tr>
            <tr>
                <td class="label"><span class="check">{{ $application->paint ? '[X]' : '[ ]' }}</span> Paint</td>
                <td class="value">{{ $application->paint ? ($application->paint_detail ?: '-') : '' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Wordings</h3>
        <p style="min-height:0.5in; border-bottom:1px solid #ccc; padding:6px 0;">{{ $application->wordings ?: '-' }}</p>
    </div>

    <div class="section">
        <h3>Premises Of</h3>
        <p style="min-height:0.3in; border-bottom:1px solid #ccc; padding:6px 0;">{{ $application->premises_of ?: '-' }}</p>
    </div>

    <div class="sig-block">
        <div class="sig-name">{{ strtoupper($applicantName) }}</div>
        <div>Applicant's Signature Over Printed Name</div>
    </div>

    @if($boName ?? null)
    <div class="sig-block">
        <div class="sig-name">{{ strtoupper(trim(($boTitle ?? '') . ' ' . ($boName ?? ''))) }}</div>
        <div>{{ strtoupper($boDesignation ?? 'Building Official') }}</div>
    </div>
    @endif

    <div class="footer">This is a computer-generated document. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}</div>
</body>
</html>
