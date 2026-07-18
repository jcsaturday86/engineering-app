<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { margin: 14mm 14mm 14mm 14mm; size: A4 portrait; }
body { font-family: Arial, sans-serif; font-size: 11.5px; color: #000; line-height: 1.35; }

/* ── Header ── */
.hdr { text-align: center; margin-bottom: 6px; }
.hdr img.seal { height: 68px; margin-bottom: 4px; }
.hdr p  { margin: 1px 0; font-size: 11.5px; }
.hdr .city { font-weight: bold; font-size: 13.5px; }
.hdr .office { text-decoration: underline; font-weight: bold; font-size: 12px; margin-top: 4px; }

/* ── App info block ── */
.info-wrap { width: 100%; margin-bottom: 6px; }
.info-wrap td { vertical-align: top; padding: 1px 0; }
.info-label { font-weight: normal; }
.info-val { font-weight: bold; text-decoration: underline; }
.barcode-cell { text-align: right; vertical-align: top; padding-left: 8px; }
.barcode { font-family: monospace; font-size: 8.5px; letter-spacing: 1px; word-break: break-all; border: 1px solid #000; padding: 2px 4px; display: inline-block; }

/* ── Title ── */
.soc-title { text-align: center; font-weight: bold; text-decoration: underline; font-size: 13.5px; margin: 8px 0 2px; }
.soc-subtitle { text-align: center; font-weight: bold; font-size: 12px; margin: 0 0 10px; }

/* ── Section layout ── */
.section { margin-bottom: 8px; }
.sec-header { font-weight: bold; font-size: 11.5px; margin-bottom: 2px; }

/* ── Fee table (shared) ── */
table.ft { width: 100%; border-collapse: collapse; font-size: 11px; }
table.ft td { padding: 1.5px 3px; vertical-align: top; }
table.ft .col-desc { width: 38%; }
table.ft .col-unit { width: 8%;  text-align: right; }
table.ft .col-fee  { width: 10%; text-align: right; }
table.ft .col-add  { width: 12%; text-align: right; }
table.ft .col-amt  { width: 14%; text-align: right; border-bottom: 1px solid #000; }
table.ft .col-sub  { width: 14%; text-align: right; font-weight: bold; }
table.ft .col-sub-placeholder { width: 14%; }
table.ft .th { font-weight: normal; font-size: 10.5px; border-bottom: 1px solid #555; padding-bottom: 1px; }

/* ── Grand total ── */
.grand-total { text-align: right; font-weight: bold; font-size: 13px; margin-top: 10px; padding-top: 4px; border-top: 2px solid #000; }

/* ── Signatures ── */
.sig-wrap { width: 100%; margin-top: 30px; }
.sig-wrap td { width: 50%; vertical-align: top; padding: 0 10px; }
.sig-name { font-weight: bold; text-align: center; font-size: 11.5px; margin-top: 2px; }
.sig-title { text-align: center; font-size: 10.5px; }
.sig-line { border-top: 1px solid #000; margin-top: 28px; padding-top: 2px; font-size: 10.5px; }
</style>
</head>
<body>

{{-- ═══════════════ HEADER ═══════════════ --}}
<div class="hdr">
    @if(!empty($sealImage))
        <img src="{{ $sealImage }}" class="seal" alt="Official Seal">
    @endif
    <p>Republic of the Philippines</p>
    <p class="city">{{ strtoupper($settings['general.city'] ?? 'CITY') }}, {{ strtoupper($settings['general.province'] ?? 'PROVINCE') }}</p>
    <p class="office">OFFICE OF THE CITY ENGINEER</p>
</div>

{{-- ═══════════════ APP INFO ═══════════════ --}}
@php
    $kindLabel = ($application->application_kind ?? 'new') === 'yearly' ? 'Yearly (Annual Re-Inspection)' : 'New';
    $location = collect([$application->location_street ?? '', $barangayName, ($settings['general.city'] ?? ''), ($settings['general.province'] ?? '')])
                ->filter()->join(', ');
    $printDate = now()->format('m/d/Y');
    $barcodeVal = 'AI-' . $application->app_year . '-' . str_pad($application->app_month,2,'0',STR_PAD_LEFT) . '-' . str_pad($application->app_counter,5,'0',STR_PAD_LEFT);
@endphp
<table class="info-wrap" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:75%">
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td class="info-label" style="width:90px">Date:</td>
                    <td class="info-val">{{ $printDate }}</td>
                </tr>
                <tr>
                    <td class="info-label" style="white-space:nowrap">Owner/Lessee:</td>
                    <td class="info-val">{{ $application->owner_name ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Location:</td>
                    <td class="info-val">{{ $location ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Application Kind:</td>
                    <td class="info-val">{{ $kindLabel }}</td>
                </tr>
            </table>
        </td>
        <td class="barcode-cell" style="width:25%">
            @if(!empty($barcodeImage))
            <img src="data:image/png;base64,{{ $barcodeImage }}"
                 style="width:100%;max-height:70px;display:block;margin-bottom:3px;">
            @endif
            <div class="barcode">{{ $barcodeVal }}</div>
        </td>
    </tr>
</table>

{{-- ═══════════════ TITLE ═══════════════ --}}
<div class="soc-title">ANNUAL INSPECTION ASSESSMENT</div>
<div class="soc-subtitle">SUMMARY OF COMPUTATION</div>

@php
$grandTotal = 0;

$mpSections = [
    'AI_AC' => '1. AIR CONDITIONING / REFRIGERATION FEES',
    'AI_MACH' => '2. MACHINERY FEES',
    'AI_ESC' => '3. ESCALATOR / FUNICULAR / CABLE CAR FEES',
    'AI_ELEV' => '4. ELEVATOR FEES',
    'AI_GENSET' => '5. GENERATOR SET FEES',
];
@endphp

@foreach($mpSections as $catCode => $sectionTitle)
@php
    $sectionItems = $itemsByCategory->get($catCode, collect());
    $sectionSub = $sectionItems->sum('amount') + $sectionItems->sum('inspection_fee');
    $grandTotal += $sectionSub;
@endphp
<div class="section">
    <div class="sec-header">{{ $sectionTitle }}</div>
    <table class="ft" cellpadding="0" cellspacing="0">
        <tr>
            <td class="col-desc th">Details</td>
            <td class="col-unit th">Unit</td>
            <td class="col-fee th">Fee</td>
            <td class="col-add th">Additional Fee</td>
            <td class="col-amt th" style="text-align:right">Total Amount</td>
            <td class="col-sub-placeholder th"></td>
        </tr>
        @forelse($sectionItems as $idx => $item)
        @php $isLast = $idx === $sectionItems->count() - 1; @endphp
        <tr>
            <td class="col-desc">{{ chr(96 + $idx + 1) }}) {{ $item->description }}</td>
            <td class="col-unit">{{ $item->quantity > 0 ? number_format($item->quantity, 2) : '' }}</td>
            <td class="col-fee">{{ $item->unit_fee > 0 ? number_format($item->unit_fee, 2) : '' }}</td>
            <td class="col-add">{{ number_format($item->excess_fee, 2) }}</td>
            <td class="col-amt">P &nbsp;{{ number_format($item->amount, 2) }}</td>
            <td class="col-sub">@if($isLast && $sectionSub > 0)P &nbsp;{{ number_format($sectionSub, 2) }}@endif</td>
        </tr>
        @empty
        <tr><td class="col-desc" style="color:#888;font-style:italic">—</td><td colspan="4"></td><td class="col-sub"></td></tr>
        @endforelse
    </table>
</div>
@endforeach

{{-- ═══════════════ GRAND TOTAL ═══════════════ --}}
<div class="grand-total">
    TOTAL COMPUTATION :&nbsp; P &nbsp;&nbsp;{{ number_format($grandTotal, 2) }}
</div>

{{-- ═══════════════ SIGNATURES ═══════════════ --}}
<table class="sig-wrap" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <div style="font-size:8.5px">Prepared By:</div>
            <div class="sig-line">
                <div class="sig-name">{{ strtoupper($preparedBy?->name ?? '') }}</div>
            </div>
        </td>
        <td>
            <div style="font-size:8.5px">Approved By:</div>
            <div class="sig-line">
                <div class="sig-name">{{ strtoupper(trim(($buildingOfficial?->title ? $buildingOfficial->title . ' ' : '') . ($buildingOfficial?->name ?? ''))) }}</div>
                <div class="sig-title">{{ $buildingOfficial?->designation ?? 'Building Official' }}</div>
            </div>
        </td>
    </tr>
</table>

</body>
</html>
