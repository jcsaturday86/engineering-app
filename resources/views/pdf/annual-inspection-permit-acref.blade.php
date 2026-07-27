<!DOCTYPE html>
<html>
<head>
    @php
        $faviconPath = $settings['general.favicon'] ?? null;
        $faviconUrl = $faviconPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($faviconPath)
            ? asset('storage/' . $faviconPath)
            : asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
    <meta charset="utf-8">
    <title>Air-Conditioning/Refrigeration Certificate {{ $permit->permit_number }}</title>
    <style>
        @page { size: 11.69in 8.27in; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #000; }

        .print-page {
            position: relative;
            width: 11.69in;
            height: 8.27in;
            background-image: url('{{ public_path('images/forms/aircon.jpg') }}');
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
    $items = $aiGroupItems; // ACREF items bundled into this one certificate (not per-unit, same as Machinery)

    $descriptions = $items->map(function ($i) {
        $label = $i->fee_code ? \App\Models\AnnualInspectionEquipmentItem::labelFor($i->fee_code) : '';
        $label = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $label));
        $specs = $i->computation_details['specs'] ?? [];
        $desc = $specs['equipment_description'] ?? null;
        $tons = $specs['tons_or_hp'] ?? null;
        $line = $desc ? "{$label}: {$desc}" : $label;
        $line = $tons ? "{$line} — {$tons}" : $line;
        $qty = (int) ($i->computation_details['quantity_count'] ?? 1);
        $unitWord = $qty === 1 ? 'Unit' : 'Units';
        return "{$qty} {$unitWord} of {$line}";
    })->filter()->values();

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
    {{-- ======================== LEFT HALF — CERTIFICATE OF OPERATION: AIR-CONDITIONING/REFRIGERATION (NBC Form No. M.10) ======================== --}}
    <div class="f clip" style="top:1.189in; left:1.02in; width:3.75in;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f clip" style="top:1.581in; left:1.02in; width:3.75in;">{{ $locationText }}</div>
    {{-- "A certificate was submitted by ___" left blank — no licensed-engineer name captured anywhere in the data model --}}

    {{-- "VERIFIED AS TO THE FOLLOWING" — list each ACREF item with its tons/HP spec (spacing tuned to fit up to 10 bullets) --}}
    @foreach($descriptions->take(10) as $descIndex => $descText)
    <div class="f clip" style="top:{{ 2.92 + $descIndex * 0.1033 }}in; left:0.55in; width:5.10in; text-align:left; font-size:8pt; line-height:1.25;">&#8226; {{ $descText }}</div>
    @endforeach

    <div class="f" style="top:3.995in; left:1.87in; width:1.44in;">{{ $issuedDate->format('m/d/Y') }}</div>
    <div class="f" style="top:4.168in; left:1.87in; width:1.44in;">{{ $expiresDate->format('m/d/Y') }}</div>
    <div class="f" style="top:4.333in; left:1.87in; width:1.44in;">&#8369;{{ number_format($aiUnit->amount ?? 0, 2) }}</div>
    <div class="f" style="top:4.497in; left:1.87in; width:1.44in;">{{ $collection->or_number ?? '' }}</div>
    <div class="f" style="top:4.654in; left:1.87in; width:1.44in;">{{ $issuedDate->format('m/d/Y') }}</div>

    <div class="f clip" style="top:5.25in; left:2.007in; width:0.862in; font-size:6.5pt;">{{ $permit->permit_number }}</div>
    <div class="f" style="top:5.25in; left:3.544in; width:0.909in;">{{ $issuedDate->format('m/d/Y') }}</div>

    {{-- "Mechanical Inspector" left blank — no distinct signatory role for this line --}}
    <div class="f ctr" style="top:6.018in; left:3.019in; width:2.140in; font-weight:bold;">{{ strtoupper($sigFull('ai_mechanical')) }}</div>
    <div class="f ctr" style="top:6.896in; left:0.557in; width:1.960in; font-weight:bold;">{{ strtoupper($sigFull('ai_chief_inspection_enforcement')) }}</div>
    <div class="f ctr" style="top:6.896in; left:3.019in; width:2.078in; font-weight:bold;">{{ strtoupper($sigFull('ai_chief_processing_evaluation')) }}</div>

    {{-- ======================== RIGHT HALF — OFFICE OF THE BUILDING OFFICIAL: CERTIFICATE OF OPERATION ======================== --}}
    <div class="f" style="top:1.824in; left:8.766in; width:1.16in;">{{ $permit->permit_number }}</div>
    <div class="f" style="top:2.012in; left:8.766in; width:1.16in;">&#8369;{{ number_format($aiUnit->amount ?? 0, 2) }}</div>
    <div class="f" style="top:2.192in; left:8.766in; width:1.16in;">{{ $collection->or_number ?? '' }}</div>
    <div class="f" style="top:2.372in; left:8.766in; width:1.16in;">{{ $collection?->or_date ? \Carbon\Carbon::parse($collection->or_date)->format('m/d/Y') : '' }}</div>
    <div class="f" style="top:2.553in; left:8.766in; width:1.16in;">{{ $issuedDate->format('m/d/Y') }}</div>

    <div class="f clip" style="top:3.525in; left:7.762in; width:3.40in;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f clip" style="top:3.823in; left:7.441in; width:3.73in;">{{ $locationText }}</div>

    @foreach($descriptions->take(10) as $descIndex => $descText)
    <div class="f clip" style="top:{{ 4.01 + $descIndex * 0.0958 }}in; left:6.64in; width:4.50in; text-align:left; font-size:8pt; line-height:1.25;">&#8226; {{ $descText }}</div>
    @endforeach

    <div class="f ctr" style="top:7.42in; left:8.718in; width:2.211in; font-weight:bold;">{{ strtoupper($sigFull('ai_city_engineer')) }}</div>
    <div class="f ctr sm" style="top:7.55in; left:8.718in; width:2.211in;">{{ $cityEngineerDesignation }}</div>

    @if(!empty($qrImage))
    <img src="{{ $qrImage }}" alt="Verification QR" style="display:block; position:absolute; top:7.25in; left:6.55in; width:0.8in; height:0.8in;">
    @endif
</div>

</body>
</html>
