<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Annual Inspection Permit {{ $permit->permit_number }}</title>
    <style>
        @page { size: 11.69in 8.27in; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #000; }

        .print-page {
            position: relative;
            width: 11.69in;
            height: 8.27in;
            background-image: url('{{ public_path('images/forms/nbc-form-b19-hq.jpg') }}');
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
    $occGroup = $application->applicationOccupancyGroups->first();
    $groupText = $occGroup?->occupancyGroup ? ($occGroup->occupancyGroup->code . ': ' . $occGroup->occupancyGroup->name) : '';
    $subGroupText = $occGroup?->occupancySubGroup->name ?? '';

    $collection = $application->collections->where('status', 'active')->first();
    $locationText = trim(($application->location_street ?? '') . ', ' . ($application->locationBarangay?->name ?? ''), ', ');

    // Locked at generation time (doGenerateAi()) so later edits to Signatory rows don't
    // retroactively change already-generated certificates — same principle as building_official_*.
    // Older permits generated before this snapshot existed fall back to a live lookup.
    $signatoriesSnapshot = $permit->signatories_snapshot ?? [];
    $sigFull = function (string $role) use ($signatoriesSnapshot, $signatories) {
        if (array_key_exists($role, $signatoriesSnapshot)) {
            return trim(($signatoriesSnapshot[$role]['title'] ?? '') . ' ' . ($signatoriesSnapshot[$role]['name'] ?? ''));
        }
        $s = $signatories[$role] ?? null;
        return trim((($s->title ?? '') . ' ' . ($s->name ?? '')));
    };
@endphp

<div class="print-page">
    {{-- ======================== LEFT HALF — NBC FORM B-19 ======================== --}}
    <div class="f ctr" style="top:1.13in; left:0.39in; width:5.09in; font-size:10.5pt; font-weight:bold;">{{ strtoupper($application->owner_name ?? '') }}</div>

    <div class="f ctr" style="top:1.73in; left:0.39in; width:5.09in; font-size:9.5pt;">{{ $locationText }}</div>

    <div class="f ctr" style="top:2.29in; left:0.39in; width:2.40in;">{{ $subGroupText }}</div>
    <div class="f ctr" style="top:2.29in; left:2.90in; width:2.55in;">{{ $groupText }}</div>

    {{-- Verified as to the following requirements — 4 rows x 3 columns of AI discipline signatories --}}
    <div class="f ctr" style="top:3.98in; left:0.39in; width:1.63in;">{{ $sigFull('ai_locational_zoning') }}</div>
    <div class="f ctr" style="top:3.98in; left:2.12in; width:1.66in;">{{ $sigFull('ai_line_and_grade') }}</div>
    <div class="f ctr" style="top:3.98in; left:3.82in; width:1.66in;">{{ $sigFull('ai_architectural') }}</div>

    <div class="f ctr" style="top:4.56in; left:0.39in; width:1.63in;">{{ $sigFull('ai_civil_structural') }}</div>
    <div class="f ctr" style="top:4.56in; left:2.12in; width:1.66in;">{{ $sigFull('ai_electrical') }}</div>
    <div class="f ctr" style="top:4.56in; left:3.82in; width:1.66in;">{{ $sigFull('ai_mechanical') }}</div>

    <div class="f ctr" style="top:5.08in; left:0.39in; width:1.63in;">{{ $sigFull('ai_sanitary') }}</div>
    <div class="f ctr" style="top:5.08in; left:2.12in; width:1.66in;">{{ $sigFull('ai_plumbing') }}</div>
    <div class="f ctr" style="top:5.08in; left:3.82in; width:1.66in;">{{ $sigFull('ai_electronics') }}</div>

    <div class="f ctr" style="top:5.54in; left:0.39in; width:1.63in;">{{ $sigFull('ai_interior_design') }}</div>
    <div class="f ctr" style="top:5.54in; left:2.12in; width:1.66in;">{{ $sigFull('ai_accessibility') }}</div>
    <div class="f ctr" style="top:5.54in; left:3.82in; width:1.66in;">{{ $sigFull('ai_fire_safety') }}</div>

    <div class="f ctr" style="top:6.55in; left:0.55in; width:1.0in;">{{ $application->occupancy_no ?? '' }}</div>
    <div class="f ctr" style="top:6.55in; left:2.45in; width:1.1in;">{{ $application->occupancy_issued_date ? \Carbon\Carbon::parse($application->occupancy_issued_date)->format('m/d/Y') : '' }}</div>

    {{-- Chief signature blocks --}}
    <div class="f ctr" style="top:7.09in; left:0.39in; width:2.45in; font-weight:bold;">{{ strtoupper($sigFull('ai_chief_inspection_enforcement')) }}</div>
    <div class="f ctr" style="top:7.09in; left:3.04in; width:2.42in; font-weight:bold;">{{ strtoupper($sigFull('ai_chief_processing_evaluation')) }}</div>
    {{-- Dates left blank — signed and dated by hand --}}

    {{-- ======================== RIGHT HALF — OFFICE OF THE BUILDING OFFICIAL ======================== --}}
    @if($sealImage ?? null)
    <img src="{{ $sealImage }}" alt="Official Seal" style="display:block; position:absolute; top:0.30in; left:6.42in; width:0.70in; height:0.70in;">
    @endif
    <div class="f ctr" style="top:0.43in; left:5.845in; width:5.845in; font-size:9.5pt;">Republic of the Philippines</div>
    <div class="f ctr" style="top:0.59in; left:5.845in; width:5.845in; font-size:9.5pt;">Province of {{ $settings['general.province'] ?? 'La Union' }}</div>
    <div class="f ctr" style="top:0.75in; left:5.845in; width:5.845in; font-size:10.5pt; font-weight:bold;">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>

    <div class="f" style="top:1.44in; left:7.86in; width:1.4in;">{{ $permit->permit_number }}</div>
    <div class="f" style="top:1.62in; left:8.21in; width:1.6in;">&#8369;{{ number_format($aiUnit->amount ?? 0, 2) }}</div>
    <div class="f" style="top:1.78in; left:8.82in; width:1.6in;">{{ $collection->or_number ?? '' }}</div>
    <div class="f" style="top:1.96in; left:8.32in; width:1.5in;">{{ $collection?->or_date ? \Carbon\Carbon::parse($collection->or_date)->format('m/d/Y') : '' }}</div>
    <div class="f ctr" style="top:2.18in; left:9.52in; width:1.70in;">{{ $permit->issued_date ? \Carbon\Carbon::parse($permit->issued_date)->format('m/d/Y') : now()->format('m/d/Y') }}</div>

    <div class="f clip" style="top:2.86in; left:7.9in; width:3.4in;">{{ strtoupper($application->owner_name ?? '') }}</div>
    <div class="f ctr clip sm" style="top:3.08in; left:7.7in; width:0.65in;">{{ $subGroupText }}</div>
    <div class="f clip" style="top:3.05in; left:9.25in; width:2.05in;">{{ $groupText }}</div>
    <div class="f clip" style="top:3.25in; left:7.35in; width:3.95in;">{{ $locationText }}</div>

    <div class="f ctr" style="top:6.82in; left:9.59in; width:1.66in; font-weight:bold;">{{ strtoupper(trim(($permit->building_official_title ?? '') . ' ' . ($permit->building_official_name ?? ''))) }}</div>
    {{-- Date left blank — signed and dated by hand --}}

    @if(!empty($qrImage))
    <img src="{{ $qrImage }}" alt="Verification QR" style="display:block; position:absolute; top:6.35in; left:7.0in; width:1.0in; height:1.0in;">
    @endif
</div>

</body>
</html>
