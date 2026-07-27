<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <meta charset="utf-8">
    <title>Machinery Certificate {{ $permit->permit_number }}</title>
    <style>
        @page { size: 11.69in 8.27in; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #000; }

        .print-page {
            position: relative;
            width: 11.69in;
            height: 8.27in;
            background-image: url('{{ public_path('images/forms/machinery.jpg') }}');
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
    $items = $aiGroupItems; // Machinery items bundled into this one certificate (not per-unit)
    $totalQty = $items->sum('quantity');

    $descriptions = $items->map(function ($i) {
        $label = $i->fee_code ? \App\Models\AnnualInspectionEquipmentItem::labelFor($i->fee_code) : '';
        $desc = $i->computation_details['specs']['equipment_description'] ?? null;
        return $desc ? "{$label}: {$desc}" : $label;
    })->filter()->values();

    // The left-page "Description:" field only has two printed lines to write on, so wrap onto
    // the continuation line below it (at ~48 chars/line for 8.5pt text over the ~3.9in field width)
    // instead of letting long descriptions run off the page edge.
    $descriptionLines = explode("\n", wordwrap($descriptions->implode('; '), 48, "\n"));
    $descriptionLine1 = $descriptionLines[0] ?? '';
    $descriptionLine2 = implode(' ', array_slice($descriptionLines, 1));

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
    {{-- ======================== LEFT HALF — CERTIFICATE OF INSPECTION: MACHINERY ======================== --}}
    <div class="f" style="top:0.781in; left:3.953in; width:1.286in;">{{ $issuedDate->format('m/d/Y') }}</div>

    <div class="f clip" style="top:1.769in; left:1.616in; width:3.624in;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f clip" style="top:2.162in; left:2.039in; width:3.200in;">{{ $locationText }}</div>

    <div class="f" style="top:2.969in; left:1.396in; width:3.843in;">{{ number_format($totalQty, 0) }}</div>
    <div class="f" style="top:3.369in; left:1.357in; width:3.882in;">{{ $descriptionLine1 }}</div>
    <div class="f" style="top:3.769in; left:0.549in; width:4.690in;">{{ $descriptionLine2 }}</div>
    {{-- TOTAL H.P. left blank — no horsepower data source captured for Machinery items --}}
    <div class="f" style="top:4.593in; left:1.780in; width:3.459in;">{{ $issuedDate->format('m/d/Y') }}</div>
    <div class="f" style="top:5.009in; left:1.757in; width:3.482in;">{{ $expiresDate->format('m/d/Y') }}</div>
    <div class="f" style="top:5.424in; left:1.812in; width:3.427in;">&#8369;{{ number_format($aiUnit->amount ?? 0, 2) }}</div>
    <div class="f" style="top:5.840in; left:1.192in; width:4.047in;">{{ $collection->or_number ?? '' }}</div>
    <div class="f" style="top:6.248in; left:1.325in; width:3.906in;">{{ $collection?->or_date ? \Carbon\Carbon::parse($collection->or_date)->format('m/d/Y') : '' }}</div>

    <div class="f ctr" style="top:6.915in; left:2.478in; width:2.737in; font-weight:bold;">{{ strtoupper($sigFull('ai_mechanical')) }}</div>

    {{-- ======================== RIGHT HALF — CERTIFICATE OF OPERATION: MACHINERY ======================== --}}
    <div class="f" style="top:0.420in; left:9.647in; width:1.537in;">{{ $permit->permit_number }}</div>
    <div class="f" style="top:0.671in; left:9.788in; width:1.396in;">{{ $issuedDate->format('m/d/Y') }}</div>

    <div class="f ctr" style="top:1.667in; left:7.153in; width:3.012in;">{{ $settings['general.city'] ?? 'City of San Fernando' }}, {{ $settings['general.province'] ?? 'La Union' }}</div>

    <div class="f ctr" style="top:2.790in; left:8.055in; width:0.651in;">{{ $issuedDate->format('d') }}</div>
    <div class="f ctr" style="top:2.790in; left:9.129in; width:1.647in;">{{ $issuedDate->format('F Y') }}</div>

    <div class="f clip" style="top:3.064in; left:8.235in; width:2.965in;">{{ $locationText }}</div>

    <div class="f clip" style="top:3.918in; left:6.376in; width:4.824in;">{{ $descriptions->get(0, '') }}</div>
    <div class="f clip" style="top:4.185in; left:6.376in; width:4.824in;">{{ $descriptions->get(1, '') }}</div>
    <div class="f clip" style="top:4.436in; left:6.376in; width:4.824in;">{{ $descriptions->get(2, '') }}</div>
    <div class="f clip" style="top:4.687in; left:6.376in; width:4.824in;">{{ $descriptions->get(3, '') }}</div>

    <div class="f clip" style="top:5.056in; left:8.212in; width:2.973in;">{{ strtoupper($application->owner_name ?? '') }}</div>

    {{-- Total H.P. left blank — no horsepower data source captured for Machinery items --}}
    <div class="f" style="top:5.904in; left:9.427in; width:1.514in;">{{ number_format($totalQty, 0) }}</div>
    <div class="f" style="top:6.192in; left:7.114in; width:1.192in;">&#8369;{{ number_format($aiUnit->amount ?? 0, 2) }}</div>
    <div class="f" style="top:6.192in; left:9.216in; width:1.733in;">{{ $collection->or_number ?? '' }}</div>
    <div class="f" style="top:6.479in; left:7.114in; width:1.192in;">{{ $collection?->or_date ? \Carbon\Carbon::parse($collection->or_date)->format('m/d/Y') : '' }}</div>

    <div class="f ctr" style="top:6.75in; left:8.745in; width:2.196in; font-weight:bold;">{{ strtoupper($sigFull('ai_city_engineer')) }}</div>
    <div class="f ctr sm" style="top:6.95in; left:8.745in; width:2.196in;">{{ $cityEngineerDesignation }}</div>

    @if(!empty($qrImage))
    <img src="{{ $qrImage }}" alt="Verification QR" style="display:block; position:absolute; top:6.73in; left:6.95in; width:0.89in; height:0.89in;">
    @endif
</div>

</body>
</html>
