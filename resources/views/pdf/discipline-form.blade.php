<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $formTitle }} - {{ $application->application_number }}</title>
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
            font-size: 11px;
            color: #000;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 6px;
        }
        .header img.seal {
            height: 55px;
            margin-bottom: 4px;
        }
        .header p {
            margin: 0;
            font-size: 10.5px;
        }
        .page-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
        }
        .app-info {
            display: table;
            width: 100%;
            margin-bottom: 16px;
            font-size: 10.5px;
        }
        .app-info .col {
            display: table-cell;
            width: 50%;
        }
        .app-info .lbl {
            font-weight: bold;
        }
        .blank-area {
            border: 1px solid #000;
            min-height: 8.5in;
        }
    </style>
</head>
<body>

<div class="header">
    @if(!empty($sealImage))
        <img src="{{ $sealImage }}" class="seal" alt="Official Seal">
    @endif
    <p>Republic of the Philippines</p>
    <p>{{ $settings['general.city'] ?? 'City of San Fernando' }}, Province of {{ $settings['general.province'] ?? 'La Union' }}</p>
</div>

<div class="page-title">{{ strtoupper($formTitle) }}</div>

<div class="app-info">
    <div class="col">
        <span class="lbl">Application No.:</span> {{ $application->application_number }}
    </div>
    <div class="col">
        <span class="lbl">Applicant:</span> {{ $application->applicant_full_name }}
    </div>
</div>

<div class="blank-area"></div>

</body>
</html>
