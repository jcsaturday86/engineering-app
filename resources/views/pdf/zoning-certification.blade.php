<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Zoning Certification</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 25mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 100%;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #333;
            padding-bottom: 12px;
        }
        .header .republic {
            font-size: 11px;
            margin-bottom: 2px;
        }
        .header .municipality {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .header .office {
            font-size: 12px;
            margin-bottom: 8px;
        }
        .header .title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 5px;
        }
        .cert-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .cert-info .cert-number {
            font-size: 13px;
            font-weight: bold;
            color: #c00;
        }
        .cert-info .cert-date {
            font-size: 11px;
            margin-top: 3px;
        }
        .body-text {
            text-align: justify;
            margin-bottom: 15px;
            font-size: 11px;
            line-height: 1.8;
            text-indent: 40px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #e8e8e8;
            padding: 5px 8px;
            border: 1px solid #999;
            margin-bottom: 0;
        }
        .section-body {
            border: 1px solid #999;
            border-top: none;
            padding: 10px;
            margin-bottom: 15px;
        }
        .field-row {
            margin-bottom: 6px;
            overflow: hidden;
        }
        .field-label {
            font-weight: bold;
            font-size: 10px;
            color: #555;
            text-transform: uppercase;
        }
        .field-value {
            font-size: 11px;
            padding: 2px 0;
            border-bottom: 1px dotted #999;
            min-height: 16px;
        }
        .fee-section {
            margin-bottom: 15px;
        }
        table.fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.fee-table th,
        table.fee-table td {
            border: 1px solid #999;
            padding: 5px 8px;
            font-size: 11px;
            text-align: left;
        }
        table.fee-table th {
            background-color: #e8e8e8;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        table.fee-table td.amount {
            text-align: right;
            white-space: nowrap;
        }
        table.fee-table tfoot td {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        .signature-block {
            margin-top: 50px;
            width: 280px;
            float: right;
            text-align: center;
        }
        .signature-block .sig-line {
            border-top: 1px solid #333;
            padding-top: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 40px;
        }
        .signature-block .sig-title {
            font-size: 10px;
            color: #555;
        }
        .signature-block .sig-designation {
            font-size: 10px;
            color: #777;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <p class="republic">Republic of the Philippines</p>
            <p class="municipality">{{ config('app.municipality_name', 'City/Municipality Name') }}</p>
            <p class="office">Office of the Building Official</p>
            <p class="title">Zoning Certification</p>
        </div>

        {{-- Certificate Number and Date --}}
        <div class="cert-info">
            <p class="cert-number">Certificate No.: {{ $application->zoningAssessment?->decision_no ?? '___________' }}</p>
            <p class="cert-date">Date: {{ $application->zoningAssessment?->certificate_date?->format('F d, Y') ?? '___________' }}</p>
        </div>

        {{-- Certification Body Text --}}
        <p class="body-text">
            This is to certify that the project <strong>{{ $application->project_title ?? '___________' }}</strong>
            of <strong>{{ $application->applicant_full_name }}</strong>
            located at <strong>{{ $application->building_street }}{{ $application->buildingBarangay ? ', Brgy. ' . $application->buildingBarangay->name : '' }}</strong>
            has been found to be in conformity with the zoning regulations and the Comprehensive Land Use Plan of the City/Municipality.
        </p>

        {{-- Project Details --}}
        <div class="section-title">Project Details</div>
        <div class="section-body">
            <div class="field-row">
                <div class="field-label">Project Classification</div>
                <div class="field-value">{{ $application->zoningAssessment?->project_classification ?? '' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">Site Zoning Classification</div>
                <div class="field-value">{{ $application->zoningAssessment?->site_zoning_classification ?? '' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">Land Use</div>
                <div class="field-value">{{ $application->zoningAssessment?->right_over_lands ?? '' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">Project Status</div>
                <div class="field-value">{{ $application->zoningAssessment?->project_status ?? '' }}</div>
            </div>
        </div>

        {{-- Fee Paid --}}
        @php
            $zoningFees = collect();
            $zoningFeeTotal = 0;
            if ($application->collections) {
                foreach ($application->collections as $collection) {
                    if ($collection->collectionDetails) {
                        $filtered = $collection->collectionDetails->filter(function ($detail) {
                            return stripos($detail->fee_category, 'Zoning') !== false;
                        });
                        $zoningFees = $zoningFees->merge($filtered);
                    }
                }
                $zoningFeeTotal = $zoningFees->sum('amount');
            }
        @endphp

        @if($zoningFees->count())
            <div class="fee-section">
                <div class="section-title">Fees Paid</div>
                <table class="fee-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="width: 150px; text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($zoningFees as $fee)
                        <tr>
                            <td>{{ $fee->description }}</td>
                            <td class="amount">&#8369;{{ number_format($fee->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td class="amount">&#8369;{{ number_format($zoningFeeTotal, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        {{-- Signatory --}}
        <div class="signature-block">
            @if(isset($signatories['planning_officer']))
                <div class="sig-line">{{ $signatories['planning_officer']->name }}</div>
                @if($signatories['planning_officer']->title)
                    <div class="sig-title">{{ $signatories['planning_officer']->title }}</div>
                @endif
                @if($signatories['planning_officer']->designation)
                    <div class="sig-designation">{{ $signatories['planning_officer']->designation }}</div>
                @endif
            @else
                <div class="sig-line">&nbsp;</div>
                <div class="sig-title">Planning Officer</div>
            @endif
        </div>
    </div>
</body>
</html>
