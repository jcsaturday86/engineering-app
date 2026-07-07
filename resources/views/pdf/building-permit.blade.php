<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Building Permit {{ $permit->permit_number }}</title>
    <style>
        {{-- A4 landscape = 11.69in x 8.27in; 0.5in margin all around leaves a 10.69in x 7.27in frame.
             NOTE: the reset must not target * or html — in dompdf those wipe the @page margin. --}}
        @page { margin: 0.5in; }
        body, div, p, span, img { margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 13.5px; color: #222; line-height: 1.25; }

        {{-- content-box height tuned so the frame's outer edge lands exactly 0.5in from
             all four page edges without pushing content onto a spurious second page --}}
        .frame { border: 6px double #1a3d6d; padding: 4mm 10mm; height: 6.82in; }

        .form-no { font-size: 12px; font-weight: bold; margin-bottom: 2px; }

        .header { margin-bottom: 4px; }
        .header-table { display: table; width: 100%; }
        .header-cell { display: table-cell; vertical-align: middle; }
        .seal-cell { width: 90px; text-align: right; padding-right: 6px; }
        .seal-cell img.seal { height: 100px; }
        .spacer-cell { width: 90px; }
        .text-cell { text-align: center; }
        .header p { margin: 1px 0; font-size: 13.5px; }
        .header .office { font-weight: bold; font-size: 14.5px; margin-top: 2px; }

        .title { text-align: center; font-weight: bold; font-size: 27px; letter-spacing: 2px; margin: 5px 0 2px; }
        .checkbox-row { text-align: center; font-size: 13.5px; margin-bottom: 7px; }
        .checkbox-row span { margin: 0 10px; }

        .two-col { display: table; width: 100%; margin-bottom: 5px; }
        .two-col .col { display: table-cell; width: 50%; vertical-align: top; }
        .no-row { font-size: 13px; margin-bottom: 2px; }
        .no-row .label { display: inline-block; }
        .no-row .value { display: inline-block; border-bottom: 1px solid #333; min-width: 170px; padding: 0 4px; font-weight: bold; }

        .intro { font-size: 12.5px; margin: 5px 0 8px; text-align: justify; }
        .intro strong { font-weight: bold; }

        .field-row { display: table; width: 100%; margin-bottom: 4px; }
        .field-row .label { display: table-cell; width: 260px; font-size: 13px; vertical-align: top; padding-top: 1px; }
        .field-row .colon { display: table-cell; width: 14px; vertical-align: top; }
        .field-row .value { display: table-cell; border-bottom: 1px solid #333; font-weight: bold; font-size: 13.5px; padding-bottom: 1px; }

        .sub-row { display: table; width: 100%; margin: 2px 0 4px; }
        .sub-row .indent { display: table-cell; width: 274px; }
        .sub-row .item { display: table-cell; padding-right: 14px; font-size: 13px; }
        .sub-row .item .value { border-bottom: 1px solid #333; font-weight: bold; padding: 0 4px; }

        .sig-block { margin-top: 10px; text-align: center; }
        .sig-label { font-size: 13px; font-weight: bold; margin-bottom: 14px; }
        .sig-name { display: inline-block; min-width: 300px; padding-top: 2px; font-weight: bold; font-size: 14px; text-decoration: underline; }
        .sig-title { font-weight: bold; font-size: 13px; margin-top: 1px; }
        .sig-line { font-size: 12.5px; margin-top: 8px; }
        .sig-line .fill { border-bottom: 1px solid #333; display: inline-block; min-width: 180px; }

        .bottom-row { display: table; width: 100%; }
        .bottom-cell { display: table-cell; vertical-align: bottom; }
        .qr-cell { width: 130px; text-align: center; }
        .sig-cell { width: auto; }
        .qr-cell img.qr { width: 110px; height: 110px; }

        .footer-note { margin-top: 5px; font-size: 10px; font-weight: bold; text-align: center; white-space: nowrap; line-height: 1.1; }
        .generated-note { margin-top: 2px; font-size: 10px; font-weight: normal; text-align: center; color: #555; line-height: 1.1; }
    </style>
</head>
<body>
<div class="frame">

    <div class="form-no">NBC FORM NO. B - 018</div>

    <div class="header">
        <div class="header-table">
            <div class="header-cell seal-cell">
                @if(!empty($sealImage))
                    <img class="seal" src="{{ $sealImage }}">
                @endif
            </div>
            <div class="header-cell text-cell">
                <p>Republic of the Philippines</p>
                <p>{{ $settings['general.city'] ?? 'City' }}</p>
                <p>Province of {{ $settings['general.province'] ?? 'Province' }}</p>
                <p class="office">OFFICE OF THE BUILDING OFFICIAL</p>
            </div>
            <div class="header-cell spacer-cell"></div>
        </div>
    </div>

    <div class="title">BUILDING PERMIT</div>
    @php
        $appType = strtolower($application->applicationType->name ?? '');
    @endphp
    <div class="checkbox-row">
        <span>[{{ $appType === 'new' ? 'X' : ' ' }}] NEW</span>
        <span>[{{ $appType === 'renewal' ? 'X' : ' ' }}] RENEWAL</span>
        <span>[{{ $appType === 'amendatory' ? 'X' : ' ' }}] AMENDATORY</span>
    </div>

    <div class="two-col">
        <div class="col">
            <div class="no-row"><span class="label">BUILDING PERMIT NO.:</span> <span class="value">{{ $permit->permit_number }}</span></div>
            <div class="no-row"><span class="label">DATE ISSUED :</span> <span class="value">{{ $permit->issued_date ? \Carbon\Carbon::parse($permit->issued_date)->format('m/d/Y') : '' }}</span></div>
            <div class="no-row"><span class="label">FSEC NO. :</span> <span class="value">{{ $application->fsec_no ?? '-' }}</span></div>
            <div class="no-row"><span class="label">DATE ISSUED :</span> <span class="value">{{ $application->fsec_issued_date ? \Carbon\Carbon::parse($application->fsec_issued_date)->format('m/d/Y') : '-' }}</span></div>
        </div>
        <div class="col">
            @php
                $collection = $application->collections->where('status', 'active')->first();
            @endphp
            <div class="no-row"><span class="label">OFFICIAL RECEIPT NO.:</span> <span class="value">{{ $collection->or_number ?? '' }}</span></div>
            <div class="no-row"><span class="label">DATE PAID:</span> <span class="value">{{ $collection && $collection->or_date ? \Carbon\Carbon::parse($collection->or_date)->format('m/d/Y') : '' }}</span></div>
        </div>
    </div>

    <div class="intro">
        This <strong>PERMIT</strong> is issued pursuant to Sections 207, 301, 302, 303 and 304 of the National Building Code of the Philippines (PD 1096), its Revised IRR, other Referral Codes and its Terms and Conditions.
    </div>

    @php
        $ownerName = trim(($application->applicant_last_name ?? '') . ', ' . ($application->applicant_first_name ?? '') . ' ' . ($application->applicant_middle_name ?? '') . ' ' . ($application->applicant_suffix ?? ''));
        $ownerName = preg_replace('/\s+/', ' ', $ownerName);
        $occupancyText = $application->applicationOccupancyGroups->map(fn ($og) => $og->occupancySubGroup?->name ?? $og->occupancyGroup?->name)->filter()->unique()->implode(', ');
        $zip = $settings['general.zip_code'] ?? null;
    @endphp

    <div class="field-row"><span class="label">Owner/Permittee</span><span class="colon">:</span><span class="value">{{ $ownerName }}</span></div>
    <div class="field-row"><span class="label">Project Title</span><span class="colon">:</span><span class="value">{{ $application->project_title }}</span></div>

    <div class="field-row"><span class="label">Location of Construction</span><span class="colon">:</span>
        <span class="value">Lot: {{ $application->lot_no ?: 'na' }} &nbsp; Blk: {{ $application->block_no ?: 'na' }} &nbsp; TD No: {{ $application->tax_dec_no ?: 'na' }} &nbsp; Street: {{ $application->building_street ?: 'na' }}</span>
    </div>
    <div class="sub-row">
        <div class="indent"></div>
        <div class="item">Brgy: <span class="value">{{ $application->buildingBarangay?->name ?? 'na' }}</span></div>
        <div class="item">City/Municipality: <span class="value">{{ $settings['general.city'] ?? '' }}</span></div>
        <div class="item">ZIP Code: <span class="value">{{ $zip ?? '' }}</span></div>
    </div>

    <div class="field-row"><span class="label">Use of Character of Occupancy</span><span class="colon">:</span><span class="value">{{ $occupancyText ?: '' }}</span></div>
    <div class="sub-row">
        <div class="indent"></div>
        <div class="item">and Classified as <span class="value">{{ $application->occupancy_classified ?? '' }}</span></div>
    </div>

    <div class="field-row"><span class="label">Scope of Work</span><span class="colon">:</span><span class="value">{{ strtoupper($application->scopeOfWork?->name ?? '') }}</span></div>
    <div class="field-row"><span class="label">Total Project Cost</span><span class="colon">:</span><span class="value">Php {{ number_format($application->total_estimated_cost ?? 0, 2) }}</span></div>
    <div class="field-row"><span class="label">Professional In Charge of Construction</span><span class="colon">:</span><span class="value">{{ $application->engineer_name }}</span></div>

    <div class="bottom-row">
        <div class="bottom-cell qr-cell">
            @if(!empty($qrImage))
                <img class="qr" src="{{ $qrImage }}">
            @endif
        </div>
        <div class="bottom-cell sig-cell">
            <div class="sig-block">
                <div class="sig-label">PERMIT ISSUED BY:</div>
                <div class="sig-name">{{ strtoupper(trim(($permit->building_official_title ?? '') . ' ' . ($permit->building_official_name ?? ''))) }}</div>
                <div class="sig-title">BUILDING OFFICIAL</div>
                <div class="sig-line">Date: <span class="fill">&nbsp;</span></div>
            </div>
        </div>
        <div class="bottom-cell qr-cell"></div>
    </div>

    <div class="footer-note">
        THIS PERMIT MAY BE CANCELLED OR REVOKED PURSUANT TO SECTIONS 207, 305 AND 306 OF THE NATIONAL BUILDING CODE OF THE PHILIPPINES (PD 1096) AND ITS REVISED IRR.
    </div>
    <div class="generated-note">
        This is a computer-generated permit. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}
    </div>

</div>
</body>
</html>
