<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certification</title>
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
            font-size: 15px;
            color: #000;
            line-height: 1.6;
        }
        .content {
            padding: 0.75in;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img.seal {
            height: 80px;
            margin-bottom: 8px;
        }
        .header p {
            margin: 1px 0;
            font-size: 13px;
        }
        .header .office {
            font-size: 14px;
            font-weight: bold;
        }
        .title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 6px;
            margin: 30px 0;
        }
        .salutation {
            margin-bottom: 20px;
            font-size: 15px;
        }
        .body-text {
            text-align: justify;
            font-size: 15px;
            line-height: 2;
            text-indent: 50px;
            margin-bottom: 20px;
        }
        .body-text u {
            text-underline-offset: 2px;
        }
        .signature-block {
            margin-top: 60px;
            margin-left: auto;
            width: 320px;
            text-align: center;
        }
        .signature-block .sig-line {
            font-size: 15px;
            font-weight: bold;
        }
        .signature-block .sig-designation {
            font-size: 13px;
        }
        .footer-details {
            margin-top: 50px;
            font-size: 14px;
        }
        .footer-details .row {
            margin-bottom: 6px;
        }
        .footer-details .label {
            display: inline-block;
            min-width: 150px;
        }
        .form-code {
            margin-top: 40px;
            font-size: 12px;
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
    <div class="title">C E R T I F I C A T I O N</div>

    {{-- Salutation --}}
    <div class="salutation">TO WHOM IT MAY CONCERN:</div>

    @php
        $projectTitle = $application->project_title ?? '___________';
        $applicantName = strtoupper(trim(
            $application->applicant_last_name . ', ' . $application->applicant_first_name
            . ($application->applicant_middle_name ? ' ' . mb_substr($application->applicant_middle_name, 0, 1) . '.' : '')
        ));
        $location = $application->buildingBarangay->name ?? $application->building_street ?? '___________';
        $classification = $application->zoningAssessment?->project_classification ?? '___________';
        $certDate = $application->zoningAssessment?->certificate_date;
    @endphp

    {{-- Certification Body --}}
    <p class="body-text">
        THIS IS TO CERTIFY that the proposed location of the <u><strong>{{ strtoupper($projectTitle) }}</strong></u> building/fence
        of <u><strong>{{ $applicantName }}</strong></u> located at <u><strong>{{ $location }}</strong></u> this city
        declared as <u><strong>{{ strtoupper($classification) }}</strong></u> of the zonification plan of the city.
    </p>

    <p class="body-text">
        Issued this <u><strong>{{ $certDate ? $certDate->format('jS') : '____' }}</strong></u> day of
        <u><strong>{{ $certDate ? $certDate->format('F') : '___________' }}</strong></u>,
        <u><strong>{{ $certDate ? $certDate->format('Y') : '____' }}</strong></u> at the City of San Fernando,
        upon the request of <u><strong>{{ $applicantName }}</strong></u> in connection with
        his/her/their application for building/fencing permit.
    </p>

    {{-- Signatory --}}
    <div class="signature-block">
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

    {{-- Payment Details --}}
    @php
        $zoningFees = collect();
        foreach ($application->collections ?? [] as $collection) {
            foreach ($collection->collectionDetails ?? [] as $detail) {
                if (stripos($detail->fee_category, 'ZONING_CERT') !== false) {
                    $zoningFees->push($detail);
                }
            }
        }
        $zoningFeeTotal = $zoningFees->sum('amount');
        $paymentCollection = $zoningFees->first()?->collection;
    @endphp

    <div class="footer-details">
        <div class="row"><span class="label">Amount Paid:</span> Php {{ number_format($zoningFeeTotal, 2) }}</div>
        <div class="row"><span class="label">Paid Under O.R. No.:</span> {{ $paymentCollection->or_number ?? '___________' }}</div>
        <div class="row"><span class="label">Date Paid:</span> {{ $paymentCollection?->or_date?->format('m/d/Y') ?? '___________' }}</div>
    </div>

    <div class="form-code">PDO-011-&Oslash;</div>
</div>
</body>
</html>
