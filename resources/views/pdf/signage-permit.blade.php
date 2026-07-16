<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sign Permit {{ $permit->permit_number }}</title>
    <style>
        @page { size: letter portrait; margin: 0.75in; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #000; line-height: 1.5; }

        .content { padding: 0.75in; }

        .header { text-align: center; margin-bottom: 14px; }
        .header img.seal { height: 75px; margin-bottom: 6px; }
        .header p { margin: 1px 0; font-size: 13px; font-weight: bold; }

        .title { text-align: center; font-size: 20px; font-weight: bold; letter-spacing: 4px; margin: 18px 0; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .info-table td { width: 50%; vertical-align: top; padding: 2px 0; font-size: 13px; }
        .info-table .fill { border-bottom: 1px solid #000; font-weight: bold; padding: 0 4px; }

        .body-text { font-size: 13px; margin-bottom: 4px; }
        .body-text .fill { border-bottom: 1px solid #000; font-weight: bold; padding: 0 4px; }

        .conditions { margin: 14px 0; font-size: 12.5px; }
        .conditions p { margin-bottom: 8px; text-align: justify; }

        .bottom-row { display: table; width: 100%; margin-top: 22px; }
        .bottom-cell { display: table-cell; vertical-align: bottom; }
        .fee-cell { width: auto; }
        .fee-row { display: table; width: 100%; margin-bottom: 4px; font-size: 13px; }
        .fee-row .label { display: table-cell; width: 60px; }
        .fee-row .fill { display: table-cell; border-bottom: 1px solid #000; font-weight: bold; padding: 0 4px; }
        .qr-cell { width: 110px; text-align: center; }
        .qr-cell img.qr { width: 95px; height: 95px; }
        .sig-cell { width: 240px; text-align: center; }
        .sig-line { border-bottom: 1px solid #000; display: block; min-width: 220px; margin-bottom: 2px; }
        .sig-name { font-weight: bold; display: block; }
        .sig-designation { display: block; font-size: 12px; }

        .generated-note { margin-top: 24px; font-size: 10px; text-align: center; color: #555; }
    </style>
</head>
<body>
<div class="content">

    <div class="header">
        @if(!empty($sealImage))
            <img src="{{ $sealImage }}" class="seal" alt="Seal">
        @endif
        <p>Republic of the Philippines</p>
        <p>{{ $settings['general.city'] ?? 'CITY OF SAN FERNANDO' }}</p>
        <p>OFFICE OF THE CITY ENGINEER</p>
    </div>

    <div class="title">SIGN PERMIT</div>

    @php
        $ownerName = trim(($application->applicant_last_name ?? '') . ', ' . ($application->applicant_first_name ?? '') . ' ' . ($application->applicant_middle_name ?? ''));
        $ownerName = preg_replace('/\s+/', ' ', $ownerName);
        $location = trim(collect([$application->applicant_street ?? '', $application->buildingBarangay?->name ?? ''])->filter()->implode(', '));
        $scopeParts = [];
        if ($application->install) $scopeParts[] = 'Install' . ($application->install_detail ? ' — ' . $application->install_detail : '');
        if ($application->attach) $scopeParts[] = 'Attach' . ($application->attach_detail ? ' — ' . $application->attach_detail : '');
        if ($application->paint) $scopeParts[] = 'Paint' . ($application->paint_detail ? ' — ' . $application->paint_detail : '');
        $scopeText = implode('; ', $scopeParts);
        $collection = $application->collections->where('status', 'active')->first();
    @endphp

    <table class="info-table">
        <tr>
            <td>Permit Number:<br><span class="fill">{{ $permit->permit_number }}</span></td>
            <td>Date Issued:<br><span class="fill">{{ $permit->issued_date ? \Carbon\Carbon::parse($permit->issued_date)->format('m/d/Y') : '' }}</span></td>
        </tr>
        <tr>
            <td>District/City/Municipality:<br><span class="fill">{{ $settings['general.city'] ?? 'CITY OF SAN FERNANDO' }}</span></td>
            <td>Area Code:<br><span class="fill">3314-W</span></td>
        </tr>
    </table>

    <p class="body-text">Permit is hereby granted to <span class="fill">{{ $ownerName }}</span></p>
    <p class="body-text">with postal address at <span class="fill">{{ $location }}</span></p>
    <p class="body-text">to <span class="fill">{{ $scopeText }}</span></p>
    <p class="body-text">with the wordings: <span class="fill">{{ $application->wordings }}</span></p>
    <p class="body-text">at the premises of <span class="fill">{{ $application->premises_of }}</span> as per attached sketch or location plan pursuant to pertinent provisions of the National Building Code (P.D. 1096) and its Implementing Rules and Regulations and subject to the following conditions:</p>

    <div class="conditions">
        <p>1. The sign shall be installed in conformity with Rule V of the Implementing Rules and Regulations of P.D. 1096.</p>
        <p>2. In case of electric or neon signs, the corresponding electrical permit shall first be secured.</p>
        <p>3. This permit must be kept in the premises of the establishment wherein the sign is installed for inspection purposes. It may be cancelled or revoked pursuant to Sections 305 and 306 of the National Building Code (P.D. 1096) and when public interest so demands.</p>
    </div>

    <div class="bottom-row">
        <div class="bottom-cell fee-cell">
            <div class="fee-row"><span class="label">Fee:</span> <span class="fill">{{ number_format($collection->amount_due ?? 0, 2) }}</span></div>
            <div class="fee-row"><span class="label">OR/No.:</span> <span class="fill">{{ $collection->or_number ?? '' }}</span></div>
            <div class="fee-row"><span class="label">Date:</span> <span class="fill">{{ $collection?->or_date ? \Carbon\Carbon::parse($collection->or_date)->format('m/d/Y') : '' }}</span></div>
        </div>
        <div class="bottom-cell qr-cell">
            @if(!empty($qrImage))
                <img class="qr" src="{{ $qrImage }}">
            @endif
        </div>
        <div class="bottom-cell sig-cell">
            <span class="sig-line">&nbsp;</span>
            <span class="sig-name">{{ strtoupper(trim(($permit->building_official_title ?? '') . ' ' . ($permit->building_official_name ?? ''))) }}</span>
            <span class="sig-designation">{{ $permit->building_official_designation ?? 'City Engineer / Building Official' }}</span>
        </div>
    </div>

    <div class="generated-note">
        This is a computer-generated document. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}
    </div>

</div>
</body>
</html>
