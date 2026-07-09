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
| Application form print | DONE | Browser print, background-image overlay of the official 2-page Unified Application Form (8.5×13in long bond), ~84 dynamic fields; overlaid letterhead (seal left, National Govt logo right, city/province from Settings); Area No. from `general.area_number`; p2 applicant signature line (Building Official block removed with the new scan) |
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
