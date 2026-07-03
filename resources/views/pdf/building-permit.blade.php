<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Building Permit {{ $permit->permit_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #222; line-height: 1.35; }

        .frame { border: 3px double #1a3d6d; padding: 10mm 12mm; }

        .form-no { font-size: 9px; font-weight: bold; margin-bottom: 4px; }

        .header { text-align: center; margin-bottom: 6px; }
        .header img.seal { height: 60px; margin-bottom: 4px; }
        .header p { margin: 1px 0; font-size: 10.5px; }
        .header .office { font-weight: bold; font-size: 11px; margin-top: 2px; }

        .title { text-align: center; font-weight: bold; font-size: 22px; letter-spacing: 2px; margin: 10px 0 4px; }
        .checkbox-row { text-align: center; font-size: 10px; margin-bottom: 10px; }
        .checkbox-row span { margin: 0 8px; }

        .two-col { display: table; width: 100%; margin-bottom: 8px; }
        .two-col .col { display: table-cell; width: 50%; vertical-align: top; }
        .no-row { font-size: 10px; margin-bottom: 2px; }
        .no-row .label { display: inline-block; }
        .no-row .value { display: inline-block; border-bottom: 1px solid #333; min-width: 130px; padding: 0 4px; font-weight: bold; }

        .intro { font-size: 9.5px; margin: 8px 0 12px; text-align: justify; }
        .intro strong { font-weight: bold; }

        .field-row { display: table; width: 100%; margin-bottom: 5px; }
        .field-row .label { display: table-cell; width: 210px; font-size: 9.5px; vertical-align: top; padding-top: 1px; }
        .field-row .colon { display: table-cell; width: 12px; vertical-align: top; }
        .field-row .value { display: table-cell; border-bottom: 1px solid #333; font-weight: bold; font-size: 10px; padding-bottom: 1px; }

        .sub-row { display: table; width: 100%; margin: 2px 0 5px 210px; width: calc(100% - 210px); }
        .sub-row .item { display: table-cell; padding-right: 10px; font-size: 9.5px; }
        .sub-row .item .value { border-bottom: 1px solid #333; font-weight: bold; padding: 0 4px; }

        .sig-block { margin-top: 26px; text-align: center; }
        .sig-label { font-size: 9.5px; font-weight: bold; margin-bottom: 34px; }
        .sig-name { border-top: 1px solid #333; display: inline-block; min-width: 260px; padding-top: 2px; font-weight: bold; font-size: 10.5px; text-decoration: underline; }
        .sig-title { font-weight: bold; font-size: 9.5px; margin-top: 1px; }
        .sig-caption { font-size: 8px; color: #555; margin-top: 1px; }

        .footer-note { margin-top: 14px; font-size: 8px; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
<div class="frame">

    <div class="form-no">NBC FORM NO. B - 018</div>

    <div class="header">
        @if(!empty($sealImage))
            <img class="seal" src="{{ $sealImage }}">
        @endif
        <p>Republic of the Philippines</p>
        <p>{{ $settings['general.city'] ?? 'City' }}</p>
        <p>Province of {{ $settings['general.province'] ?? 'Province' }}</p>
        <p class="office">OFFICE OF THE BUILDING OFFICIAL</p>
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
        $zip = $application->buildingBarangay?->city?->zip_code;
    @endphp

    <div class="field-row"><span class="label">Owner/Permittee</span><span class="colon">:</span><span class="value">{{ $ownerName }}</span></div>
    <div class="field-row"><span class="label">Project Title</span><span class="colon">:</span><span class="value">{{ $application->project_title }}</span></div>

    <div class="field-row"><span class="label">Location of Construction</span><span class="colon">:</span>
        <span class="value">Lot: {{ $application->lot_no ?: 'na' }} &nbsp; Blk: {{ $application->block_no ?: 'na' }} &nbsp; TD No: {{ $application->tax_dec_no ?: 'na' }} &nbsp; Street: {{ $application->building_street ?: 'na' }}</span>
    </div>
    <div class="sub-row">
        <div class="item">Brgy: <span class="value">{{ $application->buildingBarangay?->name ?? 'na' }}</span></div>
        <div class="item">City/Municipality: <span class="value">{{ $settings['general.city'] ?? '' }}</span></div>
        <div class="item">ZIP Code: <span class="value">{{ $zip ?? '' }}</span></div>
    </div>

    <div class="field-row"><span class="label">Use of Character of Occupancy</span><span class="colon">:</span><span class="value">{{ $occupancyText ?: '' }}</span></div>
    <div class="sub-row">
        <div class="item">and Classified as <span class="value">{{ $application->occupancy_classified ?? '' }}</span></div>
    </div>

    <div class="field-row"><span class="label">Scope of Work</span><span class="colon">:</span><span class="value">{{ strtoupper($application->scopeOfWork?->name ?? '') }}</span></div>
    <div class="field-row"><span class="label">Total Project Cost</span><span class="colon">:</span><span class="value">Php {{ number_format($application->total_estimated_cost ?? 0, 2) }}</span></div>
    <div class="field-row"><span class="label">Professional In Charge of Construction</span><span class="colon">:</span><span class="value">{{ $application->engineer_name }}</span></div>

    <div class="sig-block">
        <div class="sig-label">PERMIT ISSUED BY:</div>
        <div class="sig-name">{{ strtoupper(trim(($signatories['building_official']->title ?? '') . ' ' . ($signatories['building_official']->name ?? ''))) }}</div>
        <div class="sig-title">BUILDING OFFICIAL</div>
        <div class="sig-caption">(Signature Over Printed Name)</div>
    </div>

    <div class="footer-note">
        THIS PERMIT MAY BE CANCELLED OR REVOKED PURSUANT TO SECTIONS 207, 305 AND 306 OF THE NATIONAL BUILDING CODE OF THE PHILIPPINES (PD 1096) AND ITS REVISED IRR.
    </div>

</div>
</body>
</html>
