# Feature Completeness Status

> Legend: DONE | PARTIAL | PENDING | N/A

---

## Core Infrastructure

| Feature | Status | Notes |
|---------|--------|-------|
| Laravel 12 project setup | DONE | PHP 8.2, MariaDB 12.3 |
| Self-healing boot | DONE | SelfHealingServiceProvider |
| Staff authentication | DONE | /staff/login with role-based redirect |
| Client authentication | DONE | /login, /register |
| RBAC | DONE | 9 roles, 30+ permissions via Spatie |
| Activity logging | DONE | Application, Assessment, Collection, Permit |
| Soft deletes | DONE | All transaction tables |
| Settings management | DONE | Key-value with admin UI |
| Dynamic branding (seal/logos/favicon) | DONE | `Setting::general()`/`imageDataUri()` helpers; seal on all printed documents; `general.favicon` browser-tab icon (falls back to seal); `general.national_govt_logo` on both application forms |
| Browser autofill disabled | DONE | autocomplete="off" on all forms |
| Form validation UX | DONE | Error banner, section highlighting, scroll-to-error |
| Test data seeder | DONE | ApplicationSeeder: 5 BP + 5 OP |
| Revert / send-back actions | DONE | Password-confirmed backward transitions at every workflow step: submission, zoning, engineering assessment, OP-to-draft, permit generation |
| On-demand barangay lookup | DONE | `GeoController::barangaysForCity()` — AJAX fetch replacing full ~42K-row client-side dataset |
| Unknown-URL fallback | DONE | `Route::fallback()` — redirect to role-appropriate home or login instead of 404 |
| Session-expired (419) redirect | DONE | Redirects to login/staff.login with flash message instead of default Page Expired screen |
| Password visibility toggles | DONE | Client login, staff login, registration, staff account creation |
| Staff account password complexity | DONE | Admin-supplied password now enforced/used on Create User (was previously discarded, hardcoded to `password123`) |

---

## Building Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD (walk-in) | DONE | |
| Application CRUD (online) | DONE | Client portal |
| Application numbering | DONE | BP-YYYY-MM-NNNNN |
| Occupancy group selection | DONE | Groups A–J with sub-groups |
| All BP form fields | DONE | Applicant, enterprise, project, building, costs, engineers |
| Application form print | DONE | Now a real DomPDF stream (was browser-print HTML): background-image overlay of the official 2-page Unified Application Form (8.5×13in long bond), ~84 dynamic fields; overlaid letterhead (seal left, National Govt logo right, city/province from Settings); Area No. from `general.area_number`; p2 applicant signature line (Building Official block removed with the new scan); `dpi=200` set explicitly so the background scan isn't downsampled/blurred |
| Print Forms dropdown | DONE | BP Show page: single "Print Forms" dropdown (Alpine) replacing 7 separate buttons — Application Form + 6 discipline forms (Architectural/Structural/Electrical/Sanitary/Mechanical/Electronics) |
| Discipline print routes | DONE | `applications/{id}/print-discipline/{discipline}` — all 6 disciplines (Architectural, Structural, Electrical, Sanitary, Mechanical, Electronics) now render real permit-form PDFs |
| Architectural Permit PDF (NBC Form A-01) | DONE | Background-image overlay (own scans, GD pixel-calibrated); Boxes 1/4/5/6 auto-filled from the Application record + letterhead; Box 3 (Design Professional) left blank for hand-signing; page 2 "Permit Issued By" reads the Permit's building-official snapshot, or the active Signatory if no Permit yet |
| Structural Permit PDF (NBC Form A-07) | DONE | Same overlay technique/conventions as Architectural, own scans; Box 4 "Supervision/In-Charge" reuses the generic `engineer_*` fields (no dedicated structural-engineer columns exist); shares `resolveBuildingOfficial()` with Architectural |
| Electrical Permit PDF (Form No. 77-001-S) | DONE | Same overlay technique, own scans; Box 2 "Design Professional" filled from real `pee_*` (Professional Electrical Engineer) fields + `total_connected_load`/`total_transformer_capacity`/`total_generator_capacity` (KVA summary); Box 3 "Supervisor of Electrical Works" reuses generic `engineer_*` fields; only the "New Installation" scope checkbox maps to existing data (this form's other 7 scope options have no equivalent field) |
| Sanitary/Plumbing Permit PDF (Form No. 77-001-S) | DONE | Same overlay technique, own scans; Box 6 (Design Professional) left blank for hand-signing, Box 7 (In-Charge of Installation) and Box 8 (Applicant) filled from generic `engineer_*`/`applicant_*` fields; also fills `no_of_storeys`, `total_floor_area`, `plumbing_cost`, `proposed_construction_date`/`expected_completion_date` — fields with no printed home on any other discipline form; no "Permit Issued By" signatory block exists on this form |
| Mechanical Permit PDF (NBC Form A-04) | DONE | Same overlay technique; source is a clean digitally-generated reference image (not a scan), 8.5×14in legal-size (unlike the other forms' 8.5×13in); Scope of Work maps all 12 checkbox options against `scope_of_works`; Box 2 (Installation types) and Box 3/4 (PME/Supervisor of Mechanical Works) left blank — no backing columns exist; Box 5/6 (Building/Lot Owner) reuse generic `applicant_*`/`owner_*` fields; page 2 "Permit Issued By" shares `resolveBuildingOfficial()` |
| Electronics Permit PDF (NBC Form A-07) | DONE | Sixth and final discipline form — print-forms set now complete. Same overlay technique, standard 8.5×13in; Scope of Work maps New Installation/Others (Annual Inspection has no equivalent); Box 2 (Nature of Installation checklist) and Box 3/4 (Design Professional/Supervisor) left blank — no backing columns; Box 5/6 reuse generic `applicant_*`/`owner_*` fields with a real gutter margin between columns; page 2 "Permit Issued By" has no signature-line caption on this form |
| "Computer-generated document" footer on all forms | DONE | Extended from Building/Occupancy Permit to all 10 application/permit PDFs (BP/OP applications + all 6 discipline forms); `bottom:` positioning on `.print-page` forms auto-adapts to Mechanical's taller 8.5×14in page; no controller changes needed, `auth()->user()?->full_name` called directly from each view |
| Cancel hidden after permit generation | DONE | Show-page Cancel button excluded for `permit_generated` (in addition to paid/released/cancelled) |
| Status workflow | DONE | 8-state machine |
| Submission notification | DONE | Notifies engineering users |
| FSEC No. / Date Issued fields | DONE | Reference-only fields on the application form, shown on the printed Building Permit |
| Revert submission / return to zoning | DONE | `revertSubmission()`, `sendBackForEditing()`, `returnToZoning()` — password-confirmed |
| Year filter + Turn Around Time column | DONE | `/applications` index: `?year=` filter (default current year), submitted→permit-generated day count |

---

## Occupancy Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD | DONE | Separate controller/model/table |
| OP-specific fields | DONE | BP reference, FSEC, FSIC No., completion date |
| Applies For (Full/Partial) | DONE | Selected via Application Type; drives the FULL/PARTIAL checkbox on the printed certificate |
| Character of Occupancy | DONE | Shared occupancy group selection |
| Status workflow (skips zoning) | DONE | submitted → engineering_assessed |
| Polymorphic downstream | DONE | assessments, billings, collections, permits, documents |
| OP application form print (PDF) | DONE | Dedicated `occupancy-application-form.blade.php` (DomPDF, A4) — fixed crash from reusing the BP overlay view; official Certificate of Occupancy application layout with two-column signatory block |
| Revert submission / revert-to-draft | DONE | `revertSubmission()`; `AssessmentController::revertToDraftOp()` also purges occupancy fee entries |
| OP-appropriate status labels | DONE | `zoning_assessed` shown as "For Occupancy Assessment" (no zoning stage in OP) |
| Year filter + Turn Around Time column | DONE | `/occupancy-applications` index; Project Title column (replaced Applicant Address) |

---

## Demolition Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD (walk-in) | DONE | `demolition_applications` table + `DemolitionApplication` model/controller; morph map `dp` |
| Application numbering | DONE | DP-YYYY-MM-NNNNN |
| DP form fields | DONE | Applicant, enterprise, address, Location of Demolition Works, Scope of Work, Full-time Inspector/Supervisor, Lot Owner Consent |
| Status workflow (skips zoning) | DONE | submitted → engineering_assessed → billed → paid → permit_generated → released |
| City/Barangay edit-form selection bug | DONE (fixed) | Alpine `x-model` + `x-for`-rendered `<option>`s race condition — switched to `:value` + `@change` + `$nextTick`/`$watch` reapplication |
| Workflow Actions section removed from Show page | DONE | Redundant with header action buttons |
| DEMO_FEE fee category | DONE | 6 fee types (floor area, mech equip, hand demolition incl/excl floors, appendage, moving), own category scoped to the DP permit type — separate from BP's pre-existing `ASS_DEMO_*` under ACC_FEE |
| Fee-schedule-driven assessment tab | DONE | `addDemolitionItem()` auto-computes `amount = quantity × rate` server-side; replaced the earlier manual "Unit Fee" text-entry fallback |
| `fee_types.unit_label` + Demolition Fees settings page | DONE | `/settings/demolition-fees` — Settings-configurable physical unit ("sq.m.", "lineal meter(s)", etc.) per fee type, drives the assessment tab's dynamic Quantity label |
| Application form PDF (NBC Form B-08) | DONE | Background-image overlay of the official 2-page scan; letterhead + Building Official block on page 2 |
| Assessment summary PDF | DONE | `pdf/assessment-summary-dp.blade.php` |
| Final permit certificate PDF | DONE | `pdf/demolition-permit.blade.php`, bordered-frame landscape style, QR verification code |
| Sidebar entries | DONE | Main nav, Assessment flyout, Permits flyout |
| Excluded from online self-service + generic fee-schedule listing | DONE | Has its own dedicated Demolition Fees settings page |
| `/permits/demolition` Print button removed | DONE | Application-form printing for DP is a manual/physical process — underlying route/PDF untouched |
| OR Number autofocus on payment form | DONE | `collections/create.blade.php` (shared by BP/OP/DP/SGP) |

---

## Signage Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD (walk-in) | DONE | `signage_applications` table + `SignageApplication` model/controller; morph map `sgp`; permit code `SGP` (not `SP`, already reserved for a future unbuilt permit type) |
| Application numbering | DONE | SGP-YYYY-MM-NNNNN |
| SGP form fields | DONE | Applicant name, applicant address, Scope of Work (Install/Attach/Paint checkboxes + detail textboxes), Wordings, Premises Of |
| Status workflow (skips zoning) | DONE | Same 5-step shape as DP: submitted → engineering_assessed → billed → paid → permit_generated → released |
| Assessment fees | DONE (manual entry) | Empty `SGP_FEE` category seeded (tab renders); no `FeeType`/`FeeSchedule` rows yet — generic "Add Fee Item" fallback form used, same as every category originally worked before a dedicated fee-schedule form was built |
| Application form print | PENDING | No scanned official form supplied yet; deferred by explicit scope decision — every other print output is complete |
| Assessment summary PDF | DONE | `pdf/assessment-summary-sgp.blade.php` |
| Final permit certificate PDF | DONE | `pdf/signage-permit.blade.php`, bordered-frame landscape style, QR verification code |
| Sidebar entries | DONE | Main nav, Assessment flyout, Permits flyout — positioned below Demolition Permit |
| Excluded from online self-service | DONE | Not excluded from the generic `/settings/fees` listing (no dedicated settings page yet) |
| `/permits/signage` Print button | DONE | Shown (unlike DP) — final permit certificate print is complete, only the application-form print is deferred |
| Cross-cutting `match($permitCode)` bug sweep | DONE (fixed) | Found and fixed 4 places missing an `SGP` arm during end-to-end verification: `BillingService::generateFor()` (billing created with wrong morph type), `collections/index.blade.php` (pay-button route + type badge), `permits/index.blade.php` (permit-number link hardcoded to the BP show route — a pre-existing bug also affecting DP, now fixed for all 4 types), `verify/permit.blade.php` (type label) |

---

## Fencing Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD (walk-in) | DONE | `fencing_applications` table + `FencingApplication` model/controller; permit code `FP` (pre-existing inactive PermitType flipped to active) |
| Application numbering | DONE | FP-YYYY-MM-NNNNN |
| FP form fields | DONE | Applicant/enterprise/address, Location of Construction, Scope of Work, Design Professional block, Inspector/Supervisor block, Consent of Lot Owner |
| Status workflow (skips zoning) | DONE | Same 5-step shape as DP/SGP: submitted → engineering_assessed → billed → paid → permit_generated → released |
| Inspector section design iteration | DONE | Originally a repeatable "Add Inspector" Alpine.js UI backed by a `fencing_inspectors` child table (`is_primary` flag) — first repeatable-child-record UI in this codebase. Simplified per user request to a second FIXED single block (8 flat `inspector_*` columns on `fencing_applications`, mirroring `design_professional_*`); migration drops `fencing_inspectors`, `FencingInspector` model deleted, controller/views/PDF read the flat columns directly |
| "Same as Design Professional" toggle | DONE | Pill-style toggle on the Inspector section copies all 8 Design Professional field values via client-side JS, reusing the existing "Same as PEE" pattern from the BP form |
| FP_FEE fee category | DONE | Reuses existing `ACC_FEE`-scoped fee schedule data (`ASS_FENCE_MASONRY`/`ASS_FENCE_INDIG`) under a new dedicated `FP_FEE` category rather than duplicating rate data |
| Line & Grade / Ground Preparation fee codes | DONE | 7 more codes added to the FP assessment fee dropdown (`ASS_LINE_GRADE`, `ASS_GP_INSPECT`, `ASS_GP_EXCAV`, `ASS_GP_ISSUANCE`, `ASS_GP_FOUND`, `ASS_GP_OTHER`, `ASS_GP_ENCROACH`), reusing existing `ACC_FEE` rate data; required adding a `case 'fixed':` branch to the fee-computation logic (3 of the 7 use fixed-fee computation, not needed by the original 2-code implementation). Note: these were first mistakenly wired into the Zoning assessment's fee dropdown, then fully reverted before being correctly added here — Zoning's dropdown is unchanged from before this session |
| Assessed Fees summing bug | DONE (fixed) | Certificate's Assessed Fees table only showed the first active fee item's amount instead of summing all active items — missed a second fee type when both Masonry and Indigenous fencing fees were assessed together |
| Final permit certificate PDF | DONE | `pdf/fencing-permit.blade.php`, 2-page plain-HTML/CSS reproduction of NBC Form B-03 |
| DomPDF 3-page pagination bug | DONE (fixed) | Certificate rendered 3 pages instead of 2 — root cause: insufficient CSS vertical-spacing headroom combined with a `display:table`-based two-column layout (`.box-half`) DomPDF mis-paginated; fixed by tightening spacing and switching to CSS `float`-based columns |
| Sidebar entries | DONE | Main nav, Assessment flyout, Permits flyout — positioned between Occupancy Permit and Demolition Permit |
| End-to-end verification | DONE | Full lifecycle verified in browser: create → submit → assess → finalize → pay → generate permit → print |

---

## Zoning / Planning Module

| Feature | Status | Notes |
|---------|--------|-------|
| Zoning assessment form | DONE | BOPMS-style card layout |
| Fee auto-compute | DONE | land_use_and_zoning_fees + certification_zoning_fees |
| Fee items table + delete | DONE | Per-row delete, auto-compute button |
| Assessment finalization | DONE | for_zoning_assessment → zoning_assessed |
| Skip locational clearance | DONE | Bypass planning, → submitted |
| Dedicated zoning fee tables | DONE | 162 LC rows, P500 cert fee |
| Zoning fee settings page | DONE | /settings/zoning-fees accordion UI |
| Variance/Non-Conforming fees | DONE | land_use_and_zoning_other_fees table |
| Fee type selector (4 types) | DONE | LC, LC Manual, Certification, Others |
| Checkbox bulk delete | DONE | fetch API bulk delete |
| Finalize password confirm | DONE | Hash::check() modal |
| Finalized lock | DONE | Add/remove/autocompute blocked after finalize; single amber banner |
| Zoning certification PDF | DONE | Template exists |
| Locational clearance PDF | DONE | Template exists |
| Revert zoning finalize / send back to editing | DONE | `revertZoning()`, `sendBackForEditing()` — password-confirmed |

---

## Fee Computation

| Feature | Status | Notes |
|---------|--------|-------|
| Fee schedule management | DONE | Categories/types/schedules CRUD |
| Zoning fee management | DONE | /settings/zoning-fees |
| All 6 computation methods | DONE | fixed, per_unit, range_based, cumulative_range, percentage (formula = PARTIAL) |
| Excess/min/max | DONE | |
| Construction fee data + tab | DONE | BOPMS-style: Part+Division+Area → auto lookup |
| Electrical fee data + tab | DONE | BOPMS-style: 7 types, range kVA, auto inspection % |
| Electrical inspection fee | DONE | `assessment.electrical_inspection_percentage` setting (default 10%) |
| Mechanical fee data + tab | DONE | BOPMS-style: equipment type+unit → auto base + NBC inspection fee |
| Mechanical NBC inspection fees | DONE | MECH_INSP category: 29 INSP_* types / 55 schedules from BOPMS ann_inspection_f* tables |
| Mechanical inspection formulas | DONE | flat (range-band), per_unit (rate×count), tiered (cumulative for elevators) |
| BP assessment tab navigation | DONE | 8 tabs + Summary, badges, hidden MECH_INSP tab |
| Plumbing fee data | DONE | Seeded |
| Plumbing tab (BOPMS-style) | DONE | 22 PLUMB_* types, dynamic unit labels |
| Electronics fee data | DONE | Seeded |
| Electronics tab (BOPMS-style) | DONE | 11 ELECT_* types |
| Accessories tab (ACC_BLDG) | DONE | BOPMS-style |
| Accessory Fees tab (ACC_FEE) | DONE | BOPMS-style |
| Surcharge tab (SURCHARGE) | DONE | Percentage-based on violation stage |
| Occupancy fee data | DONE | Seeded |
| Occupancy fee tab — OP (BOPMS-style) | DONE | 8 OCC_* types, dynamic unit label (Costing/Area/Unit/Meters); range_based w/ excess_every, per_unit, percentage all verified |
| Assessment finalization lock | DONE | BP + zoning: add/remove/autocompute blocked after finalize (UI + server guards) |
| Finalize stays on Summary tab | DONE | Redirects to ?tab=SUMMARY |
| BP assessment PDF | DONE | Fire Code Fees removed; Code 128 barcode above BP number; Approved By from building_official signatory; city seal header, enlarged fonts |
| OP assessment PDF | DONE | Separate `assessment-summary-op` template titled "OCCUPANCY PERMIT ASSESSMENT"; only Occupancy Fees section (no Zoning/BP/Other Fees); city seal header, enlarged fonts |
| Print button on BP + OP assessment index | DONE | Shown when status = engineering_assessed or billed |
| Revert engineering finalize (BP + OP) | DONE | `revertEngineering()` / `revertEngineeringOp()` — password-confirmed un-finalize |
| Zoning fees missing from printed Summary of Computation | DONE (fixed) | Root cause: `fee_category_id` never set on zoning `AssessmentItem::create()` calls; fixed + backfilled |

---

## Payment / Treasury Module

| Feature | Status | Notes |
|---------|--------|-------|
| Payment collection (cash/check/online) | DONE | |
| Official receipt generation | DONE | PDF, unique OR number, city seal header |
| Void transaction | DONE | Password verify, void tracking; header button removed from /collections (route remains) |
| Collection history | DONE | "My Collections": scoped to logged-in collector, month filter (default current month) |
| Barcode scan / search on Collections | DONE | Exact app-number match → payment form; partial match filters list |
| Cash change display | DONE | Live Alpine calc; server rejects insufficient cash amount |
| No-scroll payment form redesign | DONE | POS-style 3-col amount strip, segmented payment mode, sticky action bar |
| Awaiting Payment already-paid exclusion | DONE | `whereDoesntHave('collections', active)` guard, in addition to `status = billed` |

---

## Billing

| Feature | Status | Notes |
|---------|--------|-------|
| Billing auto-generation | DONE | Auto on assessment finalize (BillingService::generateFor); BL-YYYY-MM-NNNNN; Billing menu/page removed |
| Billing statement PDF | DONE | billing.print route kept; city seal + city/province from Settings |
| Billing status tracking | DONE | unpaid, partial, paid, void |

---

## Permit Generation

| Feature | Status | Notes |
|---------|--------|-------|
| Building permit PDF | DONE | NBC Form B-018 style — A4 landscape, city seal + DPWH logo header, thick bordered frame, QR verification code |
| Occupancy permit PDF | DONE | DPWH Certificate of Occupancy style — A4 landscape, DPWH logo + city seal, QR verification code |
| Permit numbering | DONE | CODE-YYYY-MM-NNNNN |
| QR code verification | DONE | `verification_token` (UUID) per permit; public `/verify/permit/{token}` page (no auth); `general.domain` setting controls the QR's domain |
| Generate Permit routing fix (OP) | DONE | Occupancy Permits list was posting to the BP-only generate route (404); now branches by `$type` |
| Revoke generated permit | DONE | `revertGenerate()` / `revertGenerateOp()` — tags `status = 'revoked'` + soft-delete, retains permit number, blocks regeneration; password-confirmed with required reason |
| Restore revoked permit | DONE | `restoreRevoke()` / `restoreRevokeOp()` — un-trashes the same Permit row, same number; password-confirmed only |
| Permits list filters + TTA + Permit No. column | DONE | `/permits/building`, `/permits/occupancy` — Search/Status(incl. Revoked)/Year filters; Permit No. as primary column; TTA beside Date |
| Building Official snapshot | DONE | Signatory captured on `Permit` at generation time; used by both PDFs + verification page; immune to later Signatory edits |
| Printed permit footer note | DONE | "Computer-generated permit. Printed on: {date} \| Printed by: {user}" on both BP/OP PDFs |
| Evaluation report PDF | DONE | City seal + Republic/city/province header |

---

## Reports

| Feature | Status | Notes |
|---------|--------|-------|
| Permit / Revenue / Collection reports | DONE | |
| Excel + PDF export | DONE | |
| Permit report status filter + Permit No./TTA columns | DONE | Filters to Permit Generated/Revoked only; combined app-date→permit-date range |
| Permit report peso sign fix | DONE | Switched PDF font to DejaVu Sans (bundled with DomPDF) — Helvetica/Arial lack the ₱ glyph |
| Audit Logs report | DONE | `/reports/audit-logs`, super-admin only (`view-audit-logs` permission); filters Spatie's `activity_log` by search/causer/subject type/event/month |

---

## Settings / Admin

| Feature | Status | Notes |
|---------|--------|-------|
| System settings | DONE | File settings: `general.logo`, `general.favicon`, `general.dpwh_logo`, `general.national_govt_logo` (GD-resized, per-key storage path); strings: `general.city`/`general.province` (real values seeded), `general.area_number`, `general.zip_code`, `general.domain` |
| User management | DONE | Create User: password now admin-set with complexity enforcement + strength UI (was hardcoded `password123`) |
| User management: role select / blank-field bug | PENDING | Create/Edit User form is currently unusable end-to-end — role `<select>` sends IDs but validation expects names; `User::create()` crashes if middle_name/phone/department/position are blank. Found during password-complexity work, tracked separately (not yet fixed) |
| Role/permission matrix | DONE | |
| Fee schedule management | DONE | |
| Signatory management | DONE | |

---

## Online Client Portal

| Feature | Status | Notes |
|---------|--------|-------|
| Registration + login | DONE | Separate portal |
| Online application submission | DONE | Auto-submits |
| Status tracking | DONE | Timeline view |
| Document requirement upload | PARTIAL | Model/route exists, UI needs work |
| Permit download | DONE | When status = released; now carries the same seal/DPWH logo/QR as the staff print path (previously rendered without them) |

---

## Dashboard

| Feature | Status | Notes |
|---------|--------|-------|
| KPI cards | DONE | Applications, pending, revenue — always reflect the live/current period |
| Monthly revenue chart | DONE | Chart.js; year-navigable via `?year=` (prev/next arrows, clamped to current year) |
| Monthly transactions chart | DONE | Grouped bar, BP vs OP, from `Collection.applicationable_type`; shares the same year navigator |
| Recent applications + daily count | DONE | |

---

## Not Migrating from BOPMS

| Feature | Reason |
|---------|--------|
| BFP module (fire-safety assessment/inspection workflow) | Not included in this system. FSEC No./Date (BP, OP) and FSIC No. (OP) exist only as reference fields shown on printed permits — no BFP validation, workflow, or integration |
| DB-level encryption | Not required |
| Annual inspection (non-mechanical) | Future scope |
| BFP partial payment | BFP excluded |
