<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { margin: 14mm 14mm 14mm 14mm; size: A4 portrait; }
body { font-family: Arial, sans-serif; font-size: 9.5px; color: #000; line-height: 1.35; }

/* ── Header ── */
.hdr { text-align: center; margin-bottom: 6px; }
.hdr p  { margin: 1px 0; font-size: 9.5px; }
.hdr .city { font-weight: bold; font-size: 11px; }
.hdr .office { text-decoration: underline; font-weight: bold; font-size: 10px; margin-top: 4px; }

/* ── App info block ── */
.info-wrap { width: 100%; margin-bottom: 6px; }
.info-wrap td { vertical-align: top; padding: 1px 0; }
.info-label { font-weight: normal; }
.info-val { font-weight: bold; text-decoration: underline; }
.barcode-cell { text-align: right; vertical-align: top; padding-left: 8px; }
.barcode { font-family: monospace; font-size: 7px; letter-spacing: 1px; word-break: break-all; border: 1px solid #000; padding: 2px 4px; display: inline-block; }

/* ── Title ── */
.soc-title { text-align: center; font-weight: bold; text-decoration: underline; font-size: 11px; margin: 8px 0 10px; }

/* ── Section layout ── */
.section { margin-bottom: 8px; }
.sec-header { font-weight: bold; font-size: 9.5px; margin-bottom: 2px; }

/* ── Fee table (shared) ── */
table.ft { width: 100%; border-collapse: collapse; font-size: 9px; }
table.ft td { padding: 1.5px 3px; vertical-align: top; }
table.ft .col-desc { width: 38%; }
table.ft .col-unit { width: 8%;  text-align: right; }
table.ft .col-fee  { width: 10%; text-align: right; }
table.ft .col-add  { width: 12%; text-align: right; }
table.ft .col-amt  { width: 14%; text-align: right; border-bottom: 1px solid #000; }
table.ft .col-sub  { width: 14%; text-align: right; font-weight: bold; }
table.ft .col-sub-placeholder { width: 14%; }
table.ft .th { font-weight: normal; font-size: 8.5px; border-bottom: 1px solid #555; padding-bottom: 1px; }
table.ft .section-total td { font-weight: bold; }
table.ft .sec-total-amt { text-align: right; font-weight: bold; border-top: 1px solid #000; }

/* ── Grand total ── */
.grand-total { text-align: right; font-weight: bold; font-size: 10.5px; margin-top: 10px; padding-top: 4px; border-top: 2px solid #000; }

/* ── Signatures ── */
.sig-wrap { width: 100%; margin-top: 30px; }
.sig-wrap td { width: 50%; vertical-align: top; padding: 0 10px; }
.sig-name { font-weight: bold; text-align: center; font-size: 9.5px; margin-top: 2px; }
.sig-title { text-align: center; font-size: 8.5px; }
.sig-line { border-top: 1px solid #000; margin-top: 28px; padding-top: 2px; font-size: 8.5px; }
</style>
</head>
<body>

{{-- ═══════════════ HEADER ═══════════════ --}}
<div class="hdr">
    <p>Republic of the Philippines</p>
    <p class="city">{{ strtoupper($settings['general.city'] ?? 'CITY') }}, {{ strtoupper($settings['general.province'] ?? 'PROVINCE') }}</p>
    <p class="office">OFFICE OF THE CITY ENGINEER</p>
</div>

{{-- ═══════════════ APP INFO ═══════════════ --}}
@php
    $applicantName = trim(
        ($application->applicant_last_name ?? '') . ', ' .
        ($application->applicant_first_name ?? '') .
        ($application->applicant_middle_name ? ' ' . $application->applicant_middle_name : '')
    );
    $location = collect([$application->building_street ?? '', $barangayName, ($settings['general.city'] ?? ''), ($settings['general.province'] ?? '')])
                ->filter()->join(', ');
    $printDate = now()->format('m/d/Y');
    $barcodeVal = ($application->permitType?->code ?? 'BP') . '-' . $application->app_year . '-' . str_pad($application->app_month,2,'0',STR_PAD_LEFT) . '-' . str_pad($application->app_counter,5,'0',STR_PAD_LEFT);
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
                    <td class="info-label">Project Title:</td>
                    <td class="info-val">{{ $application->project_title ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label" style="white-space:nowrap">Owner/Applicant:</td>
                    <td class="info-val">{{ $applicantName ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Location:</td>
                    <td class="info-val">{{ $location ?: '—' }}</td>
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
<div class="soc-title">SUMMARY OF COMPUTATION</div>

@php
/* ── Helpers ── */
$letter = function(int $n): string {
    return chr(96 + $n); // a, b, c...
};
$php = function($v): string {
    return 'P &nbsp;' . number_format((float)$v, 2);
};

/* ── Per-section totals tracker ── */
$grandTotal = 0;

/* ── Category item groups ── */
$constItems    = $itemsByCategory->get('CONST',    collect());
$elecItems     = $itemsByCategory->get('ELEC',     collect());
$mechItems     = $itemsByCategory->get('MECH',     collect());
$plumbItems    = $itemsByCategory->get('PLUMB',    collect());
$electItems    = $itemsByCategory->get('ELECT',    collect());
$accBldgItems  = $itemsByCategory->get('ACC_BLDG', collect());
$accFeeItems   = $itemsByCategory->get('ACC_FEE',  collect());
$surchargeItems= $itemsByCategory->get('SURCHARGE',collect());

/* ── Filing / processing from building assessment ── */
$filingFee    = (float)($buildingAssessment?->filing_fee    ?? 0);
$processingFee= (float)($buildingAssessment?->processing_fee?? 0);
@endphp

{{-- ═══════════════ 1. ZONING FEES ═══════════════ --}}
@php
$zoningLC   = $zoningByCategory->get('ZONING_LC',   collect());
$zoningCert = $zoningByCategory->get('ZONING_CERT',  collect());
$allZoning  = $zoningLC->merge($zoningCert);
$zoningSub  = $allZoning->sum('amount');
$grandTotal += $zoningSub;
@endphp
<div class="section">
    <div class="sec-header">1. ZONING FEES</div>
    <table class="ft" cellpadding="0" cellspacing="0">
        <tr>
            <td class="col-desc th">Details</td>
            <td colspan="3" class="th"></td>
            <td class="col-amt th" style="text-align:right">Total Amount</td>
            <td class="col-sub-placeholder th"></td>
        </tr>
        @forelse($allZoning as $idx => $item)
        @php $isLast = $idx === $allZoning->count() - 1; @endphp
        <tr>
            <td class="col-desc">{{ $item->description }}</td>
            <td colspan="3"></td>
            <td class="col-amt">P &nbsp;{{ number_format($item->amount, 2) }}</td>
            <td class="col-sub">@if($isLast && $zoningSub > 0)P &nbsp;{{ number_format($zoningSub, 2) }}@endif</td>
        </tr>
        @empty
        <tr><td class="col-desc" style="color:#888;font-style:italic">—</td><td colspan="4"></td><td class="col-sub"></td></tr>
        @endforelse
    </table>
</div>

{{-- ═══════════════ 2. BUILDING CONSTRUCTION FEES ═══════════════ --}}
@php
$constSub = $constItems->sum('amount');
$grandTotal += $constSub;
@endphp
<div class="section">
    <div class="sec-header">2. BUILDING CONSTRUCTION FEES</div>
    <table class="ft" cellpadding="0" cellspacing="0">
        <tr>
            <td class="col-desc th">Details</td>
            <td class="col-unit th" style="text-align:left">Unit</td>
            <td colspan="2" class="th"></td>
            <td class="col-amt th" style="text-align:right">Total Amount</td>
            <td class="col-sub-placeholder th"></td>
        </tr>
        @forelse($constItems as $idx => $item)
        @php
            $cd = is_array($item->computation_details) ? $item->computation_details : (json_decode($item->computation_details ?? '{}', true));
            $method = ucfirst(str_replace('_', ' ', $cd['computation_method'] ?? ''));
            $unitLabel = number_format($item->quantity, 2) . ' m² ' . $method;
            $isLast = $idx === $constItems->count() - 1;
        @endphp
        <tr>
            <td class="col-desc">{{ chr(96 + $idx + 1) }}) {{ $item->description }}</td>
            <td class="col-unit" style="text-align:left; white-space:nowrap">{{ $unitLabel }}</td>
            <td colspan="2"></td>
            <td class="col-amt">P &nbsp;{{ number_format($item->amount, 2) }}</td>
            <td class="col-sub">@if($isLast && $constSub > 0)P &nbsp;{{ number_format($constSub, 2) }}@endif</td>
        </tr>
        @empty
        <tr><td class="col-desc" style="color:#888;font-style:italic">—</td><td colspan="4"></td><td class="col-sub"></td></tr>
        @endforelse
    </table>
</div>

{{-- ═══════════════ 4. ELECTRICAL FEES ═══════════════ --}}
@php
$elecAmt  = $elecItems->sum('amount');
$elecInsp = $elecItems->sum('inspection_fee');
$elecSub  = $elecAmt + $elecInsp;
$grandTotal += $elecSub;
@endphp
<div class="section">
    <div class="sec-header">3. ELECTRICAL FEES</div>
    <table class="ft" cellpadding="0" cellspacing="0">
        <tr>
            <td class="col-desc th">Details</td>
            <td class="col-unit th">Unit</td>
            <td class="col-fee th">Fee</td>
            <td class="col-add th">Additional Fee</td>
            <td class="col-amt th" style="text-align:right">Total Amount</td>
            <td class="col-sub-placeholder th"></td>
        </tr>
        @forelse($elecItems as $idx => $item)
        <tr>
            <td class="col-desc">{{ chr(96 + $idx + 1) }}) {{ $item->description }}</td>
            <td class="col-unit">{{ $item->quantity > 0 ? number_format($item->quantity, 2) : '' }}</td>
            <td class="col-fee">{{ $item->unit_fee > 0 ? number_format($item->unit_fee, 2) : '' }}</td>
            <td class="col-add">{{ number_format($item->excess_fee, 2) }}</td>
            <td class="col-amt">P &nbsp;{{ number_format($item->amount, 2) }}</td>
            <td class="col-sub"></td>
        </tr>
        @empty
        @endforelse
        @if($elecItems->isNotEmpty())
        <tr>
            <td class="col-desc">{{ chr(96 + $elecItems->count() + 1) }}) Electrical Inspection Fee</td>
            <td class="col-unit"></td>
            <td class="col-fee"></td>
            <td class="col-add"></td>
            <td class="col-amt">P &nbsp;{{ number_format($elecInsp, 2) }}</td>
            <td class="col-sub">P &nbsp;{{ number_format($elecSub, 2) }}</td>
        </tr>
        @else
        <tr><td class="col-desc" style="color:#888;font-style:italic">—</td><td colspan="4"></td><td class="col-sub"></td></tr>
        @endif
    </table>
</div>

{{-- ═══════════════ 5. MECHANICAL FEES ═══════════════ --}}
@php
$mechAmt  = $mechItems->sum('amount');
$mechInsp = $mechItems->sum('inspection_fee');
$mechSub  = $mechAmt + $mechInsp;
$grandTotal += $mechSub;
@endphp
<div class="section">
    <div class="sec-header">4. MECHANICAL FEES</div>
    <table class="ft" cellpadding="0" cellspacing="0">
        <tr>
            <td class="col-desc th">Details</td>
            <td class="col-unit th">Unit</td>
            <td class="col-fee th">Fee</td>
            <td class="col-add th">Additional Fee</td>
            <td class="col-amt th" style="text-align:right">Total Amount</td>
            <td class="col-sub-placeholder th"></td>
        </tr>
        @forelse($mechItems as $idx => $item)
        <tr>
            <td class="col-desc">{{ chr(96 + $idx + 1) }}) {{ $item->description }}</td>
            <td class="col-unit">{{ $item->quantity > 0 ? number_format($item->quantity, 2) : '' }}</td>
            <td class="col-fee">{{ $item->unit_fee > 0 ? number_format($item->unit_fee, 2) : '' }}</td>
            <td class="col-add">{{ number_format($item->excess_fee, 2) }}</td>
            <td class="col-amt">P &nbsp;{{ number_format($item->amount, 2) }}</td>
            <td class="col-sub"></td>
        </tr>
        @empty
        @endforelse
        @if($mechItems->isNotEmpty())
        <tr>
            <td class="col-desc">{{ chr(96 + $mechItems->count() + 1) }}) Mechanical Inspection Fee</td>
            <td class="col-unit"></td>
            <td class="col-fee"></td>
            <td class="col-add"></td>
            <td class="col-amt">P &nbsp;{{ number_format($mechInsp, 2) }}</td>
            <td class="col-sub">P &nbsp;{{ number_format($mechSub, 2) }}</td>
        </tr>
        @else
        <tr><td class="col-desc" style="color:#888;font-style:italic">—</td><td colspan="4"></td><td class="col-sub"></td></tr>
        @endif
    </table>
</div>

{{-- ═══════════════ 6. SANITARY/PLUMBING FEES ═══════════════ --}}
@php
$plumbSub = $plumbItems->sum('amount') + $plumbItems->sum('inspection_fee');
$grandTotal += $plumbSub;
@endphp
<div class="section">
    <div class="sec-header">5. SANITARY/PLUMBING FEES</div>
    <table class="ft" cellpadding="0" cellspacing="0">
        <tr>
            <td class="col-desc th">Details</td>
            <td class="col-unit th">Unit</td>
            <td class="col-fee th">Fee</td>
            <td class="col-add th">Additional Fee</td>
            <td class="col-amt th" style="text-align:right">Total Amount</td>
            <td class="col-sub-placeholder th"></td>
        </tr>
        @forelse($plumbItems as $idx => $item)
        @php $isLast = $idx === $plumbItems->count() - 1; @endphp
        <tr>
            <td class="col-desc">{{ chr(96 + $idx + 1) }}) {{ $item->description }}</td>
            <td class="col-unit">{{ $item->quantity > 0 ? number_format($item->quantity, 2) : '' }}</td>
            <td class="col-fee">{{ $item->unit_fee > 0 ? number_format($item->unit_fee, 2) : '' }}</td>
            <td class="col-add">{{ number_format($item->excess_fee, 2) }}</td>
            <td class="col-amt">P &nbsp;{{ number_format($item->amount, 2) }}</td>
            <td class="col-sub">@if($isLast && $plumbSub > 0)P &nbsp;{{ number_format($plumbSub, 2) }}@endif</td>
        </tr>
        @empty
        <tr><td class="col-desc" style="color:#888;font-style:italic">—</td><td colspan="4"></td><td class="col-sub"></td></tr>
        @endforelse
    </table>
</div>

{{-- ═══════════════ 7. ELECTRONICS FEES ═══════════════ --}}
@php
$electAmt  = $electItems->sum('amount');
$electInsp = $electItems->sum('inspection_fee');
$electSub  = $electAmt + $electInsp;
$grandTotal += $electSub;
@endphp
<div class="section">
    <div class="sec-header">6. ELECTRONICS FEES</div>
    <table class="ft" cellpadding="0" cellspacing="0">
        <tr>
            <td class="col-desc th">Details</td>
            <td class="col-unit th">Unit</td>
            <td class="col-fee th">Fee</td>
            <td class="col-add th">Additional Fee</td>
            <td class="col-amt th" style="text-align:right">Total Amount</td>
            <td class="col-sub-placeholder th"></td>
        </tr>
        @forelse($electItems as $idx => $item)
        <tr>
            <td class="col-desc">{{ chr(96 + $idx + 1) }}) {{ $item->description }}</td>
            <td class="col-unit">{{ $item->quantity > 0 ? number_format($item->quantity, 2) : '' }}</td>
            <td class="col-fee">{{ $item->unit_fee > 0 ? number_format($item->unit_fee, 2) : '' }}</td>
            <td class="col-add">{{ number_format($item->excess_fee, 2) }}</td>
            <td class="col-amt">P &nbsp;{{ number_format($item->amount, 2) }}</td>
            <td class="col-sub"></td>
        </tr>
        @empty
        <tr><td class="col-desc" style="color:#888;font-style:italic">—</td><td colspan="4"></td><td class="col-sub"></td></tr>
        @endforelse
        @if($electItems->isNotEmpty())
        <tr>
            <td class="col-desc">{{ chr(96 + $electItems->count() + 1) }}) Electronics Inspection Fee</td>
            <td colspan="3"></td>
            <td class="col-amt">P &nbsp;{{ number_format($electInsp, 2) }}</td>
            <td class="col-sub">P &nbsp;{{ number_format($electSub, 2) }}</td>
        </tr>
        @endif
    </table>
</div>

{{-- ═══════════════ 8. ACCESSORIES OF THE BUILDING/STRUCTURE ═══════════════ --}}
@php
$accBldgSub = $accBldgItems->sum('amount');
$grandTotal += $accBldgSub;
@endphp
<div class="section">
    <div class="sec-header">7. ACCESSORIES OF THE BUILDING/STRUCTURE</div>
    <table class="ft" cellpadding="0" cellspacing="0">
        <tr>
            <td class="col-desc th">Details</td>
            <td class="col-unit th">Unit</td>
            <td class="col-fee th">Fee</td>
            <td class="col-add th">Additional Fee</td>
            <td class="col-amt th" style="text-align:right">Total Amount</td>
            <td class="col-sub-placeholder th"></td>
        </tr>
        @forelse($accBldgItems as $idx => $item)
        @php $isLast = $idx === $accBldgItems->count() - 1; @endphp
        <tr>
            <td class="col-desc">{{ chr(96 + $idx + 1) }}) {{ $item->description }}</td>
            <td class="col-unit">{{ $item->quantity > 0 ? number_format($item->quantity, 2) : '' }}</td>
            <td class="col-fee">{{ $item->unit_fee > 0 ? number_format($item->unit_fee, 2) : '' }}</td>
            <td class="col-add">{{ number_format($item->excess_fee, 2) }}</td>
            <td class="col-amt">P &nbsp;{{ number_format($item->amount, 2) }}</td>
            <td class="col-sub">@if($isLast && $accBldgSub > 0)P &nbsp;{{ number_format($accBldgSub, 2) }}@endif</td>
        </tr>
        @empty
        <tr><td class="col-desc" style="color:#888;font-style:italic">—</td><td colspan="4"></td><td class="col-sub"></td></tr>
        @endforelse
    </table>
</div>

{{-- ═══════════════ 9. ACCESSORY FEES ═══════════════ --}}
@php
$accFeeSub = $accFeeItems->sum('amount');
$grandTotal += $accFeeSub;
@endphp
<div class="section">
    <div class="sec-header">8. ACCESSORY FEES</div>
    <table class="ft" cellpadding="0" cellspacing="0">
        <tr>
            <td class="col-desc th">Details</td>
            <td class="col-unit th">Unit</td>
            <td class="col-fee th">Fee</td>
            <td class="col-add th">Additional Fee</td>
            <td class="col-amt th" style="text-align:right">Total Amount</td>
            <td class="col-sub-placeholder th"></td>
        </tr>
        @forelse($accFeeItems as $idx => $item)
        @php $isLast = $idx === $accFeeItems->count() - 1; @endphp
        <tr>
            <td class="col-desc">{{ chr(96 + $idx + 1) }}) {{ $item->description }}</td>
            <td class="col-unit">{{ $item->quantity > 0 ? number_format($item->quantity, 2) : '' }}</td>
            <td class="col-fee">{{ $item->unit_fee > 0 ? number_format($item->unit_fee, 2) : '' }}</td>
            <td class="col-add">{{ number_format($item->excess_fee, 2) }}</td>
            <td class="col-amt">P &nbsp;{{ number_format($item->amount, 2) }}</td>
            <td class="col-sub">@if($isLast && $accFeeSub > 0)P &nbsp;{{ number_format($accFeeSub, 2) }}@endif</td>
        </tr>
        @empty
        <tr><td class="col-desc" style="color:#888;font-style:italic">—</td><td colspan="4"></td><td class="col-sub"></td></tr>
        @endforelse
    </table>
</div>

{{-- ═══════════════ 10. SURCHARGE FEES ═══════════════ --}}
@php
$surchargeSub = $surchargeItems->sum('amount');
$grandTotal  += $surchargeSub;
@endphp
<div class="section">
    <div class="sec-header">9. SURCHARGE FEES</div>
    <table class="ft" cellpadding="0" cellspacing="0">
        @forelse($surchargeItems as $idx => $item)
        @php $isLast = $idx === $surchargeItems->count() - 1; @endphp
        <tr>
            <td class="col-desc">{{ chr(96 + $idx + 1) }}) {{ $item->description }}</td>
            <td colspan="3"></td>
            <td class="col-amt">P &nbsp;{{ number_format($item->amount, 2) }}</td>
            <td class="col-sub">@if($isLast && $surchargeSub > 0)P &nbsp;{{ number_format($surchargeSub, 2) }}@endif</td>
        </tr>
        @empty
        @endforelse
</table>
</div>

{{-- ═══════════════ 11. OTHER FEES ═══════════════ --}}
@php
$otherSub    = $filingFee + $processingFee;
$grandTotal += $otherSub;
@endphp
<div class="section">
    <div class="sec-header">10. OTHER FEES</div>
    <table class="ft" cellpadding="0" cellspacing="0">
        <tr>
            <td class="col-desc">a) Filing Fee</td>
            <td colspan="3"></td>
            <td class="col-amt">P &nbsp;{{ number_format($filingFee, 2) }}</td>
            <td class="col-sub"></td>
        </tr>
        <tr>
            <td class="col-desc">b) Processing Fee</td>
            <td colspan="3"></td>
            <td class="col-amt">P &nbsp;{{ number_format($processingFee, 2) }}</td>
            <td class="col-sub">P &nbsp;{{ number_format($otherSub, 2) }}</td>
        </tr>
    </table>
</div>

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
