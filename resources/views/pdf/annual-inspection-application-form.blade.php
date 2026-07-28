<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <meta charset="utf-8">
    <title>Application for Annual Inspection - {{ $application->application_number }}</title>
    <style>
        @page { size: a4 portrait; margin: 0.7in 0.75in; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; color: #000; font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; }
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
        table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.items th { text-align: left; font-size: 9pt; color: #555; border-bottom: 1px solid #999; padding: 4px 6px; }
        table.items td { font-size: 9.5pt; padding: 4px 6px; border-bottom: 1px solid #eee; }
        .sig-block { margin-top: 0.6in; text-align: center; }
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

    <div class="title">Application for Annual Inspection</div>
    <div class="subtitle">Application No. {{ $application->application_number }} — {{ $application->application_kind === 'yearly' ? 'Yearly Renewal' : 'New' }}</div>

    <div class="section">
        <h3>Owner / Lessee</h3>
        <table class="fields">
            <tr>
                <td class="label">Name of Owner/Lessee</td>
                <td class="value">{{ strtoupper($application->owner_name ?? '') }}</td>
            </tr>
            @if($application->occupancy_no)
            <tr>
                <td class="label">Occupancy Permit No.</td>
                <td class="value">{{ $application->occupancy_no }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <h3>Location Address</h3>
        <table class="fields">
            <tr>
                <td class="label">Street/Building</td>
                <td class="value">{{ $application->location_street ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Barangay</td>
                <td class="value">{{ $application->locationBarangay?->name ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Equipment / Items to be Inspected</h3>
        @if($application->equipmentItems->isNotEmpty())
        <table class="items">
            <thead>
                <tr>
                    <th style="width:55%;">Equipment</th>
                    <th style="width:15%;">Qty</th>
                    <th style="width:30%;">Specification</th>
                </tr>
            </thead>
            <tbody>
                @foreach($application->equipmentItems as $item)
                <tr>
                    <td>{{ \App\Models\AnnualInspectionEquipmentItem::labelFor($item->fee_code) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->specification ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color:#888;">No equipment items listed.</p>
        @endif
    </div>

    <div class="sig-block">
        <div class="sig-name">{{ strtoupper($application->owner_name ?? '') }}</div>
        <div>Owner/Lessee's Signature Over Printed Name</div>
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
