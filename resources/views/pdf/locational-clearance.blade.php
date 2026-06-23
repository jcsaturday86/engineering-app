<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Decision on Zoning and Locational Clearance</title>
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
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 5px;
        }
        .decision-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .decision-info .decision-number {
            font-size: 13px;
            font-weight: bold;
            color: #c00;
        }
        .decision-info .decision-date {
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
        .findings-box {
            border: 1px solid #999;
            padding: 10px;
            margin-bottom: 15px;
            background-color: #fafafa;
            min-height: 60px;
            white-space: pre-wrap;
            font-size: 11px;
            line-height: 1.6;
        }
        .decision-box {
            border: 2px solid #333;
            padding: 12px;
            margin-bottom: 15px;
            background-color: #f0f8f0;
            text-align: center;
        }
        .decision-box .decision-label {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .decision-box .decision-text {
            font-size: 13px;
            font-weight: bold;
            color: #006600;
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
            <p class="title">Decision on Zoning and Locational Clearance</p>
        </div>

        {{-- Decision Number and Date --}}
        <div class="decision-info">
            <p class="decision-number">Decision No.: {{ $application->zoningAssessment?->decision_no ?? '___________' }}</p>
            <p class="decision-date">Date: {{ $application->zoningAssessment?->certificate_date?->format('F d, Y') ?? '___________' }}</p>
        </div>

        {{-- Body Text --}}
        <p class="body-text">
            After careful evaluation of the application for <strong>Locational Clearance</strong>
            filed by <strong>{{ $application->applicant_full_name }}</strong>
            for the project <strong>{{ $application->project_title ?? '___________' }}</strong>
            located at <strong>{{ $application->building_street }}{{ $application->buildingBarangay ? ', Brgy. ' . $application->buildingBarangay->name : '' }}</strong>,
            the undersigned hereby <strong>GRANTS</strong> locational clearance for the above-described project subject to the following conditions and findings:
        </p>

        {{-- Applicant and Project Details --}}
        <div class="section-title">Applicant and Project Details</div>
        <div class="section-body">
            <div class="field-row">
                <div class="field-label">Applicant Name</div>
                <div class="field-value">{{ $application->applicant_full_name }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">Project Title</div>
                <div class="field-value">{{ $application->project_title ?? '' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">Project Location</div>
                <div class="field-value">
                    {{ $application->building_street }}{{ $application->buildingBarangay ? ', Brgy. ' . $application->buildingBarangay->name : '' }}
                </div>
            </div>
            <div class="field-row">
                <div class="field-label">Project Classification</div>
                <div class="field-value">{{ $application->zoningAssessment?->project_classification ?? '' }}</div>
            </div>
        </div>

        {{-- Findings / Evaluation --}}
        <div class="section-title">Findings / Evaluation</div>
        <div class="findings-box">{{ $application->zoningAssessment?->findings_evaluation ?? 'No findings recorded.' }}</div>

        {{-- Decision --}}
        <div class="decision-box">
            <div class="decision-label">Decision Recommended</div>
            <div class="decision-text">{{ $application->zoningAssessment?->decision_recommended ?? 'Pending' }}</div>
        </div>

        {{-- Fee Paid --}}
        @php
            $locationalFees = collect();
            $locationalFeeTotal = 0;
            if ($application->collections) {
                foreach ($application->collections as $collection) {
                    if ($collection->collectionDetails) {
                        $filtered = $collection->collectionDetails->filter(function ($detail) {
                            return stripos($detail->fee_category, 'Locational') !== false;
                        });
                        $locationalFees = $locationalFees->merge($filtered);
                    }
                }
                $locationalFeeTotal = $locationalFees->sum('amount');
            }
        @endphp

        @if($locationalFees->count())
            <div class="section-title">Fees Paid</div>
            <table class="fee-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="width: 150px; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($locationalFees as $fee)
                    <tr>
                        <td>{{ $fee->description }}</td>
                        <td class="amount">&#8369;{{ number_format($fee->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="amount">&#8369;{{ number_format($locationalFeeTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
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
