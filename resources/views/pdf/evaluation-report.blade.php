<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Evaluation Report</title>
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
            line-height: 1.5;
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
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-grid td {
            width: 25%;
            padding: 5px 8px;
            border: 1px solid #999;
            font-size: 11px;
        }
        .info-grid .label {
            font-weight: bold;
            font-size: 10px;
            color: #555;
            background-color: #f5f5f5;
        }
        .two-column {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .two-column td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }
        .two-column td:first-child {
            padding-right: 8px;
        }
        .two-column td:last-child {
            padding-left: 8px;
        }
        .boundary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .boundary-grid td {
            width: 50%;
            padding: 5px 8px;
            border: 1px solid #999;
            font-size: 11px;
        }
        .boundary-grid .label {
            font-weight: bold;
            font-size: 10px;
            color: #555;
            background-color: #f5f5f5;
            width: 25%;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-yes {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-no {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .findings-box {
            border: 1px solid #999;
            border-top: none;
            padding: 10px;
            margin-bottom: 15px;
            background-color: #fafafa;
            min-height: 80px;
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
        .signature-block {
            margin-top: 40px;
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
        .date-line {
            font-size: 10px;
            color: #555;
            margin-top: 5px;
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
            <p class="title">Evaluation Report</p>
        </div>

        {{-- Project Details --}}
        <div class="section-title">Project Details</div>
        <div class="section-body">
            <div class="field-row">
                <div class="field-label">Application No.</div>
                <div class="field-value">{{ $application->application_number }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">Project Title</div>
                <div class="field-value">{{ $application->project_title ?? '' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">Applicant</div>
                <div class="field-value">{{ $application->applicant_full_name }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">Project Location</div>
                <div class="field-value">
                    {{ $application->building_street }}{{ $application->buildingBarangay ? ', Brgy. ' . $application->buildingBarangay->name : '' }}
                </div>
            </div>
        </div>

        {{-- Zoning Evaluation Fields --}}
        <div class="section-title">Zoning Evaluation</div>
        <table class="info-grid">
            <tr>
                <td class="label">Project Lifespan</td>
                <td>{{ $application->zoningAssessment?->project_lifespan ?? '' }}</td>
                <td class="label">Significance</td>
                <td>{{ $application->zoningAssessment?->project_significance ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Classification</td>
                <td>{{ $application->zoningAssessment?->project_classification ?? '' }}</td>
                <td class="label">Site Zoning</td>
                <td>{{ $application->zoningAssessment?->site_zoning_classification ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Right Over Lands</td>
                <td>{{ $application->zoningAssessment?->right_over_lands ?? '' }}</td>
                <td class="label">Radius Covered</td>
                <td>{{ $application->zoningAssessment?->radius_covered ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Land Use (Radius)</td>
                <td colspan="3">{{ $application->zoningAssessment?->land_use_radius ?? '' }}</td>
            </tr>
        </table>

        {{-- Boundaries --}}
        <div class="section-title">Boundaries</div>
        <table class="boundary-grid">
            <tr>
                <td class="label">North</td>
                <td>{{ $application->zoningAssessment?->boundary_north ?? '' }}</td>
                <td class="label">South</td>
                <td>{{ $application->zoningAssessment?->boundary_south ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">East</td>
                <td>{{ $application->zoningAssessment?->boundary_east ?? '' }}</td>
                <td class="label">West</td>
                <td>{{ $application->zoningAssessment?->boundary_west ?? '' }}</td>
            </tr>
        </table>

        {{-- Additional Details --}}
        <div class="section-title">Additional Details</div>
        <table class="info-grid">
            <tr>
                <td class="label">Building Coverage</td>
                <td>{{ $application->zoningAssessment?->building_coverage ?? '' }}</td>
                <td class="label">ECC Status</td>
                <td>
                    @if($application->zoningAssessment?->secure_ecc === true)
                        <span class="status-badge status-yes">Required / Secured</span>
                    @elseif($application->zoningAssessment?->secure_ecc === false)
                        <span class="status-badge status-no">Not Required</span>
                    @else
                        &mdash;
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Off-Street Parking</td>
                <td>
                    @if($application->zoningAssessment?->off_street_parking === true)
                        <span class="status-badge status-yes">Compliant</span>
                    @elseif($application->zoningAssessment?->off_street_parking === false)
                        <span class="status-badge status-no">Not Compliant</span>
                    @else
                        &mdash;
                    @endif
                </td>
                <td class="label">Date of Evaluation</td>
                <td>{{ $application->zoningAssessment?->date_evaluation?->format('F d, Y') ?? '' }}</td>
            </tr>
        </table>

        {{-- Findings and Evaluation --}}
        <div class="section-title">Findings and Evaluation</div>
        <div class="findings-box">{{ $application->zoningAssessment?->findings_evaluation ?? 'No findings recorded.' }}</div>

        {{-- Decision Recommended --}}
        <div class="decision-box">
            <div class="decision-label">Decision Recommended</div>
            <div class="decision-text">{{ $application->zoningAssessment?->decision_recommended ?? 'Pending' }}</div>
        </div>

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
