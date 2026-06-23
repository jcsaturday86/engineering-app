<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20mm 15mm; size: A4 portrait; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h4 { margin: 0; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        .header h2 { margin: 5px 0 3px; font-size: 14px; }
        .header h3 { margin: 0; font-size: 12px; font-weight: normal; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 3px 5px; vertical-align: top; }
        .info-table .label { font-weight: bold; width: 150px; color: #555; }
        table.fees { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.fees th, table.fees td { border: 1px solid #999; padding: 4px 6px; }
        table.fees th { background: #f0f0f0; font-weight: bold; text-align: left; font-size: 9px; text-transform: uppercase; }
        table.fees td.amount { text-align: right; }
        table.fees tr.subtotal { background: #f5f5f5; font-weight: bold; }
        table.fees tr.total { background: #e0e0e0; font-weight: bold; font-size: 11px; }
        .category-header { background: #e8e8e8; font-weight: bold; font-size: 10px; }
        .signatures { margin-top: 40px; width: 100%; }
        .signatures td { width: 50%; padding: 5px 20px; vertical-align: bottom; text-align: center; }
        .sig-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 3px; }
        .barcode { text-align: center; margin-top: 20px; font-family: monospace; font-size: 12px; letter-spacing: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <h4>Republic of the Philippines</h4>
        <h4>City/Municipality</h4>
        <h4>Office of the Building Official</h4>
        <h2>SUMMARY OF COMPUTATION / ORDER OF PAYMENT</h2>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Application No.:</td>
            <td>{{ $application->application_number }}</td>
            <td class="label">Date:</td>
            <td>{{ now()->format('F d, Y') }}</td>
        </tr>
        <tr>
            <td class="label">Applicant:</td>
            <td>{{ $application->applicant_last_name }}, {{ $application->applicant_first_name }} {{ $application->applicant_middle_name }}</td>
            <td class="label">Permit Type:</td>
            <td>{{ $application->permitType?->name }}</td>
        </tr>
        <tr>
            <td class="label">Project Title:</td>
            <td colspan="3">{{ $application->project_title }}</td>
        </tr>
    </table>

    <table class="fees">
        <thead>
            <tr>
                <th style="width:40%">Description</th>
                <th style="width:15%">Quantity</th>
                <th style="width:15%">Unit Fee</th>
                <th style="width:15%">Inspection Fee</th>
                <th style="width:15%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach($assessments as $assessment)
                @php
                    $items = $assessment->assessmentItems->where('is_active', true);
                    $grouped = $items->groupBy('fee_code');
                @endphp
                @foreach($grouped as $code => $codeItems)
                    <tr class="category-header">
                        <td colspan="5">{{ $codeItems->first()->description }}</td>
                    </tr>
                    @foreach($codeItems as $item)
                    <tr>
                        <td style="padding-left:15px">{{ $item->description }}</td>
                        <td class="amount">{{ number_format($item->quantity, 2) }}</td>
                        <td class="amount">&#8369;{{ number_format($item->unit_fee, 2) }}</td>
                        <td class="amount">&#8369;{{ number_format($item->inspection_fee, 2) }}</td>
                        <td class="amount">&#8369;{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                @endforeach
                @if($assessment->filing_fee > 0)
                <tr>
                    <td>Filing Fee</td>
                    <td></td><td></td><td></td>
                    <td class="amount">&#8369;{{ number_format($assessment->filing_fee, 2) }}</td>
                </tr>
                @endif
                @if($assessment->processing_fee > 0)
                <tr>
                    <td>Processing Fee</td>
                    <td></td><td></td><td></td>
                    <td class="amount">&#8369;{{ number_format($assessment->processing_fee, 2) }}</td>
                </tr>
                @endif
                @php
                    $assessmentTotal = $items->sum('amount') + $items->sum('inspection_fee') + $assessment->filing_fee + $assessment->processing_fee;
                    $grandTotal += $assessmentTotal;
                @endphp
                <tr class="subtotal">
                    <td colspan="4" style="text-align:right">Subtotal ({{ ucfirst($assessment->assessment_type) }})</td>
                    <td class="amount">&#8369;{{ number_format($assessmentTotal, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="4" style="text-align:right">TOTAL AMOUNT DUE</td>
                <td class="amount">&#8369;{{ number_format($grandTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">Prepared by</div>
                <div style="font-size:9px;color:#666">Date: {{ now()->format('F d, Y') }}</div>
            </td>
            <td>
                <div class="sig-line">Approved by</div>
                <div style="font-size:9px;color:#666">Building Official</div>
            </td>
        </tr>
    </table>

    <div class="barcode">{{ $application->permitType?->code }}-{{ $application->app_year }}-{{ str_pad($application->app_month, 2, '0', STR_PAD_LEFT) }}-{{ str_pad($application->app_counter, 5, '0', STR_PAD_LEFT) }}</div>
</body>
</html>
