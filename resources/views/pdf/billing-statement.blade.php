<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20mm 15mm; size: A4 portrait; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h4 { margin: 2px 0; font-size: 11px; text-transform: uppercase; }
        .header h2 { margin: 8px 0 3px; font-size: 16px; }
        .info { margin-bottom: 15px; }
        .info td { padding: 3px 5px; }
        .info .label { font-weight: bold; width: 140px; }
        table.items { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table.items th, table.items td { border: 1px solid #999; padding: 6px 8px; }
        table.items th { background: #f0f0f0; text-align: left; font-size: 9px; text-transform: uppercase; }
        table.items td.amount { text-align: right; }
        table.items tr.total { background: #e0e0e0; font-weight: bold; font-size: 12px; }
        .note { margin-top: 20px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; font-size: 9px; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h4>Republic of the Philippines</h4>
        <h4>City / Municipality</h4>
        <h4>Office of the Building Official</h4>
        <h2>BILLING STATEMENT</h2>
    </div>

    <table class="info" style="width:100%">
        <tr>
            <td class="label">Billing No.:</td>
            <td><strong>{{ $billing->billing_number }}</strong></td>
            <td class="label">Date:</td>
            <td>{{ $billing->created_at->format('F d, Y') }}</td>
        </tr>
        <tr>
            <td class="label">Application No.:</td>
            <td>{{ $billing->application?->application_number }}</td>
            <td class="label">Permit Type:</td>
            <td>{{ $billing->application?->permitType?->name }}</td>
        </tr>
        <tr>
            <td class="label">Applicant:</td>
            <td colspan="3">{{ $billing->application?->applicant_last_name }}, {{ $billing->application?->applicant_first_name }} {{ $billing->application?->applicant_middle_name }}</td>
        </tr>
        <tr>
            <td class="label">Project Title:</td>
            <td colspan="3">{{ $billing->application?->project_title }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:10%">#</th>
                <th style="width:60%">Description</th>
                <th style="width:30%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($billing->billingItems->sortBy('sort_order') as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="amount">&#8369;{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total">
                <td colspan="2" style="text-align:right">TOTAL AMOUNT DUE</td>
                <td class="amount">&#8369;{{ number_format($billing->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="note">
        <strong>Note:</strong> Please present this billing statement at the City Treasurer's Office for payment.
        This billing statement is valid for thirty (30) days from the date of issuance.
    </div>

    <div class="footer">
        Generated on {{ now()->format('F d, Y h:i A') }}
    </div>
</body>
</html>
