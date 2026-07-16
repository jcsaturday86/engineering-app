<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fencing Permit {{ $permit->permit_number }}</title>
    <style>
        @page { size: letter portrait; margin: 0.45in; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; line-height: 1.2; }

        .form-no { font-size: 8.5px; margin-bottom: 2px; }
        .header { text-align: center; margin-bottom: 3px; }
        .header .office { font-size: 12px; font-weight: bold; }
        .header .title { font-size: 19px; font-weight: bold; margin-top: 2px; }

        .top-row { display: table; width: 100%; margin: 6px 0; border-collapse: collapse; }
        .top-row .cell { display: table-cell; width: 33.33%; border: 1px solid #000; padding: 3px 6px; font-size: 9px; vertical-align: top; }
        .top-row .fill { display: block; margin-top: 6px; border-bottom: 1px solid #000; min-height: 11px; font-weight: bold; font-size: 9.5px; }

        .box { border: 1px solid #000; padding: 4px 7px; margin-bottom: 5px; }
        .box-title { font-weight: bold; font-size: 9px; margin-bottom: 2px; }
        .box-half { width: 100%; margin-bottom: 5px; overflow: hidden; }
        .box-half .col { display: inline-block; width: 49%; vertical-align: top; }
        .box-half .col + .col { }

        .field-row { display: table; width: 100%; margin-bottom: 2px; }
        .field-row .f { display: table-cell; padding-right: 6px; vertical-align: bottom; }
        .field-row .lbl { font-size: 7.5px; color: #333; }
        .field-row .val { border-bottom: 1px solid #000; font-weight: bold; font-size: 9px; min-height: 11px; }

        .checkbox-row { font-size: 9px; margin: 2px 0; }
        .checkbox-row .opt { display: inline-block; margin-right: 14px; }
        .checkbox-row .box-mark { display: inline-block; width: 9px; height: 9px; border: 1px solid #000; margin-right: 3px; text-align: center; line-height: 9px; font-size: 8px; }

        .sig-block { text-align: center; margin-top: 10px; }
        .sig-line { border-bottom: 1px solid #000; min-width: 200px; display: inline-block; }
        .sig-label { font-size: 8px; margin-top: 2px; }

        .notary-text { font-size: 8.5px; text-align: justify; margin-top: 2px; }
        .notary-fill { border-bottom: 1px solid #000; display: inline-block; min-width: 60px; }

        .page-break { page-break-before: always; }

        table.progress { width: 100%; border-collapse: collapse; font-size: 9px; margin-top: 4px; }
        table.progress th, table.progress td { border: 1px solid #000; padding: 2px 4px; text-align: center; }
        table.progress th { font-weight: bold; background: #f0f0f0; }
        table.progress td.label { text-align: left; }

        table.fees { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-top: 8px; }
        table.fees th, table.fees td { border: 1px solid #000; padding: 2px 6px; }
        table.fees th { font-weight: bold; background: #f0f0f0; text-align: center; }
        table.fees td.label { text-align: left; }
        table.fees td.amt { text-align: right; }
        table.fees tr.total td { font-weight: bold; }

        .conditions { font-size: 8.5px; margin-top: 3px; line-height: 1.15; }
        .conditions ol { padding-left: 16px; }
        .conditions li { margin-bottom: 2px; text-align: justify; }
        .conditions .sub { padding-left: 16px; list-style-type: lower-alpha; }

        .footer-note { margin-top: 8px; font-size: 8px; text-align: center; color: #555; }
    </style>
</head>
<body>

{{-- ══════════════════════ PAGE 1 : BOXES 1–5 ══════════════════════ --}}
<div class="form-no">NBC FORM NO. B-03</div>

<div class="header">
    <div class="office">OFFICE OF THE BUILDING OFFICIAL</div>
    <div class="title">FENCING PERMIT</div>
</div>

@php
    $ownerName = trim(($application->applicant_last_name ?? '') . ', ' . ($application->applicant_first_name ?? '') . ' ' . ($application->applicant_middle_name ?? ''));
    $ownerName = preg_replace('/\s+/', ' ', $ownerName);
    $applicantAddress = trim(collect([$application->applicant_street ?? '', $application->applicantBarangay?->name ?? '', $application->applicantCity?->name ?? ''])->filter()->implode(', '));
    $scopeLabels = [
        'new_construction' => 'NEW CONSTRUCTION', 'erection' => 'ERECTION', 'addition' => 'ADDITION',
        'repair' => 'REPAIR', 'others' => 'OTHERS',
    ];
    $collection = $application->collections->where('status', 'active')->first();
    $fencingAssessment = $application->assessments->firstWhere('assessment_type', 'fencing');
    $fencingFeeTotal = $fencingAssessment?->assessmentItems?->where('is_active', true)->sum('amount') ?? 0;
@endphp

<div class="top-row">
    <div class="cell">
        Application No.
        <span class="fill">{{ $application->application_number }}</span>
    </div>
    <div class="cell">
        FP No.
        <span class="fill">{{ $permit->permit_number }}</span>
    </div>
    <div class="cell">
        Building Permit No.
        <span class="fill">&nbsp;</span>
    </div>
</div>

<div class="box">
    <div class="box-title">BOX 1 (TO BE ACCOMPLISHED BY THE OWNER/APPLICANT)</div>
    <div class="field-row">
        <div class="f" style="width:60%"><span class="lbl">Owner/Applicant (Last, First, M.I.)</span><div class="val">{{ $ownerName }}</div></div>
        <div class="f" style="width:40%"><span class="lbl">TIN</span><div class="val">{{ $application->applicant_tin }}</div></div>
    </div>
    <div class="field-row">
        <div class="f" style="width:50%"><span class="lbl">For Construction Owned By an Enterprise</span><div class="val">{{ $application->owned_by_enterprise ? $application->enterprise_name : '' }}</div></div>
        <div class="f" style="width:50%"><span class="lbl">Form of Ownership</span><div class="val">{{ $application->formOfOwnership?->name }}</div></div>
    </div>
    <div class="field-row">
        <div class="f"><span class="lbl">Address (No., Street, Barangay, City/Municipality, Zip Code, Telephone No.)</span>
            <div class="val">{{ $applicantAddress }}{{ $application->applicant_zip_code ? ', ' . $application->applicant_zip_code : '' }}{{ $application->applicant_telephone ? ' — ' . $application->applicant_telephone : '' }}</div>
        </div>
    </div>
    <div class="field-row">
        <div class="f" style="width:25%"><span class="lbl">Lot No.</span><div class="val">{{ $application->lot_no }}</div></div>
        <div class="f" style="width:25%"><span class="lbl">Blk No.</span><div class="val">{{ $application->block_no }}</div></div>
        <div class="f" style="width:25%"><span class="lbl">TCT No.</span><div class="val">{{ $application->tct_no }}</div></div>
        <div class="f" style="width:25%"><span class="lbl">Tax Dec. No.</span><div class="val">{{ $application->tax_dec_no }}</div></div>
    </div>
    <div class="field-row">
        <div class="f" style="width:50%"><span class="lbl">Location — Street</span><div class="val">{{ $application->construction_street }}</div></div>
        <div class="f" style="width:50%"><span class="lbl">Barangay</span><div class="val">{{ $application->constructionBarangay?->name }}</div></div>
    </div>
    <div class="checkbox-row">
        <span class="lbl" style="display:block;margin-bottom:2px;">SCOPE OF WORK</span>
        @foreach($scopeLabels as $key => $label)
            <span class="opt"><span class="box-mark">{{ $application->scope_of_work === $key ? 'X' : '' }}</span>{{ $label }}</span>
        @endforeach
        @if(in_array($application->scope_of_work, ['repair', 'others']) && $application->scope_of_work_detail)
            <div style="margin-top:3px;"><span class="lbl">Specify:</span> <span class="val" style="display:inline-block;min-width:200px;">{{ $application->scope_of_work_detail }}</span></div>
        @endif
    </div>
</div>

<div class="box-half">
    <div class="col">
        <div class="box">
            <div class="box-title">BOX 2 — DESIGN PROFESSIONAL, PLANS AND SPECIFICATIONS</div>
            <div class="sig-block" style="margin-top:6px;">
                <span class="sig-line">&nbsp;</span>
                <div class="sig-label">{{ strtoupper($application->design_professional_name ?? '') }}<br>(Signed and Sealed Over Printed Name)</div>
            </div>
            <div class="field-row" style="margin-top:4px;">
                <div class="f"><span class="lbl">Address</span><div class="val">{{ $application->design_professional_address }}</div></div>
            </div>
            <div class="field-row">
                <div class="f" style="width:50%"><span class="lbl">PRC No.</span><div class="val">{{ $application->design_professional_prc_no }}</div></div>
                <div class="f" style="width:50%"><span class="lbl">Validity</span><div class="val">{{ optional($application->design_professional_prc_validity)->format('m/d/Y') }}</div></div>
            </div>
            <div class="field-row">
                <div class="f" style="width:50%"><span class="lbl">PTR No.</span><div class="val">{{ $application->design_professional_ptr_no }}</div></div>
                <div class="f" style="width:50%"><span class="lbl">Date Issued</span><div class="val">{{ optional($application->design_professional_ptr_date_issued)->format('m/d/Y') }}</div></div>
            </div>
            <div class="field-row">
                <div class="f" style="width:50%"><span class="lbl">Issued at</span><div class="val">{{ $application->design_professional_ptr_issued_at }}</div></div>
                <div class="f" style="width:50%"><span class="lbl">TIN</span><div class="val">{{ $application->design_professional_tin }}</div></div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="box">
            <div class="box-title">BOX 3 — FULL-TIME INSPECTOR OR SUPERVISOR OF CONSTRUCTION WORKS</div>
            <div class="sig-block" style="margin-top:6px;">
                <span class="sig-line">&nbsp;</span>
                <div class="sig-label">{{ strtoupper($application->inspector_name ?? '') }}<br>(Signed and Sealed Over Printed Name)</div>
            </div>
            <div class="field-row" style="margin-top:4px;">
                <div class="f"><span class="lbl">Address</span><div class="val">{{ $application->inspector_address }}</div></div>
            </div>
            <div class="field-row">
                <div class="f" style="width:50%"><span class="lbl">PRC No.</span><div class="val">{{ $application->inspector_prc_no }}</div></div>
                <div class="f" style="width:50%"><span class="lbl">Validity</span><div class="val">{{ optional($application->inspector_prc_validity)->format('m/d/Y') }}</div></div>
            </div>
            <div class="field-row">
                <div class="f" style="width:50%"><span class="lbl">PTR No.</span><div class="val">{{ $application->inspector_ptr_no }}</div></div>
                <div class="f" style="width:50%"><span class="lbl">Date Issued</span><div class="val">{{ optional($application->inspector_ptr_date_issued)->format('m/d/Y') }}</div></div>
            </div>
            <div class="field-row">
                <div class="f" style="width:50%"><span class="lbl">Issued at</span><div class="val">{{ $application->inspector_ptr_issued_at }}</div></div>
                <div class="f" style="width:50%"><span class="lbl">TIN</span><div class="val">{{ $application->inspector_tin }}</div></div>
            </div>
        </div>
    </div>
</div>

<div class="box-half">
    <div class="col">
        <div class="box">
            <div class="box-title">BOX 4 — APPLICANT</div>
            <div class="sig-block" style="margin-top:4px;">
                <span class="sig-line">&nbsp;</span>
                <div class="sig-label">{{ strtoupper($ownerName) }}<br>(Signature Over Printed Name)<br>Date: ______________</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="box">
            <div class="box-title">WITH MY CONSENT: LOT OWNER</div>
            <div class="sig-block" style="margin-top:4px;">
                <span class="sig-line">&nbsp;</span>
                <div class="sig-label">{{ strtoupper($application->owner_name ?? '') }}<br>(Signature Over Printed Name)<br>Date: ______________</div>
            </div>
            <div class="field-row" style="margin-top:4px;">
                <div class="f" style="width:34%"><span class="lbl">C.T.C. No.</span><div class="val">{{ $application->owner_ctc_no }}</div></div>
                <div class="f" style="width:33%"><span class="lbl">Date Issued</span><div class="val">{{ optional($application->owner_ctc_date_issued)->format('m/d/Y') }}</div></div>
                <div class="f" style="width:33%"><span class="lbl">Issued At</span><div class="val">{{ $application->owner_ctc_issued_at }}</div></div>
            </div>
        </div>
    </div>
</div>

<div class="box">
    <div class="box-title">BOX 5 — ACKNOWLEDGMENT</div>
    <div class="notary-text">
        REPUBLIC OF THE PHILIPPINES ) S.S.<br>
        CITY/MUNICIPALITY OF <span class="notary-fill">&nbsp;</span> )<br>
        BEFORE ME, at the City/Municipality of <span class="notary-fill">&nbsp;</span>, on <span class="notary-fill">&nbsp;</span> personally appeared the following persons known to me to be the same persons who executed this standard prescribed form and acknowledged to me that the same is their free and voluntary act and deed.
        <br>
        WITNESS MY HAND AND SEAL on the date and place above written.
        <br>
        Doc. No. ______ &nbsp; Page No. ______ &nbsp; Book No. ______ &nbsp; Series No. ______
        <div style="text-align:right;margin-top:6px;">NOTARY PUBLIC (Until December ______)</div>
    </div>
</div>

{{-- ══════════════════════ PAGE 2 : BOXES 6–8 ══════════════════════ --}}
<div class="page-break"></div>

<div class="box">
    <div class="box-title">BOX 6 (TO BE ACCOMPLISHED BY THE DESIGN PROFESSIONAL)</div>
    <div class="field-row">
        <div class="f" style="width:50%"><span class="lbl">Measurements — Length in Meters</span><div class="val">&nbsp;</div></div>
        <div class="f" style="width:50%"><span class="lbl">Height in Meters</span><div class="val">&nbsp;</div></div>
    </div>
    <div class="checkbox-row" style="margin-top:6px;">
        <span class="lbl" style="display:block;margin-bottom:2px;">TYPE OF FENCING</span>
        <span class="opt"><span class="box-mark"></span>Indigenous Materials</span>
        <span class="opt"><span class="box-mark"></span>R.C. (Reinforced Concrete)</span>
        <span class="opt"><span class="box-mark"></span>R.C. and Conc. Hollow Blocks</span><br>
        <span class="opt"><span class="box-mark"></span>R.C. and Bricks</span>
        <span class="opt"><span class="box-mark"></span>R.C. and Interlink/Cyclone Wire</span>
        <span class="opt"><span class="box-mark"></span>R.C. Steel Matting</span>
        <span class="opt"><span class="box-mark"></span>R.C. Barbed Wire</span>
        <span class="opt"><span class="box-mark"></span>Others (Specify) ______________</span>
    </div>
</div>

<div class="box">
    <div class="box-title">BOX 7 (TO BE ACCOMPLISHED BY THE PROCESSING AND EVALUATION DIVISION)</div>
    <div style="font-size:9.5px;font-weight:bold;text-align:center;margin-bottom:2px;">PROGRESS FLOW</div>
    <table class="progress">
        <tr>
            <th rowspan="2" style="width:22%">&nbsp;</th>
            <th colspan="2">IN</th>
            <th colspan="2">OUT</th>
            <th rowspan="2" style="width:22%">PROCESSED BY</th>
        </tr>
        <tr>
            <th>Date</th><th>Time</th><th>Date</th><th>Time</th>
        </tr>
        <tr><td class="label">Line and Grade (Geodetic)</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td class="label">Civil/Structural</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td class="label">Electrical</td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td class="label">Others (Specify)</td><td></td><td></td><td></td><td></td><td></td></tr>
    </table>

    <div style="font-size:9.5px;font-weight:bold;text-align:center;margin:8px 0 2px;">ASSESSED FEES</div>
    <table class="fees">
        <tr>
            <th style="width:34%"></th>
            <th>Amount Due</th>
            <th>O.R. Number</th>
            <th>Date Paid</th>
            <th>Processed By</th>
        </tr>
        <tr>
            <td class="label">Line and Grade (Geodetic)</td><td></td><td></td><td></td><td></td>
        </tr>
        <tr>
            <td class="label">Fencing</td>
            <td class="amt">{{ $fencingFeeTotal > 0 ? number_format($fencingFeeTotal, 2) : '' }}</td>
            <td>{{ $collection->or_number ?? '' }}</td>
            <td>{{ $collection?->or_date ? \Carbon\Carbon::parse($collection->or_date)->format('m/d/Y') : '' }}</td>
            <td></td>
        </tr>
        <tr>
            <td class="label">Electrical (If any)</td><td></td><td></td><td></td><td></td>
        </tr>
        <tr>
            <td class="label">Others (Specify)</td><td></td><td></td><td></td><td></td>
        </tr>
        <tr class="total">
            <td class="label">TOTAL</td>
            <td class="amt">{{ $collection ? number_format($collection->amount_due, 2) : '' }}</td>
            <td colspan="3"></td>
        </tr>
    </table>
</div>

<div class="box">
    <div class="box-title">BOX 8 (TO BE ACCOMPLISHED BY THE BUILDING OFFICIAL)</div>
    <div style="font-size:9.5px;font-weight:bold;margin-top:2px;">ACTION TAKEN:</div>
    <div style="font-size:9.5px;font-weight:bold;margin-top:4px;">PERMIT IS HEREBY ISSUED/GRANTED SUBJECT TO THE FOLLOWING CONDITIONS:</div>
    <div class="conditions">
        TEST STUB
    </div>

    <div style="font-size:9.5px;font-weight:bold;margin-top:6px;">PERMIT ISSUED BY:</div>
    <div class="sig-block" style="margin-top:10px;">
        <span class="sig-line">&nbsp;</span>
        <div class="sig-label">
            <strong>{{ strtoupper(trim(($permit->building_official_title ?? '') . ' ' . ($permit->building_official_name ?? ''))) }}</strong><br>
            BUILDING OFFICIAL<br>
            {{ $permit->building_official_designation ?? '' }}<br>
            (Signature Over Printed Name)<br>
            Date: {{ $permit->issued_date ? \Carbon\Carbon::parse($permit->issued_date)->format('m/d/Y') : '' }}
        </div>
    </div>
</div>

<div class="footer-note">
    This is a computer-generated document. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}
</div>

</body>
</html>
