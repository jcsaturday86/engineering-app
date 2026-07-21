<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Annual Inspection Permit {{ $permit->permit_number }}</title>
    <style>
        @page { size: letter portrait; margin: 0.45in; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; line-height: 1.2; }

        .header { text-align: center; margin-bottom: 3px; }
        .header .repub { font-size: 10px; }
        .header .city { font-size: 11px; font-weight: bold; }
        .header .office { font-size: 12px; font-weight: bold; margin-top: 4px; }
        .header .title { font-size: 16px; font-weight: bold; margin-top: 2px; }
        .header .subtitle { font-size: 11px; font-weight: bold; margin-top: 2px; color: #333; }

        .top-row { display: table; width: 100%; margin: 8px 0; border-collapse: collapse; }
        .top-row .cell { display: table-cell; width: 50%; border: 1px solid #000; padding: 4px 8px; font-size: 9px; vertical-align: top; }
        .top-row .fill { display: block; margin-top: 6px; border-bottom: 1px solid #000; min-height: 12px; font-weight: bold; font-size: 10px; }

        .box { border: 1px solid #000; padding: 5px 8px; margin-bottom: 6px; }
        .box-title { font-weight: bold; font-size: 9.5px; margin-bottom: 3px; }

        .field-row { display: table; width: 100%; margin-bottom: 3px; }
        .field-row .f { display: table-cell; padding-right: 8px; vertical-align: bottom; }
        .field-row .lbl { font-size: 8px; color: #333; }
        .field-row .val { border-bottom: 1px solid #000; font-weight: bold; font-size: 10px; min-height: 12px; }

        table.equipment { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-top: 4px; }
        table.equipment th, table.equipment td { border: 1px solid #000; padding: 3px 6px; }
        table.equipment th { font-weight: bold; background: #f0f0f0; text-align: center; }
        table.equipment td.amt { text-align: right; }
        table.equipment .total-row td { font-weight: bold; border-top: 2px solid #000; }

        table.fees { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 8px; }
        table.fees th, table.fees td { border: 1px solid #000; padding: 4px 8px; }
        table.fees th { font-weight: bold; background: #f0f0f0; text-align: center; }
        table.fees td.amt { text-align: right; }

        .sig-block { text-align: center; margin-top: 24px; }
        .sig-line { border-bottom: 1px solid #000; min-width: 240px; display: inline-block; }
        .sig-label { font-size: 8.5px; margin-top: 2px; }

        .footer-note { margin-top: 16px; font-size: 8px; text-align: center; color: #555; }
    </style>
</head>
<body>

@php
    $kindLabel = ($application->application_kind ?? 'new') === 'yearly' ? 'YEARLY (ANNUAL RE-INSPECTION)' : 'NEW';
    $collection = $application->collections->where('status', 'active')->first();
    $groupCode = $aiUnit->group_code ?? '';
    $isPerUnit = in_array($groupCode, ['ELEV', 'ESC'], true);
@endphp

<div class="header">
    <div class="repub">Republic of the Philippines</div>
    <div class="city">{{ $settings['general.city'] ?? 'City of San Fernando' }}</div>
    <div class="office">OFFICE OF THE BUILDING OFFICIAL</div>
    <div class="title">ANNUAL INSPECTION CERTIFICATE</div>
    <div class="subtitle">{{ $aiGroupLabel }}</div>
</div>

<div class="top-row">
    <div class="cell">
        Permit No.
        <span class="fill">{{ $permit->permit_number }}</span>
    </div>
    <div class="cell">
        Application No.
        <span class="fill">{{ $application->application_number }}</span>
    </div>
</div>

<div class="box">
    <div class="box-title">APPLICATION DETAILS</div>
    <div class="field-row">
        <div class="f" style="width:60%"><span class="lbl">Name of Owner/Lessee</span><div class="val">{{ strtoupper($application->owner_name ?? '') }}</div></div>
        <div class="f" style="width:40%"><span class="lbl">Application Kind</span><div class="val">{{ $kindLabel }}</div></div>
    </div>
    <div class="field-row">
        <div class="f" style="width:60%"><span class="lbl">Location — Street/Bldg.</span><div class="val">{{ $application->location_street ?? '' }}</div></div>
        <div class="f" style="width:40%"><span class="lbl">Barangay</span><div class="val">{{ $application->locationBarangay?->name ?? '' }}</div></div>
    </div>
</div>

<div class="box">
    <div class="box-title">EQUIPMENT/FEES COVERED @if($aiGroupLabel) &mdash; {{ $aiGroupLabel }} @endif</div>
    @if($isPerUnit)
    <div class="field-row">
        <div class="f" style="width:70%"><span class="lbl">Description</span><div class="val">{{ $aiUnit->description ?? '' }}</div></div>
        <div class="f" style="width:30%"><span class="lbl">Quantity</span><div class="val">1</div></div>
    </div>
    @else
    <table class="equipment">
        <tr>
            <th style="width:55%">Description</th>
            <th style="width:15%">Qty</th>
            <th style="width:30%">Amount</th>
        </tr>
        @forelse($aiGroupItems as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td style="text-align:center;">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
            <td class="amt">&#8369;{{ number_format($item->amount + $item->inspection_fee, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="3" style="text-align:center;color:#666;">No assessed items.</td></tr>
        @endforelse
        <tr class="total-row">
            <td colspan="2" style="text-align:right;">TOTAL</td>
            <td class="amt">&#8369;{{ number_format($aiUnit->amount ?? 0, 2) }}</td>
        </tr>
    </table>
    @endif
</div>

<table class="fees">
    <tr>
        <th>Amount</th>
        <th>O.R. Number</th>
        <th>Date Paid</th>
    </tr>
    <tr>
        <td class="amt">&#8369;{{ number_format($aiUnit->amount ?? 0, 2) }}</td>
        <td style="text-align:center;">{{ $collection->or_number ?? '' }}</td>
        <td style="text-align:center;">{{ $collection?->or_date ? \Carbon\Carbon::parse($collection->or_date)->format('m/d/Y') : '' }}</td>
    </tr>
</table>

<div class="sig-block">
    <span class="sig-line">&nbsp;</span>
    <div class="sig-label">
        <strong>{{ strtoupper(trim(($permit->building_official_title ?? '') . ' ' . ($permit->building_official_name ?? ''))) }}</strong><br>
        BUILDING OFFICIAL<br>
        {{ $permit->building_official_designation ?? '' }}<br>
        (Signature Over Printed Name)<br>
        Date: {{ $permit->issued_date ? \Carbon\Carbon::parse($permit->issued_date)->format('m/d/Y') : '' }}
    </div>
</div>

<div class="footer-note">
    This is a computer-generated document. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}
</div>

</body>
</html>
