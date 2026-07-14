<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Application for Demolition Permit - {{ $application->application_number }}</title>
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
            font-size: 12px;
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
            margin: 8px 0 12px;
            line-height: 1.2;
        }
        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 11.5px;
        }
        table.info-table td {
            padding: 1.5px 4px;
            vertical-align: top;
        }
        table.info-table .lbl {
            font-weight: normal;
            white-space: nowrap;
            width: 150px;
        }
        .fill {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 60px;
            padding: 0 3px;
        }
        .sec-title {
            font-weight: bold;
            font-size: 12px;
            margin: 10px 0 4px;
            text-decoration: underline;
        }
        .radio-row {
            font-size: 11.5px;
            margin-bottom: 4px;
        }
        .radio-row .box {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            text-align: center;
            line-height: 9px;
            font-size: 9px;
            font-weight: bold;
            margin-right: 4px;
            vertical-align: middle;
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
            margin-top: 30px;
            margin-bottom: 0;
            line-height: 1;
            font-size: 1px;
        }
        table.prc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 4px;
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
    <div class="title">APPLICATION FOR DEMOLITION PERMIT</div>

    {{-- Applicant --}}
    <div class="sec-title">Applicant Information</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Name of Applicant</td>
            <td>: {{ $application->applicant_last_name }}, {{ $application->applicant_first_name }}{{ $application->applicant_middle_name ? ' ' . mb_substr($application->applicant_middle_name, 0, 1) . '.' : '' }}</td>
        </tr>
        <tr>
            <td class="lbl">TIN</td>
            <td>: <span class="fill">{{ $application->applicant_tin ?? '' }}</span> &nbsp;&nbsp; Telephone: <span class="fill">{{ $application->applicant_telephone ?? '' }}</span></td>
        </tr>
        <tr>
            <td class="lbl">Owned By Enterprise</td>
            <td>: {{ $application->owned_by_enterprise ? ($application->enterprise_name ?? 'Yes') : 'No' }} &nbsp;&nbsp; Form of Ownership: {{ $application->formOfOwnership?->name ?? '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Address</td>
            <td>: {{ trim(collect([$application->applicant_street, $application->applicantBarangay?->name, $application->applicantCity?->name])->filter()->implode(', ')) }}</td>
        </tr>
        <tr>
            <td class="lbl"></td>
            <td>ZIP Code: <span class="fill">{{ $application->applicant_zip_code ?? '' }}</span></td>
        </tr>
        <tr>
            <td class="lbl">CTC No.</td>
            <td>: <span class="fill">{{ $application->applicant_ctc_no ?? '' }}</span> &nbsp;&nbsp; Date Issued: <span class="fill">{{ $application->applicant_ctc_date_issued?->format('F d, Y') ?? '' }}</span> &nbsp;&nbsp; Place Issued: <span class="fill">{{ $application->applicant_ctc_place_issued ?? '' }}</span></td>
        </tr>
    </table>

    {{-- Character of Occupancy --}}
    @if($application->applicationOccupancyGroups && $application->applicationOccupancyGroups->count())
    <div class="sec-title">Character of Occupancy</div>
    <table class="info-table">
        @foreach($application->applicationOccupancyGroups as $occGroup)
        <tr>
            <td colspan="2">{{ $loop->iteration }}. {{ $occGroup->occupancyGroup?->name ?? '' }}{{ $occGroup->occupancySubGroup ? ' — ' . $occGroup->occupancySubGroup->name : '' }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    {{-- Location of Demolition Works --}}
    <div class="sec-title">Location of Demolition Works</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Lot No. / Blk No.</td>
            <td>: <span class="fill">{{ $application->lot_no ?? '' }}</span> / <span class="fill">{{ $application->block_no ?? '' }}</span></td>
        </tr>
        <tr>
            <td class="lbl">TCT No. / Tax Dec. No.</td>
            <td>: <span class="fill">{{ $application->tct_no ?? '' }}</span> / <span class="fill">{{ $application->tax_dec_no ?? '' }}</span></td>
        </tr>
        <tr>
            <td class="lbl">Street / Barangay</td>
            <td>: {{ trim(collect([$application->demolition_street, $application->demolitionBarangay?->name])->filter()->implode(', ')) }}</td>
        </tr>
    </table>

    {{-- Scope of Work --}}
    <div class="sec-title">Scope of Work</div>
    <div class="radio-row">
        <span class="box">{{ $application->scope_of_work === 'demolition' ? 'X' : '' }}</span> Demolition
        @if($application->scope_of_work === 'demolition' && $application->scope_of_work_detail)
            &nbsp;&mdash;&nbsp;{{ $application->scope_of_work_detail }}
        @endif
    </div>
    <div class="radio-row">
        <span class="box">{{ $application->scope_of_work === 'others' ? 'X' : '' }}</span> Others (Specify)
        @if($application->scope_of_work === 'others' && $application->scope_of_work_detail)
            &nbsp;&mdash;&nbsp;{{ $application->scope_of_work_detail }}
        @endif
    </div>

    {{-- Signatories: Full-time Inspector (left) / Lot Owner Consent (right) --}}
    <div class="sig2-wrap">
        <div class="sig2-col left">
            <div class="role-label" style="margin-top:0;">Full-time Inspector and Supervisor of Demolition Works:</div>
            <div class="blank-sig-line" style="margin-top:36px;">&nbsp;</div>
            <div class="sig-name-plain" style="text-align:center;">{{ strtoupper($application->inspector_name ?? '') }}</div>
            <div class="sig-caption" style="text-align:center;">Name of Architect or Civil Engineer</div>
            <table class="prc-table">
                <tr>
                    <td>PRC No. {{ $application->inspector_prc_no ?? '' }}</td>
                    <td>Validity: {{ $application->inspector_prc_validity?->format('m/d/Y') ?? '' }}</td>
                </tr>
                <tr>
                    <td>PTR No. {{ $application->inspector_ptr_no ?? '' }}</td>
                    <td>Date Issued: {{ $application->inspector_ptr_date_issued?->format('m/d/Y') ?? '' }}</td>
                </tr>
                <tr>
                    <td>Issued at: {{ $application->inspector_ptr_issued_at ?? '' }}</td>
                    <td>TIN: {{ $application->inspector_tin ?? '' }}</td>
                </tr>
                <tr>
                    <td colspan="2">Address: {{ $application->inspector_address ?? '' }} &nbsp;&nbsp; Tel: {{ $application->inspector_telephone ?? '' }}</td>
                </tr>
            </table>
        </div>
        <div class="sig2-col right">
            <div class="role-label" style="margin-top:0;">Lot Owner Consent:</div>
            <div class="blank-sig-line" style="margin-top:36px;">&nbsp;</div>
            <div class="sig-name-plain" style="text-align:center;">{{ strtoupper($application->owner_name ?? '') }}</div>
            <div class="sig-caption" style="text-align:center;">Full Name of Lot Owner</div>
            <div class="row" style="margin-top:8px;">CTC No. <span class="fill">{{ $application->owner_ctc_no ?? '' }}</span></div>
            <div class="row">Date Issued: <span class="fill">{{ $application->owner_ctc_date_issued?->format('m/d/Y') ?? '' }}</span></div>
            <div class="row">Place Issued: <span class="fill">{{ $application->owner_ctc_place_issued ?? '' }}</span></div>
        </div>
    </div>

    @if(isset($signatories['building_official']))
    <div style="margin-top:24px; text-align:center;">
        <div class="blank-sig-line" style="border-bottom:1px solid #000; width:260px; margin:0 auto;">&nbsp;</div>
        <div class="sig-name-plain">{{ strtoupper(trim(($signatories['building_official']->title ?? '') . ' ' . $signatories['building_official']->name)) }}</div>
        <div class="sig-caption">{{ $signatories['building_official']->designation ?? 'Building Official' }}</div>
    </div>
    @endif

    <div style="margin-top:10px; font-size:9px; text-align:center; color:#555;">This is a computer-generated document. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}</div>
</div>
</body>
</html>
