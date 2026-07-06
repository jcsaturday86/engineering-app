<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Occupancy Permit {{ $permit->permit_number }}</title>
    <style>
        {{-- A4 landscape = 11.69in x 8.27in; 0.5in margin all around leaves a 10.69in x 7.27in frame.
             NOTE: the reset must not target * or html — in dompdf those wipe the @page margin. --}}
        @page { margin: 0.5in; }
        body, div, p, span, img { margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 13.5px; color: #222; line-height: 1.25; }

        {{-- content-box height tuned so the frame's outer edge lands exactly 0.5in from
             all four page edges without pushing content onto a spurious second page --}}
        .frame { border: 6px double #1a3d6d; padding: 4mm 10mm; height: 6.82in; }

        .header { margin-bottom: 4px; }
        .header-table { display: table; width: 100%; }
        .header-cell { display: table-cell; vertical-align: middle; }
        .logo-cell { width: 90px; text-align: center; }
        .logo-cell img { height: 90px; }
        .text-cell { text-align: center; }
        .header p { margin: 1px 0; font-size: 13.5px; }
        .header .office { font-weight: bold; font-size: 14.5px; margin-top: 2px; }

        .title { text-align: center; font-weight: bold; font-size: 27px; letter-spacing: 2px; margin: 5px 0 2px; }
        .checkbox-row { text-align: center; font-size: 14.5px; margin-bottom: 7px; }
        .checkbox-row span { margin: 0 20px; }

        .two-col { display: table; width: 100%; margin-bottom: 5px; }
        .two-col .col { display: table-cell; width: 50%; vertical-align: top; }
        .no-row { font-size: 13px; margin-bottom: 2px; }
        .no-row .label { display: inline-block; }
        .no-row .value { display: inline-block; border-bottom: 1px solid #333; min-width: 170px; padding: 0 4px; font-weight: bold; }

        .intro { font-size: 12.5px; margin: 5px 0 8px; text-align: justify; }
        .intro .value { border-bottom: 1px solid #333; font-weight: bold; padding: 0 4px; }

        .field-row { display: table; width: 100%; margin-bottom: 4px; }
        .field-row .label { display: table-cell; width: 210px; font-size: 13px; vertical-align: top; padding-top: 1px; }
        .field-row .colon { display: table-cell; width: 14px; vertical-align: top; }
        .field-row .value { display: table-cell; border-bottom: 1px solid #333; font-weight: bold; font-size: 13.5px; padding-bottom: 1px; }

        .maintain-note { font-size: 12.5px; margin: 8px 0; text-align: justify; }

        .bottom-row { display: table; width: 100%; margin-top: 10px; }
        .bottom-row .col { display: table-cell; vertical-align: top; }
        .bottom-row .box-col { width: 35%; padding-right: 16px; }
        .bottom-row .qr-col { width: 130px; text-align: center; vertical-align: bottom; }
        .bottom-row .sig-col { width: auto; }

        .posted-box { border: 1px solid #333; padding: 8px 10px; font-size: 12px; font-weight: bold; text-align: center; }

        .qr-col img.qr { width: 110px; height: 110px; }

        .sig-name { border-bottom: 1px solid #333; display: inline-block; min-width: 260px; font-weight: bold; font-size: 14px; margin-top: 30px; }
        .sig-title { font-weight: bold; font-size: 13px; margin-top: 1px; }
        .sig-line { font-size: 12.5px; margin-top: 14px; }
        .sig-line .fill { border-bottom: 1px solid #333; display: inline-block; min-width: 180px; }

        .footer-note { margin-top: 10px; font-size: 11px; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
<div class="frame">

    <div class="header">
        <div class="header-table">
            <div class="header-cell logo-cell">
                @if(!empty($dpwhLogo))
                    <img src="{{ $dpwhLogo }}">
                @endif
            </div>
            <div class="header-cell text-cell">
                <p>Republic of the Philippines</p>
                <p>Department of Public Works and Highways</p>
                <p>{{ $settings['general.city'] ?? 'City' }}, {{ $settings['general.province'] ?? 'Province' }}</p>
                <p class="office">OFFICE OF THE BUILDING OFFICIAL</p>
            </div>
            <div class="header-cell logo-cell">
                @if(!empty($sealImage))
                    <img src="{{ $sealImage }}">
                @endif
            </div>
        </div>
    </div>

    @php
        $appTypeName = strtolower($application->applicationType->name ?? '');
    @endphp
    <div class="title">CERTIFICATE OF OCCUPANCY</div>
    <div class="checkbox-row">
        <span>[{{ $appTypeName === 'full' ? 'X' : ' ' }}] FULL</span>
        <span>[{{ $appTypeName === 'partial' ? 'X' : ' ' }}] PARTIAL</span>
    </div>

    <div class="two-col">
        <div class="col">
            <div class="no-row"><span class="label">NO. :</span> <span class="value">{{ $permit->permit_number }}</span></div>
            <div class="no-row"><span class="label">DATE ISSUED :</span> <span class="value">{{ $permit->issued_date ? \Carbon\Carbon::parse($permit->issued_date)->format('m/d/Y') : '' }}</span></div>
            <div class="no-row"><span class="label">FSIC NO. :</span> <span class="value">{{ $application->fsic_no ?? '-' }}</span></div>
        </div>
        <div class="col">
            @php
                $collection = $application->collections->where('status', 'active')->first();
            @endphp
            <div class="no-row"><span class="label">FEES PAID. :</span> <span class="value">Php {{ number_format($collection->amount_due ?? 0, 2) }}</span></div>
            <div class="no-row"><span class="label">OFFICIAL RECEIPT NO. :</span> <span class="value">{{ $collection->or_number ?? '' }}</span></div>
        </div>
    </div>

    <div class="intro">
        This is to certify that the building/structure which has been designed and constructed conforms to the issued Building Permit
        No. <span class="value">{{ $application->bp_number ?? '-' }}</span>
        date <span class="value">{{ $application->bp_issued_date ? \Carbon\Carbon::parse($application->bp_issued_date)->format('m/d/Y') : '-' }}</span>
    </div>

    @php
        $ownerName = trim(($application->applicant_last_name ?? '') . ', ' . ($application->applicant_first_name ?? '') . ' ' . ($application->applicant_middle_name ?? '') . ' ' . ($application->applicant_suffix ?? ''));
        $ownerName = preg_replace('/\s+/', ' ', $ownerName);
        $occGroup = $application->applicationOccupancyGroups->first();
        $occupancyText = $application->applicationOccupancyGroups->map(fn ($og) => $og->occupancySubGroup?->name ?? $og->occupancyGroup?->name)->filter()->unique()->implode(', ');
        $groupCode = $application->applicationOccupancyGroups->map(fn ($og) => $og->occupancyGroup?->code)->filter()->unique()->implode(', ');
    @endphp

    <div class="field-row"><span class="label">Name of Owner</span><span class="colon">:</span><span class="value">{{ $ownerName }}</span></div>
    <div class="field-row"><span class="label">Name of Project</span><span class="colon">:</span><span class="value">{{ $application->project_title }}</span></div>
    <div class="field-row"><span class="label">Date of Completion</span><span class="colon">:</span><span class="value">{{ $application->completion_date ? \Carbon\Carbon::parse($application->completion_date)->format('m/d/Y') : '' }}</span></div>
    <div class="field-row"><span class="label">Occupancy Character</span><span class="colon">:</span><span class="value">{{ $occupancyText }}</span></div>
    <div class="field-row"><span class="label">Group</span><span class="colon">:</span><span class="value">{{ $groupCode }}</span></div>
    <div class="field-row"><span class="label">Located at / Along</span><span class="colon">:</span><span class="value">{{ $application->building_street }}{{ $application->buildingBarangay ? ', ' . $application->buildingBarangay->name : '' }}</span></div>

    <div class="maintain-note">
        The Owner shall properly maintain the building/structure to enhance its architectural well being, structural stability, electrical, mechanical, plumbing,
        electronics, interior design and fire-protective properties and shall not be occupied or used for purposes other than its intended use as stated above.
        This certificate of occupancy is hereby issued pursuant to section 309 of the National Building Code of the Philippines (PD 1096), its revised IRR,
        Other Referral Codes and JMC No. 2018-01.
    </div>

    <div class="bottom-row">
        <div class="col box-col">
            <div class="posted-box">
                A Certified copy here of shall be posted within the premises of the building/structure and shall not be removed without authority from the Building Official.
            </div>
        </div>
        <div class="col qr-col">
            @if(!empty($qrImage))
                <img class="qr" src="{{ $qrImage }}">
            @endif
        </div>
        <div class="col sig-col">
            <div class="sig-name">{{ strtoupper(trim(($signatories['building_official']->title ?? '') . ' ' . ($signatories['building_official']->name ?? ''))) }}</div>
            <div class="sig-title">BUILDING OFFICIAL</div>
            <div class="sig-line">Date: <span class="fill">&nbsp;</span></div>
        </div>
    </div>

    <div class="footer-note">
        THIS PERMIT MAY BE CANCELLED OR REVOKED PURSUANT TO SECTION 309 OF THE NATIONAL BUILDING CODE OF THE PHILIPPINES (PD 1096)
    </div>

</div>
</body>
</html>
