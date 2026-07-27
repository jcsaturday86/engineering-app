<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <meta charset="utf-8">
    <title>Elevator Certificate {{ $permit->permit_number }}</title>
    <style>
        @page { size: 11.69in 8.27in; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #000; }

        .print-page {
            position: relative;
            width: 11.69in;
            height: 8.27in;
            background-image: url('{{ public_path('images/forms/elevator.jpg') }}');
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
    $unit = $aiUnit; // AnnualInspectionPermitUnit for this permit (per-unit ELEV certificate)
    $item = $unit?->assessmentItem;
    $specs = $item?->computation_details['specs'] ?? [];
    $classification = $item?->fee_code ? \App\Models\AnnualInspectionEquipmentItem::labelFor($item->fee_code) : '';
    $workingLoad = isset($specs['workload_kg']) ? $specs['workload_kg'] . ' kg' : '';
    $passengers = $specs['no_of_passengers'] ?? '';

    $locationText = trim(($application->location_street ?? '') . ', ' . ($application->locationBarangay?->name ?? ''), ', ');
    $collection = $application->collections->where('status', 'active')->first();

    $issuedDate = $permit->issued_date ? \Carbon\Carbon::parse($permit->issued_date) : now();
    $expiresDate = $issuedDate->copy()->addYear();

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
    {{-- ======================== LEFT HALF — CERTIFICATE OF INSPECTION: ELEVATOR ======================== --}}
    <div class="f" style="top:0.525in; left:3.8in; width:1.3in;">{{ $permit->permit_number }}</div>
    <div class="f" style="top:0.785in; left:4.0in; width:1.15in;">{{ $issuedDate->format('m/d/Y') }}</div>

    <div class="f clip" style="top:1.93in; left:1.65in; width:4.9in;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f clip" style="top:2.235in; left:1.95in; width:4.6in;">{{ $locationText }}</div>

    <div class="f" style="top:2.89in; left:1.5in; width:5.05in;">{{ $classification }}</div>
    <div class="f" style="top:3.205in; left:1.45in; width:5.1in;">{{ $workingLoad }}</div>
    <div class="f" style="top:3.525in; left:1.75in; width:4.8in;">{{ $passengers }}</div>
    <div class="f" style="top:3.855in; left:1.5in; width:5.05in;">{{ $issuedDate->format('m/d/Y') }}</div>
    <div class="f" style="top:4.185in; left:1.15in; width:5.4in;">{{ $issuedDate->format('m/d/Y') }}</div>
    <div class="f" style="top:4.515in; left:1.25in; width:5.3in;">{{ $expiresDate->format('m/d/Y') }}</div>
    <div class="f" style="top:4.845in; left:1.95in; width:4.6in;">&#8369;{{ number_format($unit->amount ?? 0, 2) }}</div>
    <div class="f" style="top:5.175in; left:1.15in; width:5.4in;">{{ $collection->or_number ?? '' }}</div>
    <div class="f" style="top:5.5in; left:1.05in; width:5.5in;">{{ $collection?->or_date ? \Carbon\Carbon::parse($collection->or_date)->format('m/d/Y') : '' }}</div>
    <div class="f ctr" style="top:6.577in; left:1.624in; width:2.415in; font-weight:bold;">{{ strtoupper($sigFull('ai_mechanical')) }}</div>
    {{-- "Received by" left blank — no client-side signature data source, filled by hand --}}

    {{-- ======================== RIGHT HALF — CERTIFICATE OF OPERATION: ELEVATOR ======================== --}}
    <div class="f" style="top:0.44in; left:9.75in; width:1.2in;">{{ $permit->permit_number }}</div>
    <div class="f" style="top:0.67in; left:9.95in; width:1.0in;">{{ $issuedDate->format('m/d/Y') }}</div>

    <div class="f ctr" style="top:1.79in; left:6.7in; width:3.4in;">{{ $settings['general.city'] ?? 'City of San Fernando' }}, {{ $settings['general.province'] ?? 'La Union' }}</div>

    <div class="f ctr" style="top:2.95in; left:8.2in; width:0.85in;">{{ $issuedDate->format('d') }}</div>
    <div class="f ctr" style="top:2.95in; left:9.55in; width:1.35in;">{{ $issuedDate->format('F Y') }}</div>

    <div class="f clip" style="top:3.19in; left:6.6in; width:4.75in;">{{ $classification }}</div>
    <div class="f clip" style="top:3.44in; left:7.15in; width:4.2in;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f clip" style="top:3.69in; left:8.4in; width:3.0in;">{{ $locationText }}</div>
    <div class="f ctr" style="top:4.19in; left:8.45in; width:1.3in;">{{ $passengers }}</div>

    <div class="f" style="top:5.69in; left:7.0in; width:1.35in;">{{ $issuedDate->format('m/d/Y') }}</div>
    <div class="f" style="top:5.93in; left:7.3in; width:1.05in;">&#8369;{{ number_format($unit->amount ?? 0, 2) }}</div>
    <div class="f" style="top:6.15in; left:7.55in; width:1.0in;">{{ $collection->or_number ?? '' }}</div>
    <div class="f" style="top:6.39in; left:7.0in; width:1.35in;">{{ $collection?->or_date ? \Carbon\Carbon::parse($collection->or_date)->format('m/d/Y') : '' }}</div>

    <div class="f ctr" style="top:6.5in; left:8.816in; width:2.36in; font-weight:bold;">{{ strtoupper($sigFull('ai_city_engineer')) }}</div>
    <div class="f ctr sm" style="top:6.7in; left:8.816in; width:2.36in;">{{ $cityEngineerDesignation }}</div>

    @if(!empty($qrImage))
    <img src="{{ $qrImage }}" alt="Verification QR" style="display:block; position:absolute; top:6.6in; left:6.4in; width:0.8in; height:0.8in;">
    @endif
</div>

</body>
</html>
