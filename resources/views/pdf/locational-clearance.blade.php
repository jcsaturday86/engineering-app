<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Decision on Zoning and Locational Clearance</title>
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
            text-align: center;
            margin-bottom: 6px;
        }
        .header img.seal {
            height: 85px;
            margin-bottom: 5px;
        }
        .header p {
            margin: 0;
            font-size: 11.5px;
        }
        .header .office {
            font-size: 12.5px;
            font-weight: bold;
        }
        .title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            margin: 6px 0;
            line-height: 1.2;
        }
        table.form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        table.form-table td {
            border: 1px solid #000;
            padding: 2px 6px;
            font-size: 11.5px;
            vertical-align: top;
        }
        table.form-table .lbl {
            font-size: 10px;
            color: #333;
            display: block;
            margin-bottom: 1px;
        }
        .decision-row td {
            font-weight: bold;
        }
        .decision-row .granted {
            color: #006600;
        }
        .conditions-title {
            font-size: 11px;
            font-weight: bold;
            margin: 6px 0 2px;
        }
        .conditions-list {
            font-size: 10px;
            line-height: 1.2;
        }
        .cond-row {
            display: table;
            width: 100%;
            margin-bottom: 0;
        }
        .cond-box {
            display: table-cell;
            width: 22px;
            font-weight: bold;
            vertical-align: top;
        }
        .cond-text {
            display: table-cell;
            vertical-align: top;
        }
        .sub-conditions {
            margin-left: 20px;
        }
        .footer-block {
            margin-top: 6px;
            display: table;
            width: 100%;
        }
        .footer-left {
            display: table-cell;
            width: 55%;
            font-size: 11.5px;
            vertical-align: bottom;
        }
        .footer-left .row {
            margin-bottom: 2px;
        }
        .footer-left .label {
            display: inline-block;
            min-width: 105px;
        }
        .footer-right {
            display: table-cell;
            width: 45%;
            text-align: center;
            vertical-align: bottom;
        }
        .footer-right .sig-line {
            font-size: 12.5px;
            font-weight: bold;
        }
        .footer-right .sig-designation {
            font-size: 10.5px;
        }
        .form-code {
            margin-top: 5px;
            font-size: 9.5px;
        }
    </style>
</head>
<body>
<div class="content">
    {{-- Header --}}
    <div class="header">
        @if($sealImage)
            <img src="{{ $sealImage }}" class="seal" alt="Seal">
        @endif
        <p>Republic of the Philippines</p>
        <p class="office">{{ $settings['general.planning_office_name'] ?? 'CITY PLANNING & DEVELOPMENT OFFICE' }}</p>
        <p>{{ $settings['general.planning_office_address'] ?? 'Second Floor, City Hall Annex Building' }}</p>
        <p>City of San Fernando, La Union</p>
        <p>Tel. No. {{ $settings['general.planning_office_telephone'] ?? '(072) 888-69-01 Local 120' }}</p>
    </div>

    {{-- Title --}}
    <div class="title">DECISION ON ZONING<br>LOCATIONAL CLEARANCE</div>

    @php
        $za = $application->zoningAssessment;
        $applicantName = strtoupper(trim(
            $application->applicant_last_name . ', ' . $application->applicant_first_name
            . ($application->applicant_middle_name ? ' ' . mb_substr($application->applicant_middle_name, 0, 1) . '.' : '')
        ));
        $address = trim(collect([
            $application->applicant_street,
            $application->applicantBarangay?->name,
            $application->applicantCity?->name,
        ])->filter()->implode(', '));
        $typeOfProject = trim(collect([
            $za?->project_classification,
            $application->project_title,
        ])->filter()->implode(', '));
    @endphp

    {{-- Application No. / Decision No. / Dates --}}
    <table class="form-table">
        <tr>
            <td style="width:50%;">
                <span class="lbl">Application No.</span>
                {{ $application->application_number }}
            </td>
            <td style="width:50%;">
                <span class="lbl">LC Decision No.</span>
                {{ $za?->decision_no ?? '___________' }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Date of Receipt</span>
                {{ $application->submitted_at?->format('F d, Y') ?? '___________' }}
            </td>
            <td>
                <span class="lbl">Date of Issue</span>
                {{ $za?->certificate_date?->format('F d, Y') ?? '___________' }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Name of Applicant</span>
                {{ $applicantName }}
            </td>
            <td>
                <span class="lbl">Name of Corporation</span>
                {{ $application->enterprise_name ?: '—' }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Address/T.I.N.</span>
                {{ $address ?: '—' }}
            </td>
            <td>
                <span class="lbl">Address/T.I.N.</span>
                {{ $application->applicant_tin ?: '—' }}
            </td>
        </tr>
        <tr>
            <td style="width:60%;">
                <span class="lbl">Type of Project</span>
                {{ $typeOfProject ?: '—' }}
            </td>
            <td>
                <span class="lbl">Area (sq.m.)</span>
                {{ $application->lot_area ?? $application->total_floor_area ?? '—' }}
                <br>
                <span class="lbl" style="margin-top:4px;">Location</span>
                {{ $application->buildingBarangay?->name ?? '—' }}
                <br>
                <span class="lbl" style="margin-top:4px;">Lot</span>
                {{ $application->lot_no ?: '—' }}
                <br>
                <span class="lbl" style="margin-top:4px;">Bldg. Coverage</span>
                {{ $za?->building_coverage ?? '—' }}
            </td>
        </tr>
        <tr class="decision-row">
            <td style="width:60%;">
                <span class="lbl" style="font-weight:normal;">Decision</span>
                <span class="granted">{{ strtoupper($za?->decision_recommended ?? 'PENDING') }}</span>
            </td>
            <td>
                <span class="lbl" style="font-weight:normal;">Grounds for Denied Application</span>
                — — —
            </td>
        </tr>
    </table>

    {{-- Conditions --}}
    <div class="conditions-title">Conditions (with /):</div>
    <div class="conditions-list">
        <div class="cond-row"><div class="cond-box">[ / ]</div><div class="cond-text">All conditions stipulated herein form part of this decision and are subject to monitoring.</div></div>
        <div class="cond-row"><div class="cond-box">[ / ]</div><div class="cond-text">Non-compliance therewith shall be a cause for cancellation or legal action.</div></div>
        <div class="cond-row"><div class="cond-box">[ / ]</div><div class="cond-text">The applicable requirements of other government agencies and applicable provisions of existing laws shall be complied with.</div></div>
        <div class="cond-row"><div class="cond-box">[ / ]</div><div class="cond-text">No activity other than that applied for shall be conducted within the project site.</div></div>
        <div class="cond-row"><div class="cond-box">[ / ]</div><div class="cond-text">No major expansion, alteration and/or improvement shall be introduced without prior clearance from this Office.</div></div>
        <div class="cond-row"><div class="cond-box">[ / ]</div><div class="cond-text">This decision shall not be construed as a certification of CPDO as to the ownership by the applicant of the parcel of land subject of this decision.</div></div>
        <div class="cond-row"><div class="cond-box">[ / ]</div><div class="cond-text">Any misrepresentation, false statement or allegation material to the issuance of this decision shall be sufficient cause for its revocation.</div></div>
        <div class="cond-row"><div class="cond-box">[ / ]</div><div class="cond-text">Additional Conditions:</div></div>
        <div class="sub-conditions">
            <div class="cond-row"><div class="cond-box">[ / ]</div><div class="cond-text">Provisions as to setback, yard requirement, bulk, easement, area, height and other restrictions shall strictly conform with the requirements of the National Building Code and other related laws.</div></div>
            <div class="cond-row"><div class="cond-box">[ / ]</div><div class="cond-text">This decision shall be considered automatically revoked if project is not commenced within one (1) year from the date of its issuance.</div></div>
            <div class="cond-row"><div class="cond-box">[ / ]</div><div class="cond-text">Any complaint against the issuance of this Clearance found valid after due hearing shall be sufficient cause for its suspension or revocation.</div></div>
            <div class="cond-row"><div class="cond-box">[ / ]</div><div class="cond-text">This shall be without prejudice to the rights and interests of parties having valid claim/s over the lot/s subject of the application.</div></div>
            <div class="cond-row"><div class="cond-box">[&nbsp;&nbsp;&nbsp;]</div><div class="cond-text">Secure an Environmental Compliance Certificate (ECC) or Certificate of Non-Coverage (CNC) from DENR prior to introducing development within the site. Submit a copy thereof to this Office within six (6) months from issuance of this Clearance.</div></div>
            <div class="cond-row"><div class="cond-box">[&nbsp;&nbsp;&nbsp;]</div><div class="cond-text">For other conditions, see Reverse Side.</div></div>
        </div>
    </div>

    {{-- Footer: O.R. details + Signature --}}
    @php
        $lcFees = collect();
        foreach ($application->collections ?? [] as $collection) {
            foreach ($collection->collectionDetails ?? [] as $detail) {
                if (stripos($detail->fee_category, 'ZONING_LC') !== false) {
                    $lcFees->push($detail);
                }
            }
        }
        $lcFeeTotal = $lcFees->sum('amount');
        $paymentCollection = $lcFees->first()?->collection;
    @endphp

    <div class="footer-block">
        <div class="footer-left">
            <div class="row"><span class="label">O.R. No.:</span> {{ $paymentCollection->or_number ?? '___________' }}</div>
            <div class="row"><span class="label">Date Issued:</span> {{ $paymentCollection?->or_date?->format('m/d/Y') ?? '___________' }}</div>
            <div class="row"><span class="label">Amount Issued:</span> Php {{ number_format($lcFeeTotal, 2) }}</div>
            <div class="row"><span class="label">Issued by:</span> City of San Fernando, La Union</div>
        </div>
        <div class="footer-right">
            @if(isset($signatories['planning_officer']))
                <div class="sig-line">{{ strtoupper(trim(($signatories['planning_officer']->title ?? '') . ' ' . $signatories['planning_officer']->name)) }}</div>
                @if($signatories['planning_officer']->designation)
                    <div class="sig-designation">{{ $signatories['planning_officer']->designation }}</div>
                @endif
            @else
                <div class="sig-line">&nbsp;</div>
                <div class="sig-designation">Zoning Inspector</div>
            @endif
        </div>
    </div>

    <div class="form-code">PDO-012-&Oslash;</div>
</div>
</body>
</html>
