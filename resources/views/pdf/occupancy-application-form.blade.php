<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Unified Application Form for Certificate of Occupancy - {{ $application->application_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0.75in;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12.5px;
            color: #000;
            line-height: 1.2;
        }
        .content {
            padding: 0.75in;
        }
        .header {
            margin-bottom: 6px;
        }
        .header-table {
            display: table;
            width: 100%;
        }
        .header-cell {
            display: table-cell;
            vertical-align: middle;
        }
        .header-cell.logo-cell {
            width: 100px;
            text-align: center;
        }
        .header-cell.logo-cell img {
            height: 90px;
        }
        .header-cell.text-cell {
            text-align: center;
        }
        .header p {
            margin: 0;
            font-size: 14px;
        }
        .title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin: 8px 0 10px;
            line-height: 1.2;
        }
        .check-row {
            text-align: center;
            font-size: 11.5px;
            margin-bottom: 4px;
        }
        .check-row .box {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            text-align: center;
            line-height: 9px;
            font-size: 9px;
            font-weight: bold;
            margin-right: 3px;
            vertical-align: middle;
        }
        .applies-row {
            text-align: center;
            font-size: 11px;
            margin-bottom: 10px;
        }
        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 11.5px;
        }
        table.info-table td {
            padding: 1px 4px;
            vertical-align: top;
        }
        table.info-table .lbl {
            font-weight: normal;
            white-space: nowrap;
            width: 130px;
        }
        .fill {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 60px;
            padding: 0 3px;
        }
        .req-title {
            font-weight: bold;
            font-size: 11.5px;
            margin: 8px 0 4px;
        }
        .req-list {
            font-size: 10.5px;
            margin-bottom: 8px;
        }
        .req-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }
        .req-box {
            display: table-cell;
            width: 16px;
            vertical-align: top;
        }
        .req-box .box {
            display: inline-block;
            width: 9px;
            height: 9px;
            border: 1px solid #000;
        }
        .req-text {
            display: table-cell;
            vertical-align: top;
        }
        .sig2-wrap .row {
            font-size: 11px;
            margin-bottom: 2px;
        }
        .sig2-wrap {
            display: table;
            width: 100%;
            margin-top: 14px;
        }
        .sig2-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .sig2-col.left {
            padding-right: 12px;
        }
        .sig2-col.right {
            padding-left: 12px;
        }
        .sig2-col .sig-name-plain {
            font-weight: bold;
            margin-top: 2px;
        }
        .sig2-col .sig-caption {
            font-size: 9.5px;
            margin-top: 1px;
            margin-bottom: 4px;
        }
        .sig2-col .role-label {
            font-weight: bold;
            font-size: 10.5px;
            margin-top: 6px;
        }
        .sig2-col .blank-sig-line {
            border-bottom: 1px solid #000;
            margin-top: 10px;
            margin-bottom: 0;
            line-height: 1;
            font-size: 1px;
        }
        .sig2-col .sig-gap {
            height: 60px;
        }
        table.prc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        table.prc-table td {
            border: 1px solid #000;
            padding: 2px 5px;
        }
    </style>
</head>
<body>
<div class="content">
    {{-- Header --}}
    <div class="header">
        <div class="header-table">
            <div class="header-cell logo-cell">
                @if(!empty($sealImage))
                    <img src="{{ $sealImage }}" alt="Official Seal">
                @endif
            </div>
            <div class="header-cell text-cell">
                <p>Republic of the Philippines</p>
                <p>City of San Fernando</p>
                <p>Province of La Union</p>
            </div>
            <div class="header-cell logo-cell">
                @if(!empty($nationalGovtLogo))
                    <img src="{{ $nationalGovtLogo }}" alt="National Government Logo">
                @endif
            </div>
        </div>
    </div>

    {{-- Title --}}
    <div class="title">UNIFIED APPLICATION FORM FOR CERTIFICATE OF OCCUPANCY</div>

    {{-- FULL / PARTIAL --}}
    @php
        $appTypeName = strtolower($application->applicationType->name ?? '');
    @endphp
    <div class="check-row">
        <span class="box">{{ $appTypeName === 'full' ? 'x' : '' }}</span> FULL
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <span class="box">{{ $appTypeName === 'partial' ? 'x' : '' }}</span> PARTIAL
    </div>
    <div class="applies-row">
        THIS ALSO APPLIES FOR:
        <span class="box">{{ $application->fsic_no ? '&#10004;' : '' }}</span> FIRE SAFETY INSPECTION CERTIFICATE
    </div>

    {{-- BP / FSEC references --}}
    <table class="info-table">
        <tr>
            <td class="lbl">Building Permit No.</td>
            <td>: <span class="fill">{{ $application->bp_number ?? '' }}</span></td>
        </tr>
        <tr>
            <td class="lbl">Date Issued</td>
            <td>: <span class="fill">{{ $application->bp_issued_date?->format('F d, Y') ?? '' }}</span></td>
        </tr>
        <tr>
            <td class="lbl">FSEC No.</td>
            <td>: <span class="fill">{{ $application->fsec_no ?? '' }}</span></td>
        </tr>
        <tr>
            <td class="lbl">Date Issued</td>
            <td>: <span class="fill">{{ $application->fsec_issued_date?->format('F d, Y') ?? '' }}</span></td>
        </tr>
    </table>

    {{-- Applicant/Owner --}}
    <table class="info-table">
        <tr>
            <td class="lbl">Name of Applicant/Owner</td>
            <td>: {{ $application->applicant_last_name }}, {{ $application->applicant_first_name }}{{ $application->applicant_middle_name ? ' ' . mb_substr($application->applicant_middle_name, 0, 1) . '.' : '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Address of Applicant/Owner</td>
            <td>: {{ trim(collect([$application->applicant_street, $application->applicantBarangay?->name, $application->applicantCity?->name])->filter()->implode(', ')) }}</td>
        </tr>
        <tr>
            <td class="lbl"></td>
            <td>ZIP Code: <span class="fill">{{ $application->applicant_zip_code ?? '' }}</span> &nbsp;&nbsp; Contact No.: <span class="fill">{{ $application->applicant_contact_no ?? '' }}</span></td>
        </tr>
    </table>

    {{-- Requirements submitted --}}
    <div class="req-title">Requirements submitted:</div>
    <div class="req-list">
        <div class="req-row"><div class="req-box"><span class="box"></span></div><div class="req-text">3 copies of Certificate of Completion, duly notarized</div></div>
        <div class="req-row"><div class="req-box"><span class="box"></span></div><div class="req-text">Construction Logbook, signed and sealed by the Owner's Architect or Civil Engineer who undertook full-time inspection and supervision</div></div>
        <div class="req-row"><div class="req-box"><span class="box"></span></div><div class="req-text">As-Built Plans, signed and sealed by the Owner's Architect or Civil Engineer who undertook full-time inspection and supervision</div></div>
        <div class="req-row"><div class="req-box"><span class="box"></span></div><div class="req-text">1 photocopy of the valid licenses of all involved Professionals</div></div>
        <div class="req-row"><div class="req-box"><span class="box"></span></div><div class="req-text">Captioned photographs of Site and Completed Building/Structure showing front, sides and rear areas</div></div>
    </div>

    {{-- Project details --}}
    <table class="info-table">
        <tr>
            <td class="lbl">Name of Project</td>
            <td>: {{ $application->project_title ?? '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Location of Project</td>
            <td>: {{ trim(collect([$application->building_street, $application->buildingBarangay?->name])->filter()->implode(', ')) }}</td>
        </tr>
        <tr>
            <td class="lbl">Use/Character of Occupancy</td>
            <td>: {{ $application->occupancy_classified ?? '' }}</td>
        </tr>
        <tr>
            <td class="lbl">No. of Storey/s</td>
            <td>: {{ $application->no_of_storeys ?? '' }}</td>
        </tr>
        <tr>
            <td class="lbl">No. of Units</td>
            <td>: {{ $application->no_of_units ?? '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Total Gross Floor Area (Sq. M.)</td>
            <td>: {{ $application->total_floor_area ? number_format($application->total_floor_area, 2) : '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Date of Completion</td>
            <td>: {{ $application->completion_date?->format('F d, Y') ?? '' }}</td>
        </tr>
    </table>

    {{-- Signatories: Building Official (left) / Applicant-Owner (right) --}}
    <div class="sig2-wrap">
        <div class="sig2-col left">
            <div class="role-label" style="margin-top:0;">Inspected by:</div>
            <div class="blank-sig-line" style="margin-top:40px;">&nbsp;</div>
            <div class="sig-caption" style="text-align:center;">Name of Inspector</div>
            <div class="sig-gap"></div>
            <div class="blank-sig-line">&nbsp;</div>
            @if(isset($signatories['building_official']))
                <div class="sig-name-plain" style="text-align:center;">{{ strtoupper(trim(($signatories['building_official']->title ?? '') . ' ' . $signatories['building_official']->name)) }}</div>
                <div class="sig-caption" style="text-align:center;">{{ $signatories['building_official']->designation ?? 'ARCHITECT OR CIVIL ENGINEER' }}</div>
            @else
                <div class="sig-name-plain">&nbsp;</div>
                <div class="sig-caption" style="text-align:center;">ARCHITECT OR CIVIL ENGINEER</div>
            @endif
        </div>
        <div class="sig2-col right">
            <div class="role-label" style="margin-top:0;">Submitted by:</div>
            <div class="blank-sig-line" style="margin-top:40px;">&nbsp;</div>
            <div class="sig-name-plain" style="text-align:center;">{{ strtoupper(trim($application->applicant_last_name . ', ' . $application->applicant_first_name)) }}</div>
            <div class="row" style="margin-top:8px;">Community Tax Certificate No. <span class="fill"></span></div>
            <div class="row">Date Issued: <span class="fill"></span></div>
            <div class="row">Place Issued: <span class="fill"></span></div>
            <div class="sig-gap"></div>
            <div class="role-label" style="margin-top:0;">Attested by:</div>
            <div class="row" style="margin-top:2px;">FULL-TIME INSPECTOR OR SUPERVISOR OF CONSTRUCTION</div>
            <div class="blank-sig-line" style="margin-top:40px;">&nbsp;</div>
            <div class="sig-caption" style="text-align:center;">Name of Architect or Civil Engineer</div>
            <div style="text-align:center; font-size:9.5px;">Date<span class="fill" style="min-width:130px;"></span></div>
            <table class="prc-table" style="margin-top:4px;">
                <tr>
                    <td style="width:50%;" colspan="2">PRC No.</td>
                    <td>Validity:</td>
                </tr>
                <tr>
                    <td colspan="2">PTR No.</td>
                    <td>Date Issued:</td>
                </tr>
                <tr>
                    <td colspan="2">Issued at</td>
                    <td>TIN</td>
                </tr>
                <tr>
                    <td style="width:25%;">CTC No.</td>
                    <td style="width:25%;">Date Issued</td>
                    <td>Issued at:</td>
                </tr>
            </table>
        </div>
    </div>

    <div style="margin-top:6px; font-size:9px; text-align:center; color:#555;">This is a computer-generated document. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}</div>
</div>
</body>
</html>
