# Workflows

---

## Building Permit (BP) Workflow

### State Transitions

```
 ┌─────────┐     submit      ┌────────────────────────┐   finalize    ┌─────────────────┐
 │  draft  │ ──────────────→ │ for_zoning_assessment  │ ───────────→ │ zoning_assessed │
 └─────────┘                 └────────────────────────┘  (planning)  └─────────────────┘
       │ (skip LC)                                                          │
       └──────────→ ┌───────────┐                                           │ finalize
                    │ submitted │                                            │ (engineering)
                    └───────────┘                                            │
                         │               ┌──────────────────────┐           │
                         └──────────────→│ engineering_assessed │ ←─────────┘
                                         └──────────────────────┘
                                                   │ generate billing
                                              ┌────────┐
                                              │ billed │
                                              └────────┘
                                                   │ record payment
                                               ┌──────┐
                                               │ paid │
                                               └──────┘
                                                   │ generate permit
                                        ┌───────────────────┐
                                        │ permit_generated  │
                                        └───────────────────┘
                                                   │ manual release
                                            ┌──────────┐
                                            │ released │
                                            └──────────┘

  Any state ──→ [cancelled] (with reason)
```

The Cancel button on the BP/OP application Show pages is hidden once status reaches `paid`, `released`, `permit_generated`, or `cancelled` — a generated permit must be revoked through the Permits workflow instead of cancelling the application outright.

### Step Details

| Step | Status | Actor | Controller | Action |
|------|--------|-------|-----------|--------|
| 1 | draft | Engineering Staff | ApplicationController::store | Create BP |
| 2a | for_zoning_assessment | Engineering Staff | ApplicationController::submit | Route to Planning |
| 2b | submitted | Engineering Staff | ApplicationController::submit | Skip LC → Engineering |
| 3 | zoning_assessed | Planning Staff | ZoningController::finalize | Complete zoning + fees |
| 4 | engineering_assessed | Engineering Officer | AssessmentController::finalize | Finalize fee assessment |
| 5 | billed | (automatic) | BillingService::generateFor | Billing auto-generated on finalize |
| 6 | paid | Treasury Staff | CollectionController::store | Record payment (OR) |
| 7 | permit_generated | Engineering Officer | PermitController::generate | Generate permit PDF |
| 8 | released | Engineering Officer | Manual | Release to applicant |

### Skip Locational Clearance
When `applies_to = "SKIP_LC"`: `draft → submitted` (bypasses planning).
Without skip LC: `draft → for_zoning_assessment → zoning_assessed`.

### Zoning Fee Auto-Compute
1. Look up `land_use_and_zoning_fees` by occupancy sub-group + total estimated cost
2. Compute: `amount + ((totalCost - excess_of) × percentage)` per sub-group
3. Add `certification_zoning_fees` flat fee (P500)
4. Create assessment items (assessment_type = 'zoning')

---

## Occupancy Permit (OP) Workflow

```
draft → submitted → engineering_assessed → billed → paid → permit_generated → released
```

OP skips `zoning_assessed` entirely. Parallel `*Op()` methods in Assessment/Billing/Collection/Permit controllers.

---

## Demolition Permit (DP) Workflow

```
draft → submitted → engineering_assessed → billed → paid → permit_generated → released
```

Same shape as OP — no zoning stage. Parallel `*Dp()` methods in Assessment/Billing/Collection/Permit controllers, all delegating to the same generic private methods BP/OP already use. Assessment fees are looked up via the dedicated `DEMO_FEE` category (`addDemolitionItem()` — server-computed `amount = quantity × rate`, quantity labeled by the fee type's Settings-configured `unit_label`).

---

## Signage Permit (SGP) Workflow

```
draft → submitted → engineering_assessed → billed → paid → permit_generated → released
```

Same 5-step shape as DP/OP. Parallel `*Sgp()` methods, same delegation pattern. Assessment fees are **manual entry only** — the `SGP_FEE` category has no seeded `FeeType`/`FeeSchedule` rows, so the tab falls through to the generic "select category, type quantity + unit fee" fallback form. The application-form print route does not exist yet (no scanned official form supplied); the assessment-summary and final-permit-certificate prints are both complete.

---

## Fencing Permit (FP) Workflow

```
draft → submitted → engineering_assessed → billed → paid → permit_generated → released
```

Same 5-step shape as DP/SGP/OP — no zoning stage. `FencingApplicationController` (create/store/show/edit/update/submit/revertSubmission/cancel) plus parallel `*Fp()` methods in `AssessmentController`/`CollectionController`/`PermitController`, all delegating to the same generic private methods BP/OP/DP/SGP already use.

**Application Form** (`fencing-applications/form.blade.php`): Applicant Information, Applicant Address (cascading province/city/barangay, plus an `applicant_ctc_*` triplet), Location of Construction, Scope of Work (single-choice: new_construction/erection/addition/repair/others), Design Professional, Plans and Specifications, Full-Time Inspector or Supervisor (identical field shape to Design Professional, with a "Same as Design Professional" toggle that copies all 8 fields via JS), Consent of Lot Owner. Every field is `required` (client-side HTML attribute + server-side Laravel rule) except `owned_by_enterprise` itself (an optional checkbox) and its two dependents `enterprise_name`/`form_of_ownership_id`, which are `required_if:owned_by_enterprise,1` — required only when the enterprise checkbox is checked, enforced both by an Alpine `:required="ownedByEnterprise"` binding on the inputs and by the matching Laravel rule in `FencingApplicationController::validateApplication()`. `scope_of_work_detail` is similarly `required_if:scope_of_work,repair,others`, matching its existing show/hide behavior.

**Application Print** (`FencingApplicationController::printForm()`, route `fencing-applications.print`): a background-image-overlay PDF (`pdf/fencing-application-form.blade.php`) over the two official NBC Form B-03 scans, `public/images/forms/fencing-p1.jpg`/`fencing-p2.jpg` — same technique as `DemolitionApplicationController::printForm()`. The backgrounds are JPEGs (flattened from the original PNG scans) specifically for DomPDF render speed: DomPDF's PNG-embedding path is a known slow path in this codebase (see `docs/TASK.md`'s PDF Print Performance Fix note), and the original PNG-backed version of this template took ~10s to render before being converted, in line with every other discipline form's already-established PNG→JPEG conversion. The original PNGs remain on disk, unreferenced, as calibration sources. The template's header carries the official seal, the national government logo, and centered "Republic of the Philippines / [City] / Province of [Province]" text, matching `demolition-application-form.blade.php`'s letterhead pattern.

**Assessment** (`AssessmentController::assessFp()` / `addFenceItem()`, `assess.blade.php`'s `FP_FEE` tab): fee items are added via a dedicated "Add Fencing Fee Item" form with a grouped `<select>` offering 3 optgroups — Line & Grade (`ASS_LINE_GRADE`), Ground Preparation & Excavation (`ASS_GP_INSPECT`, `ASS_GP_EXCAV`, `ASS_GP_ISSUANCE`, `ASS_GP_FOUND`, `ASS_GP_OTHER`, `ASS_GP_ENCROACH`), and Fencing (`ASS_FENCE_MASONRY`, `ASS_FENCE_INDIG`) — all 9 codes reuse existing rate schedules already seeded under the Building Permit's `ACC_FEE` Accessory category; no separate rate configuration exists for FP. Supports all 3 computation methods (`range_based`, `per_unit`, `fixed`). A legacy `addItemFp()` generic-fallback method also exists on the route table but is unused now that `addFenceItem()` covers the only `FP_FEE` fee types.

**Print Assessment**: `pdf/assessment-summary-fp.blade.php`, titled "FENCING PERMIT ASSESSMENT", route `assessments.print.fp`.

**Collection of Payment**: standard generic collection flow (`CollectionController::createFp`/`storeFp`, thin wrappers around the generic `doCreate`/`doStore`), route prefix `collections/fp/*`.

**Release Permit**: `PermitController::generateFp()` produces a `Permit` row with number format `FP-YYYY-MM-NNNNN`, prints `pdf/fencing-permit.blade.php` — a 2-page reproduction of NBC Form B-03. Page 1 = Boxes 1-5 (Owner/Applicant info + Scope of Work checkboxes, Design Professional + Full-Time Inspector shown side by side, Applicant + Lot Owner Consent signature blocks, blank Notarization). Page 2 = Boxes 6-8 (blank Measurements/Type-of-Fencing filled by the Design Professional rather than the system, blank Progress-Flow tracking half, auto-filled Assessed-Fees half summing all active `FP_FEE` assessment items with OR number/date paid from the Collection record, Building-Official-signed Action-Taken conditions text).

No online self-service application for FP — `OnlineApplicationController` explicitly excludes `DP`/`SGP`/`FP` from the client-facing permit type list and from `store()` (`abort(403, ...)`), same as DP and SGP: walk-in / staff-entered only for now.

**Sidebar navigation**: FP's own Applications section sits between Occupancy Permit and Demolition Permit in the main collapsible nav. In the Assessment and Permits flyout submenus, however, Fencing Permit is listed last (after Demolition and Signage), not between Occupancy and Demolition.

---

## Annual Inspection (AI) Workflow

```
draft → submitted → engineering_assessed → billed → paid → permit_generated → released
```

Same 5-step shape as DP/SGP/FP — no zoning stage. `AnnualInspectionApplicationController` (create/store/show/edit/update/submit/revertSubmission/cancel) plus parallel `*Ai()` methods in `AssessmentController`/`CollectionController`/`PermitController`; Assessment/Billing/Collection delegate to the shared generic private methods same as every other type, but **Permit Generation is AI-specific** — it produces multiple certificates per application rather than one (see "Release Permit" below). This module originally shipped as "Mechanical Permit" (`MP`) with a 5-equipment-tab assessment and multi-permit generation; it was rebuilt around the official Annual Inspection Fees rate schedule and renamed to `AI`, briefly switched to single-permit generation, then switched back to a (differently-grouped) multi-certificate scheme — see `docs/PROJECT_CONTEXT.md` for the full history.

**Application Form** (`annual-inspection-applications/form.blade.php`): Name of Owner/Lessee, Location (Street + Barangay FK), a New/Yearly `application_kind` toggle (editable only while `draft`), a **Character of Occupancy** field (single-select radio over the same `OccupancyGroup`/`OccupancySubGroup` reference data BP/OP use — required, saved as one row via the generic `applicationOccupancyGroups()` relation; unlike BP/OP this is single-select, not a multi-select checkbox grid), and an optional **"Equipment / Items to be Inspected"** checklist — a repeatable list of declared equipment (Elevators, Escalators/Funiculars/Cable Cars, Air Conditioning & Refrigeration, Other Machinery, Electronics Equipment; each row = fee code + Quantity + optional Specification). This is a **declared reference only** — "the basis of the assessment" — shown back on the show page and as a read-only panel on the Assessment page; it does not auto-create any `AssessmentItem` rows. The equipment section itself is optional (zero rows allowed), but Equipment and Quantity are required for any row that is added.

**Assessment** (`AssessmentController::assessAi()`, `assess.blade.php`): exactly 4 tabs, all sourced from the official schedule — **General, Occupancy & Electrical** (`AINSP_GEN`, `addAnnualInspectionFeeItem()`), **Electronics** (`AINSP_ELECTRONICS`, same method), **Mechanical** (`AINSP_MECH`, same method), **Electrical** (`AINSP_ELEC`, `addAnnualInspectionElectricalItem()`, reuses the BP `ELEC_*` fee schedule). All fee computation happens server-side against seeded `FeeType`/`FeeSchedule` rows (fixed/per_unit/range_based, same computation methods as everywhere else) — no manual entry.

**Quantity (equipment count) multiplier**: 15 Mechanical + 3 Electrical fee codes priced by a continuous physical measurement (kW, ton(s), kVA, lineal meter(s), cu.m.) get a second "Quantity" form field, separate from the "Unit" measurement field — `amount = baseFee(measurement) × quantity_count`. Codes already priced as a discrete count (unit(s), head(s), outlet(s), pole(s), attachment(s)) don't get this field; their existing single input still functions as the count. The assessment items table for these 4 tabs shows this split as separate **Unit** and **Qty** columns: quantity-eligible rows show "50.00 kW" / "3"; discrete-count rows show just the unit label ("unit(s)") in the Unit column and the real count in Qty.

**Print Assessment**: `pdf/assessment-summary-ai.blade.php`, route `assessments.print.ai`. Sections are grouped by the 4 real `AINSP_*` categories (a bug once had this template still grouping by the module's original, since-deleted equipment categories, showing ₱0.00 everywhere — fixed to match the live schema).

**Collection of Payment**: standard generic collection flow (`CollectionController::createAi`/`storeAi`), route prefix `collections/ai/*`.

**Release Permit — multi-certificate generation**: `PermitController::generateAi()` → `doGenerateAi()` produces **up to 6 separate `Permit` certificates per application** (not one), grouped by inspection discipline:
1. **1 certificate** — General, Occupancy & Electrical (bundles `AINSP_GEN` + `AINSP_ELEC` items)
2. **1 certificate** — Electronics (`AINSP_ELECTRONICS` items)
3. **1 certificate** — Machinery (`AINSP_MECH` items, excluding Elevators/Escalators/Aircon-Refrigeration)
4. **1 certificate per unit** — one for each individual Elevator item
5. **1 certificate per unit** — one for each individual Escalator/Funicular/Cable Car item
6. **1 certificate** — Air Conditioning & Refrigeration (bundles ALL such items regardless of count)

A certificate is only generated for a group with at least one assessed item — no empty certificates. Each certificate gets its own `AI-YYYY-MM-NNNNN` number (one shared counter, incremented per certificate in the same generation action) and links back to its slice of assessment data via the reactivated `AnnualInspectionPermitUnit` bridge table. `revertGenerateAi()`/`restoreRevokeAi()` act on **all** of an application's certificates at once (revoke-all / restore-all), not one at a time. `pdf/annual-inspection-permit.blade.php` renders one certificate per print for every group **except `GE`** — bundle-type certificates (ELN/MACH/ACREF) show an itemized table of their items, per-unit certificates (Elevator/Escalator) show a single equipment description/quantity line. **The `GE` certificate** ("General, Occupancy & Electrical") instead renders `pdf/annual-inspection-permit-ge.blade.php`, a dedicated background-image-overlay template reproducing the official **NBC Form No. B-19 "Certificate of Annual Inspection"** as a single A4-landscape page (auto-filled Owner/Location/Character-of-Occupancy, the 12 discipline signatories + 2 Chief signature blocks from the seeded `ai_*` Signatory roles, No./Fee Paid/OR/Date Paid/Date Issued, and a Republic/Province/City letterhead) — see `docs/PROJECT_CONTEXT.md` for the full build/calibration history. The application's `show` page lists all generated certificates in a "Generated Permits (N)" panel with individual print links; `/permits/annual-inspection` shows "N permit(s) generated"/"View Permits" instead of a single permit number/direct print link. (This module briefly used single-permit-per-application generation via the shared `doGenerate()` — see `docs/PROJECT_CONTEXT.md` — before being switched to the multi-certificate scheme described here.)

No online self-service application for AI — excluded from `OnlineApplicationController` same as DP/SGP/FP.

**Sidebar navigation**: Annual Inspection sits last (after Fencing Permit) in all 3 locations (main nav, Assessment flyout, Permits flyout).

---

## State Machine

### ApplicationStatus Enum (`app/Enums/ApplicationStatus.php`)

| From | To (allowed) |
|------|-------------|
| draft | submitted, for_zoning_assessment, cancelled |
| submitted | engineering_assessed, cancelled |
| for_zoning_assessment | zoning_assessed, cancelled |
| zoning_assessed | engineering_assessed, cancelled |
| engineering_assessed | billed, cancelled |
| billed | paid, cancelled |
| paid | permit_generated, cancelled |
| permit_generated | released, cancelled |
| released / cancelled | (terminal) |

---

## Fee Computation Flow

### Computation Methods

| Method | Description | Example |
|--------|-------------|---------|
| `fixed` | Flat fee × quantity | ₱5,000/elevator × 2 = ₱10,000 |
| `per_unit` | Rate × quantity | ₱40/ton × 80 = ₱3,200 |
| `range_based` | Lookup fee by range band | Floor area 101–200 → ₱500 flat |
| `cumulative_range` | Tiered: first N at rate A, excess at rate B | Elevators: 5@₱500 + excess@₱50 |
| `percentage` | % of base amount | 10% of electrical fee |
| `formula` | Custom formula (stored as text) | |

### Assessment Item Creation Flow

#### Construction Tab
```
Select Part of Building + Division (filtered by occupancy groups) + Area
→ FeeType lookup by CONST_{division.code}
→ FeeSchedule by area range
→ amount = area × fee_per_unit
```

#### Electrical Tab
```
Select fee type (TCL / Transformer / UPS / Pole / Guying / Meter / Wiring)
→ kVA types: amount = fixed_fee + (kva × fee_per_unit)   [range lookup]
→ fixed types: amount = fixed_fee
→ inspection_fee = 0 (always — inspection fees were removed from the BP assessment entirely, see below)
```

#### Mechanical Tab
```
Select mechanical equipment type (MECH_*) + unit count
→ Base fee: FeeSchedule lookup on MECH fee schedules
  · per_unit:   amount = quantity × fee_per_unit
  · fixed:      amount = quantity × fixed_fee
  · range_based: lookup range → flat fixed_fee or fee_per_unit × qty (with optional excess)
→ inspection_fee = 0 (always — see below)
```

> **BP inspection fees removed (Electrical & Mechanical):** the BP assessment's ELEC/MECH tabs used to compute an inspection fee on top of the base permit fee (ELEC: a settings-configurable percentage; MECH: via `resolveInspectionFee()` against the `MECH_INSP`/`INSP_*` schedules) — both now always store `inspection_fee = 0`, and the "Inspection" table columns / Summary row / PDF line items were removed for BP. `resolveInspectionFee()` itself is untouched — the Annual Inspection assessment's Mechanical/Electrical tabs still use the same NBC-inspection-fee computation mechanism for their own (differently-purposed) fees. Existing affected BP applications were retroactively corrected via a one-off `php artisan bp:remove-inspection-fees` command, cascading the reduction through `Assessment`/`Billing`/`Collection` records where safe to do so automatically.

#### Plumbing Tab
```
Select plumbing fee (22 PLUMB_* types, grouped) + unit (dynamic label: fixtures/mm/cu.m)
→ per_unit:    amount = unit × fee_per_unit
→ range_based: lookup range → fixed_fee (+ excess above threshold) or fee_per_unit × unit
```

#### Electronics / Accessories / Accessory Fees / Surcharge Tabs
```
Select fee type + unit → schedule lookup → amount per computation method
(dedicated add methods: addElectronicsItem, addAccessoryItem, addAccFeeItem, addSurchargeItem)
```

#### Occupancy Fee Tab (OP assessment)
```
Select OCC_* fee type + unit (dynamic label: Costing ₱ / Area sq.m / Amount ₱ / Meters-Units)
→ range_based: lookup cost/area range → fixed_fee
  · with excess: fixed_fee + ceil((unit − excess_threshold) / excess_every) × excess_fee
    (e.g. DIV_A ₱2M → ₱800 + ceil(800k/1M)×₱800 = ₱1,600)
→ per_unit:   amount = unit × fee_per_unit          (e.g. CHANGE_USE: sq.m × ₱5)
→ percentage: amount = unit × schedule.percentage   (e.g. J-II RATE: principal fee × 50%)
```

#### Generic Tabs (fallback)
```
Select fee type + enter quantity + unit fee
→ amount = quantity × unit_fee
```

### Grand Total Formula (all categories)
```
Assessment total = sum(assessment_items.amount)
                 + sum(assessment_items.inspection_fee)
                 + filing_fee
                 + processing_fee
```

### Assessment Finalization Locking

Finalize requires password confirmation (`Hash::check()`), then redirects to the Summary tab (`?tab=SUMMARY`).

Finalize also **auto-generates the billing**: `AssessmentController::doFinalize()` calls `BillingService::generateFor()`, which creates the `billings` + `billing_items` records from all finalized assessments and moves the application straight to `billed`. There is no manual billing step or Billing menu — treasury proceeds directly to Collections.

Once an assessment status = `finalized`:
- **BP/OP assessment** — all add-item forms and Remove buttons are hidden; every add/remove endpoint calls `redirectIfFinalized()` which bounces to the Summary tab with an error
- **Zoning assessment** — autocompute, add, remove (single + bulk), and Save Details are hidden; `ZoningController::abortIfZoningFinalized()` returns 403 on any mutating request
- A single amber banner "This assessment has been finalized. No further changes can be made." is displayed

The assessment index tables (`/assessments` and `/assessments/occupancy`) list applications with status `submitted`, `zoning_assessed`, `engineering_assessed`, **and `billed`** (the `billed` status was added once finalize started auto-billing — otherwise finalized applications would disappear from the list). The Print button shows for status `engineering_assessed` **or** `billed`.

---

## Application List Views (`/applications`, `/occupancy-applications`)

- **Year filter** — `?year=`, defaults to current year; dropdown offers current + previous year. `ApplicationController::index()` / `OccupancyApplicationController::index()` apply `whereYear('created_at', $year)`.
- **Turn Around Time column** — per application, days from `submitted_at` (falls back to `created_at`) to the latest generated `Permit`'s `created_at`; shows `–` if no permit has been generated yet. Computed in the Blade view from the eager-loaded `permits()` relation, not stored.
- **OP status labels** — OP has no zoning/planning stage, so `zoning_assessed` displays as "For Occupancy Assessment" (instead of the generic "Zoning assessed") on both the OP applications index and the OP assessment index.
- **OP columns** — App No., Type, Applicant, Project Title, Status, Date, Turn Around Time, Actions.

---

## Print Forms Dropdown (BP Show Page)

`applications/{id}` shows a single right-aligned "Print Forms" dropdown (Alpine) instead of separate buttons: 1. Application Form (`applications.print`), 2–7. Architectural/Structural/Electrical/Sanitary/Mechanical/Electronics (`applications.print.discipline`, one route per discipline key). All 6 disciplines render a real background-image-overlay PDF (NBC Form A-01/A-07/77-001-S/77-001-S/A-04/A-07 respectively) — the print-forms set is complete. Every application/permit PDF (this list plus Building Permit and Occupancy Permit) also prints a small "computer-generated document" footer with the print date and the logged-in user's name on every page.

---

## Revert / Send-Back Actions

Every forward step below has a corresponding backward action, each requiring password confirmation (`Hash::check()`) and writing an `activity()` log entry. All deletions are soft-deletes.

| Backward Action | Method | Permission | Effect |
|-----------------|--------|-------------|--------|
| Revert submission → draft | `ApplicationController::revertSubmission()` / `OccupancyApplicationController::revertSubmission()` | `revert-submission` | Application's Show page; status back to `draft` |
| Send back to Engineering (from Planning) | `ZoningController::sendBackForEditing()` | `revert-submission` | BP zoning screen; sends application from Planning back to Engineering for edits |
| Revert zoning finalize | `ZoningController::revertZoning()` | `revert-zoning` | Un-finalizes a zoning assessment |
| Return to Zoning (from Engineering) | `AssessmentController::returnToZoning()` | `return-to-zoning` | BP assessment screen; deletes the BP engineering assessment items, sends application back to Planning |
| Revert engineering finalize | `AssessmentController::revertEngineering()` / `revertEngineeringOp()` / `revertEngineeringDp()` / `revertEngineeringSgp()` / `revertEngineeringFp()` / `revertEngineeringAi()` | `revert-assessments` | Un-finalizes an engineering assessment; DP/SGP/FP/AI revert to `submitted` (no zoning stage) instead of `zoning_assessed` |
| Revert OP assessment to draft | `AssessmentController::revertToDraftOp()` | `revert-submission` | OP-only; only while `status = zoning_assessed` (not yet finalized); deletes all occupancy fee entries and the occupancy Assessment, sets status back to `draft` |
| Revert DP/SGP/FP/AI assessment to draft | `AssessmentController::revertToDraftDp()` / `revertToDraftSgp()` / `revertToDraftFp()` / `revertToDraftAi()` | `revert-submission` | Same shape as `revertToDraftOp()`, but the pre-assessment status is `submitted` (no zoning stage) rather than `zoning_assessed` |
| Revoke generated permit | `PermitController::revertGenerate()` / `revertGenerateOp()` / `revertGenerateDp()` / `revertGenerateSgp()` / `revertGenerateFp()` | `revert-permits` | Tags the `Permit` as `status = 'revoked'` (with a required reason) and soft-deletes it — the permit number is retained, never reused; rolls application status back to `paid`. `doGenerate()` refuses to create a new permit for an application with a revoked permit on file. |
| Restore revoked permit | `PermitController::restoreRevoke()` / `restoreRevokeOp()` / `restoreRevokeDp()` / `restoreRevokeSgp()` / `restoreRevokeFp()` | `revert-permits` | Un-trashes the same `Permit` row, sets `status` back to `generated`, application back to `permit_generated`. Password-confirm only (no reason). |
| Revoke all AI certificates | `PermitController::revertGenerateAi()` | `revert-permits` | AI-specific: revokes + soft-deletes **all** of the application's generated certificates in one action (not just one), required reason; rolls application back to `paid`. |
| Restore all AI certificates | `PermitController::restoreRevokeAi()` | `revert-permits` | AI-specific: restores **all** trashed-revoked certificates in one action, application back to `permit_generated`. Password-confirm only. |

All "Return to Zoning" / "Revert to Draft" buttons live in the page **header** of `assessments/assess.blade.php`, not inside the Summary tab's content — a tab-gated location would leave them invisible on default page load (the assess screen lands on the first fee-entry tab, not Summary).

---

## Permits List (`/permits/building`, `/permits/occupancy`, `/permits/demolition`, `/permits/signage`, `/permits/fencing`, `/permits/annual-inspection`)

`PermitController::buildingIndex()` / `occupancyIndex()` / `demolitionIndex()` / `signageIndex()` / `fencingIndex()` / `annualInspectionIndex()` list applications at `paid`, `permit_generated`, or `released`, sharing the single `permits/index.blade.php` view keyed by a `$type` variable (`building`/`occupancy`/`demolition`/`signage`/`fencing`/`mechanical` — the AI type's internal `$type` value was kept as `mechanical` from the pre-rename build, not renamed), with:
- **Filters** — Search (app number/applicant/project title), Status (Paid, Permit generated, Released, **Revoked** — matched as `status = 'paid'` + a trashed permit tagged `revoked`), Year (defaults to current year).
- **Permit No.** as the primary (first) column — links to the correct application Show route per type (`applications.show`/`occupancy-applications.show`/`demolition-applications.show`/`signage-applications.show`), red-strikethrough for a revoked permit's number, `-` if never generated.
- **`mechanical` (AI) type is multi-permit-aware**: since one application can produce up to 6 certificates, this column instead shows "N permit(s) generated"/"N permit(s) revoked" (linking to the application's `show` page, which lists each certificate individually), and the Actions cell shows "View Permits" instead of a direct Print link; Revoke/Restore confirmation copy is pluralized ("delete/restore all N ... permit(s)").
- **Project Title column** — hidden for `demolition` and `signage` (neither application table has a `project_title` field).
- **Print button** — hidden for `demolition` only (the application-form print is a manual/physical process for DP); shown for `signage` (its final-permit-certificate print is complete, only the upstream application-form print is deferred).
- **TTA column** beside Date, same day-count logic as the application indexes.
- Actions: **Generate** (no permit yet), **Print** / **Revoke** (active permit), **Restore** (revoked permit — replaces Generate entirely, since a new permit cannot be created while a revoked one exists on file).

---

## Collections / Payment

### Barcode Scan & Search
`/collections` has a search box (auto-focused) for the collector to scan the barcode on a printed assessment or type an application number / applicant name:
- **Exact match** on a billed application's `application_number` → redirects straight to that application's payment form (`collections.create` / `collections.create.op`)
- **Partial match** → filters the "Awaiting Payment" list by application number or applicant first/last name
- No match → amber "No application awaiting payment matches …" notice

Both the exact-match redirect and the "Awaiting Payment" list query add `whereDoesntHave('collections', fn($q) => $q->where('status', 'active'))` on top of `status = 'billed'` — a defensive guard so an application never reappears in the payment queue once it already has an active (paid) collection, even if its `status` column didn't transition cleanly to `paid`.

### Cash Change
On the payment form, when Payment Mode = Cash, a live Alpine-computed box shows the **Change** (green) as the collector types Amount Received, or **Short** (red, with a warning) if the amount is insufficient. `CollectionController::doStore()` rejects an insufficient cash payment server-side ("Amount received is less than the amount due"). The `collections.change_amount` column (`max(0, amount_received - amount_due)`) is unchanged — only the live display and the guard are new.

The payment form (`collections/create.blade.php`) is a compact, single-screen POS-style layout: Application No./Applicant, OR Number/Paid By, and a three-column Amount Due / Amount Received / Change strip, followed by a segmented Cash/Check/Online control and a sticky action bar — designed so the collector doesn't need to scroll while processing a payment.

### My Collections (Payment History)
The `/collections` Payment History table is scoped to the **logged-in collector only** (`collected_by = Auth::id()`) and filtered by month (`?month=YYYY-MM`, defaults to the current month) via an auto-submitting month picker in the table header. The "Void Collection" header button was removed from this page (the `/collections/void` route still exists).

---

## Role-Based Access Per Step

| Workflow Step | Required Permission | Typical Role |
|--------------|-------------------|--------------|
| Create application | `create-applications` | engineering-staff |
| Submit application | `submit-applications` | engineering-staff |
| Zoning assessment | `create-zoning`, `finalize-zoning` | planning-staff/officer |
| Skip locational | `skip-zoning` | planning-officer |
| Engineering assessment | `create-assessments`, `finalize-assessments` | engineering-staff/officer |
| Generate billing | `generate-billing` | engineering-officer |
| Record payment | `create-collections` | treasury-staff |
| Void payment | `void-collections` | treasury-officer |
| Generate permit | `generate-permits` | engineering-officer |
| Print permit | `print-permits` | engineering-staff |
| Release permit | `release-permits` | engineering-officer |
| Revert submission / send back | `revert-submission` | engineering-officer, planning-officer |
| Revert zoning finalize | `revert-zoning` | planning-officer |
| Revert engineering finalize / return to zoning | `revert-assessments`, `return-to-zoning` | engineering-officer |
| Revoke generated permit | `revert-permits` | engineering-officer |
| Restore revoked permit | `revert-permits` | engineering-officer |
| View audit logs | `view-audit-logs` | super-admin only |

---

## Online Application Flow (Client Portal)

```
/register → /login → /online/apply (status = submitted)
→ upload requirements → track status → download permit (status = released)
```

---

## Document Generation

### PDF Templates (`resources/views/pdf/`)

| Template | Trigger |
|----------|---------|
| application-form | ApplicationController::printForm (BP only) — DomPDF (`defaultMediaType=print`, `dpi=200`); Unified Application Form for Building Permit reproduced as a background-image overlay (`public/images/forms/unified-bp-form-p{1,2}.png`) with ~84 absolutely-positioned dynamic fields; letterhead is overlaid (seal `general.logo` left, `general.national_govt_logo` right, Republic/city/province from settings centered); Area No. falls back to `general.area_number`; p2 ends with the applicant's name over a SIGNATURE OVER PRINTED NAME OF OWNER/APPLICANT line |
| occupancy-application-form | OccupancyApplicationController::printForm (OP only) — DomPDF A4 portrait; Unified Application Form for Certificate of Occupancy; two-logo header, FULL/PARTIAL from applicationType, static requirements checklist, two-column signatory block (Inspected by: building_official Signatory / Submitted by: applicant + Attested by: full-time inspector with blank PRC table) |
| architectural-form | ApplicationController::printDiscipline (BP only, discipline=architectural) — DomPDF background-image overlay of NBC Form A-01, own scans (`public/images/forms/architectural-p{1,2}.png`); Boxes 1/4/5/6 auto-filled from the Application record, Box 3 left blank for hand-signing, page 2 "Permit Issued By" from the Permit's building-official snapshot |
| structural-form | ApplicationController::printDiscipline (discipline=structural) — NBC Form A-07 Civil/Structural, same overlay technique as Architectural; Box 4 "Supervision/In-Charge" reuses generic `engineer_*` fields |
| electrical-form | ApplicationController::printDiscipline (discipline=electrical) — Form No. 77-001-S; "Summary of Electrical Loads/Capacities" from `total_connected_load`/`total_transformer_capacity`/`total_generator_capacity`; Box 3 reuses generic `engineer_*` fields |
| sanitary-form | ApplicationController::printDiscipline (discipline=sanitary) — Form No. 77-001-S; denser layout with separate ADDRESS/LOCATION OF INSTALLATION rows; Building Official block on page 1 (not page 2, unlike the other 5) |
| mechanical-form | ApplicationController::printDiscipline (discipline=mechanical) — NBC Form A-04, 8.5×14in (legal, unlike the other 5's 8.5×13in); no official source scan, background is a clean digitally-generated reference image; Scope of Work maps all 12 checkboxes against `scope_of_works` |
| electronics-form | ApplicationController::printDiscipline (discipline=electronics) — NBC Form A-07 Electronics, same no-source-scan situation as Mechanical; Scope of Work maps New Installation/Others only |
| discipline-form | ApplicationController::printDiscipline — generic blank-placeholder fallback, DomPDF A4, city seal header; no longer used by any of the 6 disciplines (all now render real forms), kept for any future/unrecognized discipline key |
| demolition-application-form | DemolitionApplicationController::printForm (DP only) — DomPDF background-image overlay of the official 2-page NBC Form No. B-08 scan; letterhead (seal + national govt logo + Republic/City/Province) and a Building Official title/name/designation block on page 2 above the signature line |
| building-permit | PermitController::print (BP) — NBC Form B-018 style, A4 landscape, city seal (left) + DPWH logo (right), QR verification code |
| occupancy-permit | PermitController::print (OP) — DPWH Certificate of Occupancy style, A4 landscape, DPWH logo + city seal, QR verification code |
| demolition-permit | PermitController::print (DP) — bordered-frame landscape A4 certificate style (same technique as building-permit/occupancy-permit), QR verification code |
| signage-permit | PermitController::print (SGP) — same bordered-frame landscape A4 certificate style, cloned from demolition-permit; Scope of Work/Wordings/Premises-Of fields, QR verification code |
| fencing-permit | PermitController::print (FP) — 2-page plain-CSS reproduction of NBC Form B-03, itemized Assessed-Fees table, QR verification code |
| annual-inspection-permit | PermitController::print (AI, groups ELN/MACH/ACREF/ELEV/ESC) — one certificate per print, parameterized by the specific `AnnualInspectionPermitUnit`; bundle-type certificates (Electronics/Machinery/Aircon-Refrigeration) show an itemized fee table, per-unit certificates (Elevator/Escalator) show a single equipment line, QR verification code |
| annual-inspection-permit-ge | PermitController::print (AI, group GE only) — background-image-overlay reproduction of NBC Form No. B-19, single A4-landscape page, auto-filled from application/assessment/permit + the 15 `ai_*` Signatory roles; no QR code (not part of the official form's layout) |
| assessment-summary | AssessmentController::print (BP only) — city seal header; Code 128 barcode above BP number; Approved By = building_official signatory; no Fire Code Fees section; no Electrical/Mechanical/Electronics inspection-fee line items (removed) |
| assessment-summary-op | AssessmentController::printOp (OP only) — city seal header; titled "OCCUPANCY PERMIT ASSESSMENT"; only an Occupancy Fees section (no Zoning/Building/Electrical/Mechanical/Other Fees/Filing/Processing) |
| assessment-summary-dp | AssessmentController::printDp (DP only) — city seal header; titled "DEMOLITION PERMIT ASSESSMENT"; only a Demolition/Moving Fees section |
| assessment-summary-sgp | AssessmentController::printSgp (SGP only) — city seal header; titled "SIGNAGE PERMIT ASSESSMENT"; only a Signage Permit Fees section |
| assessment-summary-fp | AssessmentController::printFp (FP only) — titled "FENCING PERMIT ASSESSMENT"; only a Fencing Permit Fees section |
| assessment-summary-ai | AssessmentController::printAi (AI only) — Annual Inspection Fees sections grouped by the 4 real `AINSP_*` categories (General/Electronics/Mechanical/Electrical) |
| billing-statement | BillingController::print — city seal + city/province from settings |
| official-receipt | CollectionController::receipt — city seal header |
| zoning-certification | PermitController::zoningCertification |
| locational-clearance | PermitController::locationalClearance |
| evaluation-report | PermitController::evaluationReport — city seal + Republic/city/province header |

All seal/logo images above are sourced dynamically from Settings → General (`Setting::general()` + `Setting::imageDataUri()`), so an admin logo change propagates to every printed document. `OnlineApplicationController::doDownloadPermit()` (client-portal download) passes the same seal/logo/QR variables as the staff print path.

---

## Permit QR Code Verification

Every permit generated via `PermitController::doGenerate()` gets a `verification_token` (UUID). When printing (`PermitController::print()`):

```
verifyUrl = {general.domain setting, or config('app.url') if blank} + /verify/permit/{token}
qrImage   = QR PNG encoding verifyUrl (endroid/qr-code), embedded as base64 data URI on both permit templates
```

`GET /verify/permit/{token}` (public, throttled `throttle:30,1`, no auth) → `VerifyController::show()` looks up the `Permit` by token:
- **Found** → `verify/permit.blade.php` shows permit type (Building Permit / Certificate of Occupancy / Demolition Permit / Signage Permit), permit number, date issued, **Issued By** (the snapshotted Building Official — title/name/designation, immutable per permit, see the Building Official Snapshot note in `docs/PROJECT_CONTEXT.md`), status, applicant name, project title, location
- **Not found** → same view renders a "This permit could not be verified" message (still `200 OK` — doesn't leak token validity via status code)

---

## Reports (`/reports/permits`)

`ReportController::generate()` and `PermitReportExport` filter the Permit Report (PDF + Excel) to applications at `permit_generated`, or `paid` with a revoked permit on file (same status semantics as the Permits List) — previously the report included every status with no filter. Both formats include Permit No. and TTA columns and a combined application-date → permit-date Date range. The PDF's peso sign (₱) is rendered with the `DejaVu Sans` font (bundled with DomPDF) instead of the default Helvetica/Arial substitute, which lacks that glyph and rendered it as a box/`?`.

## Audit Logs (`/reports/audit-logs`) — Super-Admin Only

`ReportController::auditLogs()` surfaces Spatie's existing `activity_log` table (already written to by Application, OccupancyApplication, Assessment, Collection, Permit, and User). Gated by the `view-audit-logs` permission, granted **only** to `super-admin` — not `administrator` or any other role — both at the route (`middleware('can:view-audit-logs')`, independent of the `reports.` group's blanket `can:view-reports`) and the sidebar link (`@can('view-audit-logs')`, so it's invisible to every other role even if they have `view-reports`). Filters: free-text search (description), causer (user dropdown), subject type (Application/OccupancyApplication/Assessment/Collection/Permit/User), event (created/updated/deleted), and month (`?month=YYYY-MM`, defaults to current month).

---

## Miscellaneous

- **Unknown-URL fallback** — any unmatched route redirects to the role-appropriate home (or `login` if a guest) instead of a 404 page (`Route::fallback()`).
- **Session-expired redirect** — a CSRF token mismatch (expired session, "Page Expired") redirects to `login` or `staff.login` with a flash message instead of showing Laravel's default 419 page.
- **Password visibility toggles** — client login, staff login, and both password fields on Create User (`settings/user-form.blade.php`) have show/hide toggles, matching the one already on client registration.
- **Printed permit footer** — both permit PDFs show "This is a computer-generated permit. Printed on: {date} | Printed by: {user}" below the legal footer note, computed fresh on every print.

### Notifications

| Notification | Trigger |
|-------------|---------|
| ApplicationSubmittedNotification | Application submitted |
| AssessmentCompleteNotification | Assessment finalized → client |
| PaymentPostedNotification | Payment recorded → client |
| ApplicationApprovedNotification | Permit generated → client |
