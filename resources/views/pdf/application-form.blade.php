<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 15mm 12mm; size: legal portrait; }
        body { font-family: Arial, sans-serif; font-size: 9px; color: #333; line-height: 1.3; }
        .header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header h4 { margin: 2px 0; font-size: 10px; text-transform: uppercase; }
        .header h2 { margin: 5px 0; font-size: 13px; }
        .app-no { text-align: right; font-size: 11px; font-weight: bold; margin-bottom: 8px; }
        .section { margin-bottom: 8px; }
        .section-title { font-weight: bold; font-size: 10px; background: #e8e8e8; padding: 3px 6px; margin-bottom: 4px; border: 1px solid #999; }
        table.form { width: 100%; border-collapse: collapse; }
        table.form td { border: 1px solid #ccc; padding: 3px 5px; vertical-align: top; }
        table.form td.label { font-weight: bold; background: #f5f5f5; width: 25%; font-size: 8px; text-transform: uppercase; }
        table.form td.value { width: 25%; }
        .costs td { text-align: right; }
        .costs td.label { text-align: left; }
        .sig-section { margin-top: 15px; }
        .sig-section table { width: 100%; }
        .sig-section td { width: 33%; text-align: center; padding: 5px 10px; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #333; margin-top: 35px; padding-top: 3px; font-size: 8px; }
        .official-use { margin-top: 15px; border: 2px solid #333; padding: 8px; }
        .official-use h4 { margin: 0 0 5px; font-size: 9px; text-transform: uppercase; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h4>Republic of the Philippines</h4>
        <h4>City / Municipality</h4>
        <h4>Office of the Building Official</h4>
        <h2>APPLICATION FOR {{ strtoupper($application->permitType?->name ?? 'BUILDING PERMIT') }}</h2>
    </div>

    <div class="app-no">Application No.: {{ $application->application_number }}</div>

    <div class="section">
        <div class="section-title">1. Applicant / Owner Information</div>
        <table class="form">
            <tr>
                <td class="label">Last Name</td>
                <td class="value">{{ $application->applicant_last_name }}</td>
                <td class="label">First Name</td>
                <td class="value">{{ $application->applicant_first_name }}</td>
            </tr>
            <tr>
                <td class="label">Middle Name</td>
                <td class="value">{{ $application->applicant_middle_name }}</td>
                <td class="label">TIN</td>
                <td class="value">{{ $application->applicant_tin }}</td>
            </tr>
            <tr>
                <td class="label">Contact No.</td>
                <td class="value">{{ $application->applicant_contact_no }}</td>
                <td class="label">Email</td>
                <td class="value">{{ $application->applicant_email }}</td>
            </tr>
            <tr>
                <td class="label">Address</td>
                <td colspan="3">{{ $application->applicant_street }} {{ $application->applicantBarangay?->name }} {{ $application->applicantCity?->name }} {{ $application->applicantProvince?->name }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. Project Details</div>
        <table class="form">
            <tr>
                <td class="label">Project Title</td>
                <td colspan="3">{{ $application->project_title }}</td>
            </tr>
            <tr>
                <td class="label">Scope of Work</td>
                <td class="value">{{ $application->scopeOfWork?->name }}</td>
                <td class="label">Application Type</td>
                <td class="value">{{ $application->applicationType?->name }}</td>
            </tr>
            <tr>
                <td class="label">No. of Storeys</td>
                <td class="value">{{ $application->no_of_storeys }}</td>
                <td class="label">No. of Units</td>
                <td class="value">{{ $application->no_of_units }}</td>
            </tr>
            <tr>
                <td class="label">Total Floor Area</td>
                <td class="value">{{ $application->total_floor_area }} sq.m.</td>
                <td class="label">Lot Area</td>
                <td class="value">{{ $application->lot_area }} sq.m.</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">3. Building Location</div>
        <table class="form">
            <tr>
                <td class="label">Lot No.</td>
                <td class="value">{{ $application->lot_no }}</td>
                <td class="label">Block No.</td>
                <td class="value">{{ $application->block_no }}</td>
            </tr>
            <tr>
                <td class="label">TCT No.</td>
                <td class="value">{{ $application->tct_no }}</td>
                <td class="label">Tax Dec. No.</td>
                <td class="value">{{ $application->tax_dec_no }}</td>
            </tr>
            <tr>
                <td class="label">Street</td>
                <td class="value">{{ $application->building_street }}</td>
                <td class="label">Barangay</td>
                <td class="value">{{ $application->buildingBarangay?->name }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">4. Cost Estimates</div>
        <table class="form costs">
            <tr><td class="label">Building</td><td>&#8369;{{ number_format($application->building_cost, 2) }}</td><td class="label">Electrical</td><td>&#8369;{{ number_format($application->electrical_cost, 2) }}</td></tr>
            <tr><td class="label">Mechanical</td><td>&#8369;{{ number_format($application->mechanical_cost, 2) }}</td><td class="label">Plumbing</td><td>&#8369;{{ number_format($application->plumbing_cost, 2) }}</td></tr>
            <tr><td class="label">Electronics</td><td>&#8369;{{ number_format($application->electronics_cost, 2) }}</td><td class="label">Other Equipment</td><td>&#8369;{{ number_format($application->other_equipment_cost, 2) }}</td></tr>
            <tr><td class="label" style="font-size:10px">TOTAL ESTIMATED COST</td><td colspan="3" style="font-size:10px;font-weight:bold">&#8369;{{ number_format($application->total_estimated_cost, 2) }}</td></tr>
        </table>
    </div>

    @if($application->applicationOccupancyGroups->count())
    <div class="section">
        <div class="section-title">5. Character of Occupancy</div>
        <table class="form">
            @foreach($application->applicationOccupancyGroups as $og)
            <tr>
                <td class="label">{{ $og->occupancyGroup?->code }}</td>
                <td>{{ $og->occupancyGroup?->name }} — {{ $og->occupancySubGroup?->name }}</td>
                <td class="label">Others</td>
                <td>{{ $og->others_text }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    <div class="sig-section">
        <table>
            <tr>
                <td>
                    <div class="sig-line">Applicant / Owner</div>
                    <div style="font-size:8px">{{ $application->applicant_first_name }} {{ $application->applicant_last_name }}</div>
                </td>
                <td>
                    <div class="sig-line">Engineer / Architect</div>
                    <div style="font-size:8px">{{ $application->engineer_name }}</div>
                    <div style="font-size:7px">PRC No. {{ $application->engineer_prc_no }}</div>
                </td>
                <td>
                    <div class="sig-line">Lot Owner</div>
                    <div style="font-size:8px">{{ $application->owner_name }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="official-use">
        <h4>For Official Use Only</h4>
        <table class="form">
            <tr>
                <td class="label">Date Received</td><td></td>
                <td class="label">Received By</td><td></td>
            </tr>
            <tr>
                <td class="label">Action Taken</td><td colspan="3"></td>
            </tr>
        </table>
    </div>
</body>
</html>
