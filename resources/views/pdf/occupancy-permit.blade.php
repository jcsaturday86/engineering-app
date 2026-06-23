<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Occupancy Permit</title>
    <style>
        @page {
            size: landscape;
            margin: 15mm;
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
            line-height: 1.4;
        }
        .container {
            max-width: 100%;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px double #333;
            padding-bottom: 10px;
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
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        .permit-info {
            margin-bottom: 12px;
            overflow: hidden;
        }
        .permit-info .left {
            float: left;
        }
        .permit-info .right {
            float: right;
            text-align: right;
        }
        .permit-info .permit-number {
            font-size: 14px;
            font-weight: bold;
            color: #c00;
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
            padding-right: 10px;
        }
        .two-column td:last-child {
            padding-left: 10px;
        }
        .section {
            margin-bottom: 12px;
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
            padding: 8px;
        }
        .field-row {
            margin-bottom: 4px;
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
        .building-info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .building-info-grid td {
            width: 25%;
            padding: 4px 8px;
            border: 1px solid #999;
            font-size: 11px;
        }
        .building-info-grid .label {
            font-weight: bold;
            font-size: 10px;
            color: #555;
            background-color: #f5f5f5;
        }
        table.details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.details th,
        table.details td {
            border: 1px solid #999;
            padding: 5px 8px;
            font-size: 11px;
            text-align: left;
        }
        table.details th {
            background-color: #e8e8e8;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        table.details td.amount {
            text-align: right;
            white-space: nowrap;
        }
        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            padding: 10px 15px;
            vertical-align: bottom;
        }
        .signatures .sig-line {
            border-top: 1px solid #333;
            padding-top: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 30px;
        }
        .signatures .sig-title {
            font-size: 10px;
            color: #555;
        }
        .signatures .sig-designation {
            font-size: 10px;
            color: #777;
            font-style: italic;
        }
        .validity-note {
            margin-top: 15px;
            padding: 8px;
            border: 1px solid #999;
            background-color: #ffffcc;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
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
            <p class="title">Occupancy Permit</p>
        </div>

        {{-- Permit Number and Date --}}
        <div class="permit-info">
            <div class="left">
                <span class="permit-number">Permit No.: {{ $permit->permit_number }}</span>
            </div>
            <div class="right">
                <span>Date Issued: {{ $permit->issued_date?->format('F d, Y') }}</span>
            </div>
        </div>

        @php
            $application = $permit->application;
        @endphp

        {{-- Owner/Applicant and Project Info (Two Columns) --}}
        <table class="two-column">
            <tr>
                <td>
                    <div class="section">
                        <div class="section-title">Owner / Applicant Information</div>
                        <div class="section-body">
                            <div class="field-row">
                                <div class="field-label">Name</div>
                                <div class="field-value">{{ $application?->applicant_full_name }}</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">Address</div>
                                <div class="field-value">
                                    {{ $application?->applicant_street }}{{ $application?->applicantBarangay ? ', ' . $application->applicantBarangay->name : '' }}{{ $application?->applicantCity ? ', ' . $application->applicantCity->name : '' }}
                                </div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">Contact Number</div>
                                <div class="field-value">{{ $application?->applicant_contact_no ?? '' }}</div>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="section">
                        <div class="section-title">Project Information</div>
                        <div class="section-body">
                            <div class="field-row">
                                <div class="field-label">Project Title</div>
                                <div class="field-value">{{ $application?->project_title ?? '' }}</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">Location</div>
                                <div class="field-value">
                                    {{ $application?->building_street }}{{ $application?->buildingBarangay ? ', Brgy. ' . $application->buildingBarangay->name : '' }}
                                </div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">BP Number</div>
                                <div class="field-value">{{ $application?->bp_number ?? '' }}</div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">BP Date Issued</div>
                                <div class="field-value">{{ $application?->bp_issued_date?->format('F d, Y') ?? '' }}</div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Building Details --}}
        <div class="section">
            <div class="section-title">Building Details</div>
        </div>
        <table class="building-info-grid">
            <tr>
                <td class="label">No. of Storeys</td>
                <td>{{ $application?->no_of_storeys ?? '' }}</td>
                <td class="label">No. of Units</td>
                <td>{{ $application?->no_of_units ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Total Floor Area (sq.m.)</td>
                <td>{{ $application?->total_floor_area ? number_format($application->total_floor_area, 2) : '' }}</td>
                <td class="label">Lot Area (sq.m.)</td>
                <td>{{ $application?->lot_area ? number_format($application->lot_area, 2) : '' }}</td>
            </tr>
        </table>

        {{-- Character of Occupancy --}}
        <div class="section">
            <div class="section-title">Character of Occupancy</div>
            <div class="section-body">
                @if($application?->applicationOccupancyGroups && $application->applicationOccupancyGroups->count())
                    @foreach($application->applicationOccupancyGroups as $occGroup)
                        <div class="field-value">
                            {{ $occGroup->occupancyGroup?->name ?? '' }}
                            @if($occGroup->occupancySubGroup)
                                &mdash; {{ $occGroup->occupancySubGroup->name }}
                            @endif
                            @if($occGroup->others_text)
                                ({{ $occGroup->others_text }})
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="field-value">&mdash;</div>
                @endif
            </div>
        </div>

        {{-- Collection Details --}}
        @if($application?->collections && $application->collections->count())
            @php
                $latestCollection = $application->collections->sortByDesc('or_date')->first();
            @endphp
            <div class="section">
                <div class="section-title">Collection Details</div>
            </div>
            <table class="building-info-grid">
                <tr>
                    <td class="label">OR Number</td>
                    <td>{{ $latestCollection->or_number }}</td>
                    <td class="label">OR Date</td>
                    <td>{{ $latestCollection->or_date?->format('F d, Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Amount Paid</td>
                    <td colspan="3">&#8369;{{ number_format($latestCollection->amount_due, 2) }}</td>
                </tr>
            </table>
        @endif

        {{-- Signatory --}}
        <table class="signatures">
            <tr>
                <td></td>
                <td></td>
                <td>
                    @if(isset($signatories['building_official']))
                        <div class="sig-line">{{ $signatories['building_official']->name }}</div>
                        @if($signatories['building_official']->title)
                            <div class="sig-title">{{ $signatories['building_official']->title }}</div>
                        @endif
                        @if($signatories['building_official']->designation)
                            <div class="sig-designation">{{ $signatories['building_official']->designation }}</div>
                        @endif
                    @else
                        <div class="sig-line">&nbsp;</div>
                        <div class="sig-title">Building Official</div>
                    @endif
                </td>
            </tr>
            <tr>
                <td>
                    <div style="font-size: 10px; color: #555; text-align: left; margin-top: 10px;">
                        Processed by: {{ $permit->processedBy?->full_name ?? '' }}
                    </div>
                </td>
                <td></td>
                <td></td>
            </tr>
        </table>

        {{-- Validity Note --}}
        <div class="validity-note">
            This Occupancy Permit certifies that the building/structure described herein has been inspected and found to be in compliance with the National Building Code of the Philippines and its implementing rules and regulations.
        </div>
    </div>
</body>
</html>
