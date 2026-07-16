<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 15mm 10mm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9px; color: #333; }
        .header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header h4 { margin: 2px 0; font-size: 10px; text-transform: uppercase; }
        .header h2 { margin: 5px 0; font-size: 14px; }
        .meta { margin-bottom: 10px; font-size: 9px; }
        table.report { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.report th, table.report td { border: 1px solid #aaa; padding: 4px 5px; word-wrap: break-word; overflow-wrap: break-word; }
        table.report th { background: #e8e8e8; font-weight: bold; text-align: left; font-size: 8px; text-transform: uppercase; }
        table.report td.amount { text-align: right; }
        table.report tr:nth-child(even) { background: #f9f9f9; }
        table.report.permits { font-size: 8px; }
        .summary { margin-top: 10px; text-align: right; font-size: 10px; }
        .footer { margin-top: 15px; font-size: 8px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>
            @switch($reportType)
                @case('permits') PERMIT REPORT @break
                @case('revenue') REVENUE REPORT @break
                @case('collections') COLLECTION REPORT @break
                @case('applications') BUILDING PERMIT APPLICATIONS REPORT @break
                @case('occupancy') OCCUPANCY PERMIT APPLICATIONS REPORT @break
                @case('demolition') DEMOLITION PERMIT APPLICATIONS REPORT @break
                @case('signage') SIGNAGE PERMIT APPLICATIONS REPORT @break
                @case('zoning') ZONING ASSESSMENT REPORT @break
            @endswitch
        </h2>
    </div>

    <div class="meta">
        <strong>Period:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('F d, Y') }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Generated:</strong> {{ now()->format('F d, Y h:i A') }}
    </div>

    @if($reportType === 'permits')
    <table class="report permits">
        <colgroup>
            <col style="width: 3%">
            <col style="width: 11%">
            <col style="width: 11%">
            <col style="width: 9%">
            <col style="width: 12%">
            <col style="width: 18%">
            <col style="width: 8%">
            <col style="width: 10%">
            <col style="width: 13%">
            <col style="width: 5%">
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Application No.</th>
                <th>Permit No.</th>
                <th>Permit Type</th>
                <th>Applicant</th>
                <th>Project Title</th>
                <th>Status</th>
                <th>Est. Cost</th>
                <th>Date</th>
                <th>TTA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $i => $app)
            @php
                $permit = $app->permits->first();
                $isRevoked = false;
                if (! $permit) {
                    $permit = $app->permits()->onlyTrashed()->where('status', 'revoked')->latest('deleted_at')->first();
                    $isRevoked = (bool) $permit;
                }
                $tatStart = $app->submitted_at ?? $app->created_at;
                $tatDays = $permit ? (int) floor($tatStart->diffInDays($permit->created_at, true)) : null;
                $permitDate = $permit?->issued_date ?? $permit?->created_at;
                $dateRange = $permitDate ? $app->created_at->format('M d, Y') . ' - ' . $permitDate->format('M d, Y') : $app->created_at->format('M d, Y');
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $app->application_number }}</td>
                <td>{{ $permit->permit_number ?? '-' }}</td>
                <td>{{ $app->permitType?->name }}</td>
                <td>{{ $app->applicant_last_name }}, {{ $app->applicant_first_name }}</td>
                <td>{{ $app->project_title }}</td>
                <td>{{ $isRevoked ? 'Permit Revoked' : ucfirst(str_replace('_', ' ', $app->status)) }}</td>
                <td class="amount">&#8369;{{ number_format($app->total_estimated_cost, 2) }}</td>
                <td>{{ $dateRange }}</td>
                <td>{{ $tatDays !== null ? $tatDays . ' day' . ($tatDays == 1 ? '' : 's') : '–' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="summary">Total Records: {{ $data->count() }}</div>
    @elseif($reportType === 'applications')
    <table class="report">
        <colgroup>
            <col style="width: 25px">
            <col>
            <col>
            <col>
            <col>
            <col>
            <col>
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>App No.</th>
                <th>Applicant</th>
                <th>Project</th>
                <th>Status</th>
                <th>Date</th>
                <th>TTA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $i => $app)
            @php
                $permit = $app->permits->sortByDesc('created_at')->first();
                $tatStart = $app->submitted_at ?? $app->created_at;
                $tatDays = $permit ? (int) floor($tatStart->diffInDays($permit->created_at, true)) : null;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $app->application_number }}</td>
                <td>{{ $app->applicant_last_name }}, {{ $app->applicant_first_name }}</td>
                <td>{{ $app->project_title ?? '-' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $app->status)) }}</td>
                <td>{{ $app->created_at->format('M d, Y') }}</td>
                <td>{{ $tatDays !== null ? $tatDays . ' day' . ($tatDays == 1 ? '' : 's') : '–' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="summary">Total Records: {{ $data->count() }}</div>
    @elseif($reportType === 'occupancy')
    <table class="report">
        <colgroup>
            <col style="width: 25px">
            <col>
            <col>
            <col>
            <col>
            <col>
            <col>
            <col>
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>App No.</th>
                <th>Type</th>
                <th>Applicant</th>
                <th>Project</th>
                <th>Status</th>
                <th>Date</th>
                <th>TTA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $i => $app)
            @php
                $permit = $app->permits->sortByDesc('created_at')->first();
                $tatStart = $app->submitted_at ?? $app->created_at;
                $tatDays = $permit ? (int) floor($tatStart->diffInDays($permit->created_at, true)) : null;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $app->application_number }}</td>
                <td>{{ $app->applicationType->name ?? 'OP' }}</td>
                <td>{{ $app->applicant_last_name }}, {{ $app->applicant_first_name }}</td>
                <td>{{ $app->project_title ?? '-' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $app->status)) }}</td>
                <td>{{ $app->created_at->format('M d, Y') }}</td>
                <td>{{ $tatDays !== null ? $tatDays . ' day' . ($tatDays == 1 ? '' : 's') : '–' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="summary">Total Records: {{ $data->count() }}</div>
    @elseif($reportType === 'demolition' || $reportType === 'signage')
    <table class="report">
        <colgroup>
            <col style="width: 25px">
            <col>
            <col>
            <col>
            <col>
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>App No.</th>
                <th>Applicant</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $i => $app)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $app->application_number }}</td>
                <td>{{ $app->applicant_last_name }}, {{ $app->applicant_first_name }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $app->status)) }}</td>
                <td>{{ $app->created_at->format('M d, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="summary">Total Records: {{ $data->count() }}</div>
    @elseif($reportType === 'zoning')
    <table class="report">
        <colgroup>
            <col style="width: 25px">
            <col>
            <col>
            <col>
            <col>
            <col>
            <col>
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Application No.</th>
                <th>Applicant</th>
                <th>Project Title</th>
                <th>Building Location</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $i => $app)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $app->application_number }}</td>
                <td>{{ $app->applicant_last_name }}, {{ $app->applicant_first_name }}</td>
                <td>{{ $app->project_title ?? '-' }}</td>
                <td>{{ $app->building_street ?? '' }}{{ $app->building_street && $app->buildingBarangay ? ', ' : '' }}{{ $app->buildingBarangay->name ?? '' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $app->status)) }}</td>
                <td>{{ $app->created_at->format('M d, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="summary">Total Records: {{ $data->count() }}</div>
    @else
    <table class="report">
        <thead>
            <tr>
                <th>#</th>
                <th>OR Number</th>
                <th>Date</th>
                <th>Application No.</th>
                <th>Paid By</th>
                <th>Amount</th>
                <th>Payment Mode</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAmount = 0; @endphp
            @foreach($data as $i => $col)
            @php $totalAmount += $col->amount_due; @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $col->or_number }}</td>
                <td>{{ $col->or_date instanceof \Carbon\Carbon ? $col->or_date->format('M d, Y') : $col->or_date }}</td>
                <td>{{ $col->application?->application_number }}</td>
                <td>{{ $col->paid_by }}</td>
                <td class="amount">&#8369;{{ number_format($col->amount_due, 2) }}</td>
                <td>{{ ucfirst($col->payment_mode) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="summary">
        Total Records: {{ $data->count() }} &nbsp;|&nbsp; Total Amount: &#8369;{{ number_format($totalAmount, 2) }}
    </div>
    @endif

    <div class="footer">
        Engineering Permit Management System &middot; Report generated on {{ now()->format('F d, Y h:i A') }}
    </div>
</body>
</html>
