<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official Receipt</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header .municipality {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .header .department {
            font-size: 12px;
            margin-bottom: 8px;
        }
        .header .title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .or-info {
            text-align: right;
            margin-bottom: 15px;
        }
        .or-info p {
            font-size: 12px;
        }
        .or-info .or-number {
            font-size: 14px;
            font-weight: bold;
            color: #c00;
        }
        .paid-by {
            margin-bottom: 15px;
            padding: 8px 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
        }
        .paid-by span {
            font-weight: bold;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.items th,
        table.items td {
            border: 1px solid #333;
            padding: 8px 10px;
            text-align: left;
            font-size: 12px;
        }
        table.items th {
            background-color: #e8e8e8;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
        }
        table.items td.amount {
            text-align: right;
            white-space: nowrap;
        }
        table.items tfoot td {
            font-weight: bold;
            font-size: 13px;
            background-color: #f5f5f5;
        }
        .payment-details {
            margin-bottom: 20px;
        }
        .payment-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-details td {
            padding: 5px 10px;
            font-size: 12px;
            border-bottom: 1px solid #eee;
        }
        .payment-details td.label {
            font-weight: bold;
            width: 180px;
            color: #555;
        }
        .payment-details td.value {
            text-align: right;
        }
        .signature-block {
            margin-top: 40px;
            text-align: center;
            width: 250px;
        }
        .signature-block .line {
            border-top: 1px solid #333;
            padding-top: 5px;
            font-size: 11px;
            font-weight: bold;
        }
        .signature-block .designation {
            font-size: 10px;
            color: #555;
        }
        .footer-note {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #777;
            text-align: center;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <p class="municipality">Republic of the Philippines</p>
            <p class="department">Office of the Building Official — Engineering Department</p>
            <p class="title">Official Receipt</p>
        </div>

        {{-- OR Number and Date --}}
        <div class="or-info">
            <p class="or-number">OR No.: {{ $collection->or_number }}</p>
            <p>Date: {{ \Carbon\Carbon::parse($collection->or_date)->format('F d, Y') }}</p>
        </div>

        {{-- Paid By --}}
        <div class="paid-by">
            <span>Received from:</span> {{ $collection->paid_by }}
            @if($collection->application)
                <br><span>Application No.:</span> {{ $collection->application->application_number }}
                <br><span>Applicant:</span> {{ $collection->application->applicant_full_name }}
            @endif
        </div>

        {{-- Fee Items Table --}}
        <table class="items">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="width: 150px; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @if($collection->billing && $collection->billing->billingItems)
                    @foreach($collection->billing->billingItems as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="amount">&#8369;{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <td>Total Amount Due</td>
                    <td class="amount">&#8369;{{ number_format($collection->amount_due, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Payment Details --}}
        <div class="payment-details">
            <table>
                <tr>
                    <td class="label">Payment Mode:</td>
                    <td class="value">{{ ucfirst($collection->payment_mode) }}</td>
                </tr>
                @if($collection->payment_mode === 'check')
                <tr>
                    <td class="label">Bank Name:</td>
                    <td class="value">{{ $collection->bank_name }}</td>
                </tr>
                <tr>
                    <td class="label">Check Number:</td>
                    <td class="value">{{ $collection->check_number }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Amount Received:</td>
                    <td class="value" style="font-weight: bold; font-size: 14px;">&#8369;{{ number_format($collection->amount_received, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Change:</td>
                    <td class="value">&#8369;{{ number_format($collection->change_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- Collector Signature --}}
        <div class="signature-block">
            <div class="line">
                {{ $collection->collectedBy->full_name ?? '' }}
            </div>
            <div class="designation">Collecting Officer</div>
        </div>

        {{-- Footer --}}
        <div class="footer-note">
            This official receipt is valid proof of payment. Please keep this for your records.
        </div>
    </div>
</body>
</html>
