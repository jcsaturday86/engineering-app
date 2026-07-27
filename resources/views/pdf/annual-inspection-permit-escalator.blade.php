<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <meta charset="utf-8">
    <title>Escalator Certificate {{ $permit->permit_number }}</title>
    <style>
        @page { size: 11.69in 8.27in; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #000; }

        .print-page {
            position: relative;
            width: 11.69in;
            height: 8.27in;
            background-image: url('{{ public_path('images/forms/escalator.jpg') }}');
            background-size: 11.69in 8.27in;
            background-repeat: no-repeat;
            background-position: top left;
            overflow: hidden;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .f {
            position: absolute;
            font: 8.5pt/1.15 'DejaVu Sans', Arial, sans-serif;
            white-space: nowrap;
        }
        .ctr { text-align: center; }
        .clip { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sm { font-size: 7.5pt; }
    </style>
</head>
<body>

@php
    $unit = $aiUnit; // AnnualInspectionPermitUnit for this permit (per-unit ESC certificate)
    $item = $unit?->assessmentItem;
    $specs = $item?->computation_details['specs'] ?? [];
    $classification = $item?->fee_code ? \App\Models\AnnualInspectionEquipmentItem::labelFor($item->fee_code) : '';

    $locationText = trim(($application->location_street ?? '') . ', ' . ($application->locationBarangay?->name ?? ''), ', ');
    $collection = $application->collections->where('status', 'active')->first();

    $issuedDate = $permit->issued_date ? \Carbon\Carbon::parse($permit->issued_date) : now();

    $signatoriesSnapshot = $permit->signatories_snapshot ?? [];
    $sigFull = function (string $role) use ($signatoriesSnapshot, $signatories) {
        if (array_key_exists($role, $signatoriesSnapshot)) {
            return trim(($signatoriesSnapshot[$role]['title'] ?? '') . ' ' . ($signatoriesSnapshot[$role]['name'] ?? ''));
        }
        $s = $signatories[$role] ?? null;
        return trim((($s->title ?? '') . ' ' . ($s->name ?? '')));
    };
    $cityEngineerDesignation = $signatories['ai_city_engineer']->designation ?? '';
@endphp

<div class="print-page">
    {{-- ======================== LEFT HALF — CERTIFICATE OF INSPECTION: ESCALATOR ======================== --}}
    <div class="f" style="top:0.550in; left:3.780in; width:1.522in;">{{ $permit->permit_number }}</div>
    <div class="f" style="top:0.836in; left:3.937in; width:1.365in;">{{ $issuedDate->format('m/d/Y') }}</div>

    <div class="f clip" style="top:1.789in; left:1.569in; width:3.624in;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f clip" style="top:2.087in; left:1.976in; width:3.216in;">{{ $locationText }}</div>

    <div class="f" style="top:2.718in; left:1.569in; width:3.624in;">{{ $specs['rated_load'] ?? '' }}</div>
    <div class="f" style="top:3.036in; left:1.576in; width:3.616in;">{{ $specs['capacity_per_hour'] ?? '' }}</div>
    <div class="f" style="top:3.365in; left:1.569in; width:3.624in;">{{ $specs['speed'] ?? '' }}</div>
    <div class="f" style="top:3.675in; left:1.569in; width:3.624in;">{{ $specs['effective_width'] ?? '' }}</div>
    <div class="f" style="top:3.977in; left:1.576in; width:3.616in;">{{ $specs['tread_width'] ?? '' }}</div>
    <div class="f" style="top:4.291in; left:1.569in; width:3.624in;">{{ $specs['floors_served'] ?? '' }}</div>
    <div class="f" style="top:4.605in; left:1.569in; width:3.624in;">{{ $specs['floor_height'] ?? '' }}</div>
    <div class="f" style="top:4.919in; left:1.576in; width:3.616in;">{{ $specs['motor_hp'] ?? '' }}</div>
    <div class="f" style="top:5.217in; left:1.710in; width:3.482in;">{{ $issuedDate->format('m/d/Y') }}</div>

    <div class="f" style="top:5.546in; left:0.839in; width:2.110in;">&#8369;{{ number_format($unit->amount ?? 0, 2) }}</div>
    <div class="f" style="top:5.546in; left:3.741in; width:1.451in;">{{ $collection->or_number ?? '' }}</div>
    <div class="f" style="top:5.852in; left:1.294in; width:3.890in;">{{ $issuedDate->format('m/d/Y') }}</div>

    <div class="f ctr" style="top:6.436in; left:1.435in; width:2.933in; font-weight:bold;">{{ strtoupper($sigFull('ai_mechanical')) }}</div>
    {{-- "Received by" and "Date" left blank — no client-side signature data source, filled by hand --}}

    {{-- ======================== RIGHT HALF — CERTIFICATE OF OPERATION: ESCALATOR ======================== --}}
    <div class="f" style="top:0.409in; left:9.686in; width:1.529in;">{{ $permit->permit_number }}</div>
    <div class="f" style="top:0.683in; left:9.835in; width:1.380in;">{{ $issuedDate->format('m/d/Y') }}</div>

    <div class="f ctr" style="top:1.820in; left:7.122in; width:2.973in;">{{ $settings['general.city'] ?? 'City of San Fernando' }}, {{ $settings['general.province'] ?? 'La Union' }}</div>

    <div class="f clip" style="top:3.271in; left:6.424in; width:4.753in;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f clip" style="top:3.562in; left:7.325in; width:3.851in;">{{ $locationText }}</div>
    <div class="f" style="top:3.852in; left:7.875in; width:2.424in;">{{ $issuedDate->format('m/d/Y') }}</div>

    <div class="f" style="top:4.534in; left:7.090in; width:0.800in;">{{ $specs['rated_load'] ?? '' }}</div>
    <div class="f" style="top:4.534in; left:9.114in; width:0.745in;">{{ $specs['capacity_per_hour'] ?? '' }}</div>
    <div class="f" style="top:4.534in; left:10.447in; width:0.729in;">{{ $specs['speed'] ?? '' }}</div>

    <div class="f" style="top:4.895in; left:7.357in; width:0.682in;">{{ $specs['effective_width'] ?? '' }}</div>
    <div class="f" style="top:4.895in; left:8.925in; width:0.659in;">{{ $specs['tread_width'] ?? '' }}</div>
    <div class="f" style="top:4.895in; left:10.533in; width:0.643in;">{{ $specs['floors_served'] ?? '' }}</div>

    <div class="f" style="top:5.256in; left:7.169in; width:1.176in;">{{ $specs['floor_height'] ?? '' }}</div>
    <div class="f" style="top:5.256in; left:9.624in; width:1.545in;">{{ $specs['motor_hp'] ?? '' }}</div>

    <div class="f" style="top:6.142in; left:6.973in; width:1.600in;">{{ $issuedDate->format('m/d/Y') }}</div>
    <div class="f" style="top:6.409in; left:7.247in; width:1.325in;">&#8369;{{ number_format($unit->amount ?? 0, 2) }}</div>
    <div class="f" style="top:6.668in; left:7.529in; width:1.043in;">{{ $collection->or_number ?? '' }}</div>
    <div class="f" style="top:6.926in; left:6.973in; width:1.600in;">{{ $collection?->or_date ? \Carbon\Carbon::parse($collection->or_date)->format('m/d/Y') : '' }}</div>

    <div class="f ctr" style="top:6.20in; left:9.35in; width:2.2in; font-weight:bold;">{{ strtoupper($sigFull('ai_city_engineer')) }}</div>
    <div class="f ctr sm" style="top:6.40in; left:9.35in; width:2.2in;">{{ $cityEngineerDesignation }}</div>

    @if(!empty($qrImage))
    <img src="{{ $qrImage }}" alt="Verification QR" style="display:block; position:absolute; top:6.7in; left:10in; width:0.9in; height:0.9in;">
    @endif
</div>

</body>
</html>
