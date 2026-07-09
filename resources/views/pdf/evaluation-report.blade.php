<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Evaluation Report</title>
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
            margin-bottom: 15px;
        }
        .section-title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            text-decoration: underline;
            margin: 13px 0 8px;
        }
        .field {
            margin-bottom: 8px;
        }
        .field .lbl {
            font-size: 10.5px;
            display: block;
        }
        .field .val {
            border-bottom: 1px solid #000;
            font-weight: bold;
            min-height: 14px;
            display: block;
            padding: 1px 2px;
        }
        .row {
            display: table;
            width: 100%;
        }
        .row > .col {
            display: table-cell;
            vertical-align: top;
        }
        .col-50 { width: 50%; padding-right: 14px; }
        .col-50:last-child { padding-right: 0; }
        .opt-row {
            display: table;
            width: 100%;
            margin: 4px 0 6px;
        }
        .opt-row .col { display: table-cell; vertical-align: top; }
        .opt {
            margin-right: 18px;
            white-space: nowrap;
        }
        table.grid {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 6px;
        }
        table.grid td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 11px;
            vertical-align: top;
        }
        .findings-lines div {
            border-bottom: 1px solid #000;
            min-height: 16px;
            margin-bottom: 4px;
        }
        .form-code {
            margin-top: 18px;
            font-size: 10px;
        }
        .page-break {
            page-break-before: always;
        }
        /* Page 2: conditions checklist */
        .cond-row {
            display: table;
            width: 100%;
            margin-bottom: 7px;
        }
        .cond-box {
            display: table-cell;
            width: 36px;
            vertical-align: middle;
        }
        .cond-box .box {
            border: 1px solid #000;
            width: 25px;
            height: 17px;
            text-align: center;
            font-weight: bold;
            font-size: 10.5px;
            line-height: 17px;
        }
        .cond-text {
            display: table-cell;
            vertical-align: middle;
            font-size: 10.5px;
        }
        .sig-block {
            margin-top: 16px;
        }
        .sig-row {
            display: table;
            width: 100%;
            margin-top: 12px;
        }
        .sig-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .sig-name {
            font-weight: bold;
            font-size: 13px;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 220px;
        }
        .sig-designation {
            font-size: 10.5px;
            margin-top: 1px;
        }
        .sig-role {
            font-size: 10.5px;
            font-style: italic;
        }
        .attach-row {
            display: table;
            width: 100%;
            margin: 12px 0;
        }
        .attach-col {
            display: table-cell;
            width: 50%;
        }
        .content {
            padding: 0.75in;
        }
    </style>
</head>
<body>
<div class="content">

@php
    $za = $application->zoningAssessment;

    $applicantName = strtoupper(trim(
        $application->applicant_last_name . ', ' . $application->applicant_first_name
        . ($application->applicant_middle_name ? ', ' . $application->applicant_middle_name : '')
    ));

    $applicantAddress = trim(collect([
        $application->applicant_street,
        $application->applicantBarangay?->name,
        $application->applicantCity?->name,
    ])->filter()->implode(', '));

    $mark = function ($actual, $expected) {
        return stripos(trim((string) $actual), $expected) === 0 ? '(/)' : '( )';
    };

    $nbsp = "\u{00A0}";
@endphp

{{-- ===================== PAGE 1 ===================== --}}
<div class="header">
    @if(!empty($sealImage))
        <img src="{{ $sealImage }}" class="seal" alt="Official Seal">
    @endif
    <p>Republic of the Philippines</p>
    <p>{{ $settings['general.city'] ?? 'City of San Fernando' }}, Province of {{ $settings['general.province'] ?? 'La Union' }}</p>
</div>

<div class="page-title">EVALUATION REPORT</div>

<div class="section-title">A.&nbsp;&nbsp;APPLICATION AND PROJECT INFORMATION</div>

<div class="row">
    <div class="col col-50">
        <div class="field">
            <span class="lbl">1. Name of Applicant (Last, First, Middle)</span>
            <span class="val">{{ $applicantName ?: $nbsp }}</span>
        </div>
    </div>
    <div class="col col-50">
        <div class="field">
            <span class="lbl">2. Name of Corporation</span>
            <span class="val">{{ $application->enterprise_name ?: $nbsp }}</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col col-50">
        <div class="field">
            <span class="lbl">3. Address of Applicant</span>
            <span class="val">{{ $applicantAddress ?: $nbsp }}</span>
        </div>
    </div>
    <div class="col col-50">
        <div class="field">
            <span class="lbl">4. Address of Corporation</span>
            <span class="val">&nbsp;</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col col-50">
        <div class="field">
            <span class="lbl">5. Project Type</span>
            <span class="val">{{ $za?->project_classification ?: $nbsp }}</span>
        </div>
    </div>
    <div class="col col-50">
        <div class="row">
            <div class="col" style="width:45%; padding-right:10px;">
                <div class="field">
                    <span class="lbl">6. Area (in sq.m.)</span>
                    <span class="val">{{ $application->lot_area ?? $application->total_floor_area ?? $nbsp }}</span>
                </div>
            </div>
            <div class="col" style="width:55%;">
                <div class="field">
                    <span class="lbl">7. Location</span>
                    <span class="val">{{ $application->buildingBarangay?->name ?: $nbsp }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section-title">B.&nbsp;&nbsp;PROJECT EVALUATION</div>

<div class="row">
    <div class="col col-50">
        <div class="lbl">8. Project Lifespan &nbsp;&nbsp;&nbsp; 9. Project Significance</div>
        <div class="opt-row">
            <div class="col">
                <span class="opt">{{ $mark($za?->project_lifespan, 'Permanent') }} Permanent</span>
                <span class="opt">{{ $mark($za?->project_significance, 'Regular') }} Regular</span>
            </div>
        </div>
        <div class="opt-row">
            <div class="col">
                <span class="opt">{{ $mark($za?->project_lifespan, 'Temporary') }} Temporary</span>
                <span class="opt">{{ $mark($za?->project_significance, 'Special') }} Special</span>
            </div>
        </div>
        <div style="font-size:10px; margin-bottom:6px;">(Specify years)</div>

        <div class="field">
            <span class="lbl">13. Existing Land Use in the vicinity</span>
        </div>
        <div style="font-size:10.5px; margin-bottom:2px;">a. Radius covered from lot boundary of project site.</div>
        <div class="opt-row">
            <div class="col">
                <span class="opt">{{ $mark($za?->radius_covered, '100 meters') }} 100 meters (Regular Project)</span>
            </div>
        </div>
        <div class="opt-row">
            <div class="col">
                <span class="opt">{{ $mark($za?->radius_covered, '1 km') }} 1 km (Special Project)</span>
            </div>
        </div>
    </div>
    <div class="col col-50">
        <div class="field">
            <span class="lbl">10. Project Classification</span>
            <span class="val">{{ $za?->project_classification ?: $nbsp }}</span>
        </div>
        <div class="field">
            <span class="lbl">11. Site Zoning Classification</span>
            <span class="val">{{ $za?->site_zoning_classification ?: $nbsp }}</span>
        </div>
        <div class="field">
            <span class="lbl">12. Right Over Land/Proofs</span>
            <span class="val">{{ $za?->right_over_lands ?: $nbsp }}</span>
        </div>
        <div class="field" style="margin-top:20px;">
            <span class="lbl">b. Indicate Land Use/s within radius and corresponding percentage/s.</span>
            <span class="val">{{ $za?->land_use_radius ?: $nbsp }}</span>
        </div>
    </div>
</div>

<div class="section-title">C.&nbsp;&nbsp;LEGAL BASIS FOR EVALUATION AND RECOMMENDED DECISION</div>

<div class="row">
    <div class="col col-50">
        <div class="lbl" style="margin-bottom:4px;">14. Legal Basis</div>
        <div style="margin-bottom:3px;">( ) Ordinance # ______________ S. _____ Approved</div>
        <div style="margin-bottom:3px; margin-left:14px;">per S.P. # ______________ S. _____</div>
        <div style="margin-bottom:3px;">( ) Others (specify law implementing rules,</div>
        <div style="margin-bottom:3px; margin-left:14px;">standards or guidelines.)</div>
        <div style="border-bottom:1px solid #000; min-height:13px; margin-top:4px;">( )</div>
    </div>
    <div class="col col-50">
        <div class="field">
            <span class="lbl">15. Findings and Evaluation of Facts</span>
            <div class="findings-lines">
                <div>{{ $za?->findings_evaluation ?: $nbsp }}</div>
                <div>&nbsp;</div>
                <div>&nbsp;</div>
                <div>&nbsp;</div>
            </div>
        </div>
    </div>
</div>

<div class="field">
    <span class="lbl">16. Decision Recommended</span>
    <span class="val">{{ $za?->decision_recommended ?: $nbsp }}</span>
</div>

<div class="section-title">D.&nbsp;&nbsp;SITE INSPECTION AND FINDINGS (Fill-up if it was inspected)</div>

<div class="row">
    <div class="col col-50">
        <div class="field">
            <span class="lbl">17. Date of Evaluation/Inspection</span>
            <span class="val">{{ $za?->date_evaluation?->format('m/d/Y') ?: $nbsp }}</span>
        </div>
    </div>
    <div class="col col-50">
        <div class="lbl" style="margin-bottom:4px;">18. Project Status as of Inspection Date</div>
        <div class="opt-row"><div class="col">
            <span class="opt">{{ $mark($za?->project_status, 'Proposed') }} Proposed</span>
            <span class="opt">{{ $mark($za?->project_status, 'Completed') }} Completed</span>
        </div></div>
        <div class="opt-row"><div class="col">
            <span class="opt">{{ $mark($za?->project_status, 'Operational') }} Operational</span>
            <span class="opt">{{ $mark($za?->project_status, '% Completed') }} ___% Completed</span>
        </div></div>
        <div class="opt-row"><div class="col">
            <span class="opt">( ) Others (Specify)</span>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col col-50">
        <div class="lbl" style="margin-bottom:4px;">19. Are information provided by applicant true?</div>
        <div class="opt-row"><div class="col">
            <span class="opt">(/) Yes</span>
            <span class="opt">( ) No</span>
        </div></div>
        <div style="font-size:10px;">(Specify findings if no) _______________</div>
    </div>
    <div class="col col-50">
        <div class="lbl" style="margin-bottom:4px;">21. Existing Land Use/s Abutting Lot Boundaries</div>
        <div class="row" style="margin-bottom:2px;">
            <div class="col" style="width:50%;">North (a) <span style="border-bottom:1px solid #000; font-weight:bold;">{{ $za?->boundary_north ?: str_repeat($nbsp, 5) }}</span></div>
            <div class="col" style="width:50%;">South (b) <span style="border-bottom:1px solid #000; font-weight:bold;">{{ $za?->boundary_south ?: str_repeat($nbsp, 5) }}</span></div>
        </div>
        <div class="row">
            <div class="col" style="width:50%;">East (c) <span style="border-bottom:1px solid #000; font-weight:bold;">{{ $za?->boundary_east ?: str_repeat($nbsp, 5) }}</span></div>
            <div class="col" style="width:50%;">West (d) <span style="border-bottom:1px solid #000; font-weight:bold;">{{ $za?->boundary_west ?: str_repeat($nbsp, 5) }}</span></div>
        </div>
    </div>
</div>

<div class="row" style="margin-top:6px;">
    <div class="col col-50">
        <div class="lbl" style="margin-bottom:4px;">20. Land Uses and Distances of Surrounding Properties from the lot boundaries of project within the prescribed distance requirements provided in laws implementing rules/regulations/standards (Fill-up if applicable).</div>
        <table class="grid">
            <tr><td style="width:45%;">Land Uses</td><td>Distance (in meters from project lot boundary)</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
        </table>
    </div>
    <div class="col col-50">
        <div class="lbl" style="margin-bottom:4px;">22. Existing Land Uses within Lot Boundaries of site.</div>
        <div style="font-size:10.5px; margin-bottom:2px;">a. Land Uses / In case of agricultural</div>
        <div style="font-size:10.5px; margin-bottom:2px;">b. Specify crops _______________________</div>
        <div style="font-size:10.5px; margin-bottom:4px;">c. _______________________</div>
        <div style="font-size:10.5px;">Indicate Tenancy Status</div>
        <div style="font-size:10.5px;">( ) tenanted &nbsp;&nbsp; ( ) not tenanted</div>
    </div>
</div>

<div class="field" style="margin-top:6px;">
    <span class="lbl">23. Other Findings</span>
    <span class="val">&nbsp;</span>
</div>

<div class="form-code">PDO-013I-&Oslash;</div>

</div>

{{-- ===================== PAGE 2 ===================== --}}
<div class="content" style="page-break-before: always;">
<div class="page-title">SITE INSPECTION AND EVALUATION REPORT</div>

<div class="section-title">E.&nbsp;&nbsp;CONDITIONS RECOMMENDED</div>

<div style="font-size:10px; margin-bottom:8px;">
    24. CONDITIONS RECOMMENDED OTHER THAN THE STANDARD CONDITIONS APPLICABLE TO ALL PROJECT TYPES AND CASES
    (Mark X on all appropriate conditions.)
</div>

@php
    $cond = fn ($checked) => $checked ? 'XX' : '';
@endphp

<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond(true) }}</div></div>
    <div class="cond-text">Provisions as to setback, yard requirements, bulk, easement, area height and other restrictions shall be strictly conform with the requirements of the National Building Code, other laws and implementing rules, standards, and guidelines. (This condition shall not apply to existing projects.)</div>
</div>
<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond(true) }}</div></div>
    <div class="cond-text">This Decision shall be considered automatically revoked if project is not commenced within one (1) year from date of expiration as above-indicated. This Office holds no assurance that any application for permit renewal may be granted. (This condition shall not apply to existing projects.)</div>
</div>
<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond(false) }}</div></div>
    <div class="cond-text">For the projects granted Temporary Use Permit (TUP), applicant hereof should terminate project activities at the date of expiration as above-indicated. This Office holds no assurance that any application for permit renewal may be granted. (This condition shall not apply to decision granting NON RENEWABLE TUP).</div>
</div>
<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond(true) }}</div></div>
    <div class="cond-text">Any compliant against the issuance of this Clearance found valid after due hearing shall be sufficient cause for its revocation.</div>
</div>
<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond($za?->secure_ecc) }}</div></div>
    <div class="cond-text">Secure an Environmental Compliance Certificate (ECC) or Certificate of Non-Coverage (CNC) from DENR prior to introducing development within the site. Submit a copy thereof to this Office within six (6) months from issuance of this clearance.</div>
</div>
<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond(true) }}</div></div>
    <div class="cond-text">Regular and adequate waste/garbage disposal shall be strictly observed. (For applicable projects only).</div>
</div>
<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond($za?->off_street_parking) }}</div></div>
    <div class="cond-text">Adequate off-street parking space, loading and unloading areas shall be provided within the project site. (For applicable projects only).</div>
</div>
<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond(true) }}</div></div>
    <div class="cond-text">Adequate safety measures against fire shall be provided at all times (For applicable projects only).</div>
</div>
<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond(true) }}</div></div>
    <div class="cond-text">Secure Conversion Clearance or Exemption Certificate for Conversion, whichever is applicable prior to introducing development within the site and submit to this office a copy thereof within six (6) months from the date of issuance of this Clearance.</div>
</div>
<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond(true) }}</div></div>
    <div class="cond-text">This Clearance is without prejudice to the rights and interests of parties having valid claims over the lot subject of the application.</div>
</div>
<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond(true) }}</div></div>
    <div class="cond-text">That the building to be constructed shall be confined with the ____________________ indicated in Tax Declaration # {{ $application->tax_dec_no ?: '____________________' }} TCT No. {{ $application->tct_no ?: '____________________' }}.</div>
</div>
<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond(true) }}</div></div>
    <div class="cond-text">That this Locational Clearance is nontransferable.</div>
</div>
<div class="cond-row">
    <div class="cond-box"><div class="box">{{ $cond(false) }}</div></div>
    <div class="cond-text">Additional Conditions:</div>
</div>

<div class="section-title">F.&nbsp;&nbsp;SIGNATORIES</div>

<div class="sig-row">
    <div class="sig-col">
        <div class="lbl">25. Prepared by:</div>
        <div class="sig-block">
            @if(isset($signatories['planning_officer']))
                <div class="sig-name">{{ strtoupper(trim(($signatories['planning_officer']->title ?? '') . ' ' . $signatories['planning_officer']->name)) }}</div>
                <div class="sig-designation">{{ $signatories['planning_officer']->designation }}</div>
            @else
                <div class="sig-name">&nbsp;</div>
                <div class="sig-designation">&nbsp;</div>
            @endif
            <div class="sig-role">Inspector/Evaluator</div>
            <div style="margin-top:6px;">Date: <span style="border-bottom:1px solid #000; font-weight:bold;">{{ $za?->certificate_date?->format('m/d/Y') ?: str_repeat($nbsp, 10) }}</span></div>
        </div>
    </div>
    <div class="sig-col">
        <div class="lbl">26. REVIEWED/VERIFIED TRUE:</div>
        <div class="sig-block" style="margin-top:14px;">
            @if(isset($signatories['planning_officer']))
                <div class="sig-name">{{ strtoupper(trim(($signatories['planning_officer']->title ?? '') . ' ' . $signatories['planning_officer']->name)) }}</div>
                <div class="sig-designation">{{ $signatories['planning_officer']->designation }}</div>
            @else
                <div class="sig-name">&nbsp;</div>
                <div class="sig-designation">&nbsp;</div>
            @endif
            <div class="sig-role">Zoning Inspector</div>
            <div style="margin-top:6px;">Date: <span style="border-bottom:1px solid #000; font-weight:bold;">{{ $za?->certificate_date?->format('m/d/Y') ?: str_repeat($nbsp, 10) }}</span></div>
        </div>
    </div>
</div>

<div class="section-title">G.&nbsp;&nbsp;REPORT ATTACHMENTS</div>

<div class="attach-row">
    <div class="attach-col">( ) Vicinity Maps as Inspected</div>
    <div class="attach-col">( ) Supplementary Report</div>
</div>
<div style="text-align:center; margin-bottom:10px;">( ) Others (Specify) ______________________________</div>

<div class="form-code">PDO-013II-&Oslash;</div>

</div>
</body>
</html>
