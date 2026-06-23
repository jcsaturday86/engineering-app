<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Building Permit</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; color: #333; line-height: 1.3; }
        .header { text-align: center; margin-bottom: 8px; border-bottom: 3px double #333; padding-bottom: 6px; }
        .header h4 { font-size: 9px; text-transform: uppercase; margin: 1px 0; letter-spacing: 1px; }
        .header h2 { font-size: 14px; margin: 4px 0; letter-spacing: 2px; }
        .permit-no { text-align: center; font-size: 11px; font-weight: bold; margin: 6px 0; }
        .two-col { display: table; width: 100%; margin-bottom: 6px; }
        .two-col .col { display: table-cell; width: 50%; vertical-align: top; padding: 0 8px; }
        .field { margin-bottom: 3px; }
        .field-label { font-size: 8px; color: #666; text-transform: uppercase; }
        .field-value { font-size: 10px; font-weight: bold; border-bottom: 1px solid #ccc; min-height: 14px; padding: 1px 0; }
        .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; background: #e8e8e8; padding: 3px 6px; margin: 6px 0 4px; border: 1px solid #999; }
        table.specs { width: 100%; border-collapse: collapse; margin: 4px 0; }
        table.specs td, table.specs th { border: 1px solid #aaa; padding: 3px 5px; font-size: 9px; }
        table.specs th { background: #f0f0f0; text-align: left; font-size: 8px; text-transform: uppercase; }
        table.specs td.amount { text-align: right; }
        .costs-grid { display: table; width: 100%; }
        .costs-grid .cost-item { display: table-cell; width: 16.66%; text-align: center; padding: 3px; border: 1px solid #ccc; }
        .cost-label { font-size: 7px; color: #666; text-transform: uppercase; }
        .cost-value { font-size: 9px; font-weight: bold; }
        .sig-section { margin-top: 12px; }
        .sig-row { display: table; width: 100%; }
        .sig-cell { display: table-cell; width: 33.33%; text-align: center; padding: 5px 15px; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #333; margin-top: 30px; padding-top: 2px; font-size: 9px; font-weight: bold; }
        .sig-title { font-size: 7px; color: #666; }
        .occ-list { font-size: 9px; margin: 3px 0; }
        .footer { margin-top: 8px; text-align: center; font-size: 7px; color: #888; border-top: 1px solid #ccc; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h4>Republic of the Philippines</h4>
        <h4>City / Municipality</h4>
        <h4>Office of the Building Official</h4>
        <h2>BUILDING PERMIT</h2>
    </div>

    <div class="permit-no">
        Permit No.: {{ $permit->permit_number }}
        &nbsp;&nbsp;&nbsp;&nbsp;
        Date Issued: {{ $permit->issued_date ? \Carbon\Carbon::parse($permit->issued_date)->format('F d, Y') : '' }}
    </div>

    {{-- Owner / Project Info --}}
    <div class="two-col">
        <div class="col">
            <div class="section-title">Owner / Applicant Information</div>
            <div class="field">
                <div class="field-label">Name</div>
                <div class="field-value">{{ $application->applicant_last_name }}, {{ $application->applicant_first_name }} {{ $application->applicant_middle_name }} {{ $application->applicant_suffix }}</div>
            </div>
            <div class="field">
                <div class="field-label">Address</div>
                <div class="field-value">{{ $application->applicant_street }} {{ $application->applicantBarangay?->name }} {{ $application->applicantCity?->name }} {{ $application->applicantProvince?->name }}</div>
            </div>
            <div class="field">
                <div class="field-label">Contact No.</div>
                <div class="field-value">{{ $application->applicant_contact_no }}</div>
            </div>
            <div class="field">
                <div class="field-label">TIN</div>
                <div class="field-value">{{ $application->applicant_tin }}</div>
            </div>
        </div>
        <div class="col">
            <div class="section-title">Project Information</div>
            <div class="field">
                <div class="field-label">Project Title</div>
                <div class="field-value">{{ $application->project_title }}</div>
            </div>
            <div class="field">
                <div class="field-label">Scope of Work</div>
                <div class="field-value">{{ $application->scopeOfWork?->name }}</div>
            </div>
            <div class="field">
                <div class="field-label">Location</div>
                <div class="field-value">{{ $application->building_street }} {{ $application->buildingBarangay?->name }}</div>
            </div>
            <div class="field">
                <div class="field-label">Lot / Block / TCT</div>
                <div class="field-value">Lot {{ $application->lot_no }} Blk {{ $application->block_no }} TCT {{ $application->tct_no }}</div>
            </div>
        </div>
    </div>

    {{-- Building Details --}}
    <div class="section-title">Building Details</div>
    <table class="specs">
        <tr>
            <th>No. of Storeys</th>
            <th>No. of Units</th>
            <th>Total Floor Area (sq.m.)</th>
            <th>Lot Area (sq.m.)</th>
            <th>Application Type</th>
        </tr>
        <tr>
            <td>{{ $application->no_of_storeys }}</td>
            <td>{{ $application->no_of_units }}</td>
            <td>{{ number_format($application->total_floor_area ?? 0, 2) }}</td>
            <td>{{ number_format($application->lot_area ?? 0, 2) }}</td>
            <td>{{ $application->applicationType?->name }}</td>
        </tr>
    </table>

    {{-- Cost Estimates --}}
    <div class="section-title">Cost Estimates</div>
    <div class="costs-grid">
        <div class="cost-item">
            <div class="cost-label">Building</div>
            <div class="cost-value">&#8369;{{ number_format($application->building_cost, 2) }}</div>
        </div>
        <div class="cost-item">
            <div class="cost-label">Electrical</div>
            <div class="cost-value">&#8369;{{ number_format($application->electrical_cost, 2) }}</div>
        </div>
        <div class="cost-item">
            <div class="cost-label">Mechanical</div>
            <div class="cost-value">&#8369;{{ number_format($application->mechanical_cost, 2) }}</div>
        </div>
        <div class="cost-item">
            <div class="cost-label">Plumbing</div>
            <div class="cost-value">&#8369;{{ number_format($application->plumbing_cost, 2) }}</div>
        </div>
        <div class="cost-item">
            <div class="cost-label">Electronics</div>
            <div class="cost-value">&#8369;{{ number_format($application->electronics_cost, 2) }}</div>
        </div>
        <div class="cost-item">
            <div class="cost-label">Total Est. Cost</div>
            <div class="cost-value">&#8369;{{ number_format($application->total_estimated_cost, 2) }}</div>
        </div>
    </div>

    {{-- Character of Occupancy --}}
    @if($application->applicationOccupancyGroups->count())
    <div class="section-title">Character of Occupancy</div>
    <div class="occ-list">
        @foreach($application->applicationOccupancyGroups as $og)
            <strong>{{ $og->occupancyGroup?->code }}.</strong> {{ $og->occupancyGroup?->name }} — {{ $og->occupancySubGroup?->name }}{{ $og->others_text ? ' ('.$og->others_text.')' : '' }};
        @endforeach
    </div>
    @endif

    {{-- Payment Info --}}
    @php
        $collection = $application->collections->where('status', 'active')->first();
    @endphp
    @if($collection)
    <div class="section-title">Payment Details</div>
    <table class="specs">
        <tr>
            <th>OR Number</th><th>OR Date</th><th>Amount Paid</th><th>Payment Mode</th>
        </tr>
        <tr>
            <td>{{ $collection->or_number }}</td>
            <td>{{ $collection->or_date }}</td>
            <td class="amount">&#8369;{{ number_format($collection->amount_due, 2) }}</td>
            <td>{{ ucfirst($collection->payment_mode) }}</td>
        </tr>
    </table>
    @endif

    {{-- Engineer Info --}}
    <div class="section-title">Designing Engineer / Architect</div>
    <div class="two-col">
        <div class="col">
            <div class="field">
                <div class="field-label">Name</div>
                <div class="field-value">{{ $application->engineer_name }}</div>
            </div>
        </div>
        <div class="col">
            <div class="field">
                <div class="field-label">PRC No. / PTR No.</div>
                <div class="field-value">{{ $application->engineer_prc_no }} / {{ $application->engineer_ptr_no }}</div>
            </div>
        </div>
    </div>

    {{-- Signatures --}}
    <div class="sig-section">
        <div class="sig-row">
            <div class="sig-cell">
                <div class="sig-line">{{ $application->applicant_first_name }} {{ $application->applicant_last_name }}</div>
                <div class="sig-title">Owner / Applicant</div>
            </div>
            <div class="sig-cell">
                <div class="sig-line">{{ $application->engineer_name }}</div>
                <div class="sig-title">Engineer / Architect</div>
            </div>
            <div class="sig-cell">
                <div class="sig-line">{{ $signatories['building_official']->name ?? '_______________' }}</div>
                <div class="sig-title">{{ $signatories['building_official']->designation ?? 'Building Official' }}</div>
            </div>
        </div>
    </div>

    <div class="footer">
        This permit is non-transferable and shall be valid for a period of one (1) year from the date of issuance.
        &nbsp;|&nbsp; Application No.: {{ $application->application_number }}
    </div>
</body>
</html>
