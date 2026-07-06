# Tasks

---

## Completed Tasks

### Separate BP and OP into Different Database Tables — COMPLETED

- `applications` = BP only; new `occupancy_applications` = OP only
- 7 downstream tables use polymorphic `applicationable_type`/`applicationable_id` (morph map: bp/op)
- New `OccupancyApplication` model + `OccupancyApplicationService` + `OccupancyApplicationDTO`
- New `OccupancyApplicationController` + `/occupancy-applications/*` routes + views
- Parallel `*Op()` methods in Assessment/Billing/Collection/Permit controllers
- 4 notification classes accept `Model` instead of `Application`
- `ApplicationStatus::allowedTransitionsFor(string $permitTypeCode)` for OP flow

### Zoning Assessment Fee Auto-Compute & Settings — COMPLETED

- New `land_use_and_zoning_fees` table (162 rows, 52 sub-groups, 6 fee patterns) + `certification_zoning_fees` (P500)
- `ZoningController::autoCompute()` matches BOPMS `zoningAutoCompute()` logic
- New `ZoningFeeController` + `/settings/zoning-fees` accordion settings page
- `land_use_and_zoning_other_fees` table (Variance/Non-Conforming) + settings UI
- New `for_zoning_assessment` status; `submitted` = skip-LC path
- Browser autofill disabled on all forms; `ApplicationSeeder` with 5 BP + 5 OP test records

### Zoning Assessment UX Improvements — COMPLETED

- 4 fee type selector (LC, LC Manual, Certification, Others) matching BOPMS
- Checkbox select-all / bulk delete via fetch API
- Password confirmation modal on finalize (Hash::check())

### BP Assessment Tabbed Navigation & BOPMS-Style Forms — COMPLETED

- 8 fee category tabs + Summary tab with item count badges
- **Construction tab:** Part of Building + Division + Area → server-side fee lookup. `amount = area × fee_per_unit`
- **Electrical tab:** 7 fee types, conditional fields, range kVA: `base = fixed_fee + (kva × fee_per_unit)`. Inspection fee = `base × electrical_inspection_percentage` (setting, 10%). `amount` = base; `inspection_fee` stored separately
- Split `ELEC_TUG` → `ELEC_TRANS` + `ELEC_UPS` matching BOPMS
- New routes: `POST /assessments/{id}/construction-item`, `POST /assessments/{id}/electrical-item`

### Mechanical Fee Assessment with NBC Inspection Fees — COMPLETED

- **MECH_INSP fee category:** 29 `INSP_*` fee types with 55 schedule rows; NBC rates sourced from BOPMS `ann_inspection_f*` SQL tables (I through XIX). Category hidden from assessment tab bar.
- **Mechanical tab (BOPMS-style):** `addMechanicalItem()` computes base permit fee (MECH schedules) + NBC inspection fee (`resolveInspectionFee()` maps `MECH_REFRIG` → `INSP_REFRIG`). `amount` = base only; `inspection_fee` stored separately. Consistent grand total: `sum(amount) + sum(inspection_fee)`
- Three inspection fee formulas: `flat` (range-band fixed_fee ± excess), `per_unit` (rate × unit ± excess), `tiered` (cumulative for elevators: first N floors × rate + excess × rate2)
- Route: `POST /assessments/{id}/mechanical-item`
- `MECH_INSP` added to `$excludedTabs` so it never appears as a manual-entry tab

### Plumbing / Electronics / Accessories / Surcharge Tabs (BOPMS-style) — COMPLETED

- **Plumbing tab:** 22 PLUMB_* fee types grouped (Installation / Fixtures / Special Fixtures / Range-Based), dynamic unit label per fee type. `addPlumbingItem()` handles per_unit and range_based (with excess) methods
- **Electronics tab:** 11 ELECT_* fee types, `addElectronicsItem()`
- **Accessories (ACC_BLDG), Accessory Fees (ACC_FEE), Surcharge (SURCHARGE) tabs** with dedicated add methods and routes
- Routes: `POST /assessments/{id}/plumbing-item`, `electronics-item`, `accessory-item`, `acc-fee-item`, `surcharge-item`

### Assessment Finalization Locking — COMPLETED

- After BP assessment finalize: all add forms hidden, per-row and bulk Remove hidden; server-side `redirectIfFinalized()` guard in every add/remove method redirects to `?tab=SUMMARY` with error
- After zoning finalize: autocompute, add, remove (single + bulk), and Save Details blocked; `ZoningController::abortIfZoningFinalized()` aborts 403; single amber "finalized" banner
- Finalize (BP and OP) redirects back to the Summary tab (`?tab=SUMMARY`) instead of the first tab

### BP Assessment PDF & Print Improvements — COMPLETED

- Fire Code Fees section removed from the printed Summary of Computation; sections renumbered 1–10
- Real Code 128 barcode image (picqer/php-barcode-generator, base64 PNG) rendered above the BP number
- "Approved By" pulled from `signatories` where role = `building_official` (title + name on one line, designation below)
- Print button on BP assessment index when status = `engineering_assessed`

### OP Occupancy Fee Tab (BOPMS-style) — COMPLETED

- `addOccupancyFeeItem()` + route `POST /assessments/op/{op}/occupancy-fee`
- 8 OCC_* fee types; Unit field label switches by type: Costing (₱) / Area (sq.m) / Amount (₱) / Meters-Units
- Server-side computation honors all three occupancy methods:
  - `range_based` with excess: `fixed_fee + ceil(excess / excess_every) × excess_fee` (e.g. "per ₱1M or fraction thereof")
  - `per_unit`: `unit × fee_per_unit`
  - `percentage`: `unit × schedule.percentage` (e.g. J-II 50% of principal rate)
- All 8 divisions verified against seeded schedules (9 samples, subtotal ₱9,250)

### Billing Menu Removal & Auto-Generation — COMPLETED

- Billing menu/index page and manual generate routes removed; `BillingController` is print-only (`billing.print` kept)
- `BillingService::generateFor(PermitApplicationContract)` — new method, contains the same generation logic the old controller used; called from `AssessmentController::doFinalize()` right after an assessment is finalized, so BP/OP applications go straight from `engineering_assessed` to `billed` with no manual step
- Fixed a latent bug surfaced by this change: `collections/index.blade.php` used `$app->permitType->code` (crashes for OP, which has no `permitType` relation) — replaced with `getPermitTypeCode()`; the Collect Payment link now routes to `collections.create.op` for OP rows
- One-time catch-up ran for applications already stuck at `engineering_assessed` before this change, generating their billing so they could proceed to payment

### OP Assessment Print — Separate Template — COMPLETED

- New `pdf/assessment-summary-op.blade.php`, titled "OCCUPANCY PERMIT ASSESSMENT" — contains only an Occupancy Fees section (Zoning/Building/Electrical/Mechanical/Other Fees/Filing/Processing all removed, since none apply to OP)
- `AssessmentController::doPrint()` now dispatches to `doPrintOp()` for OP applications
- Fixed a bug hit during testing: `$itemsByCategory->except()` threw `Collection::getKey does not exist` on a grouped Eloquent collection — fixed with `->toBase()` first

### Assessment Index / Print Button — Billed Status Fix — COMPLETED

- Auto-billing on finalize meant applications skipped straight to `billed`, but the assessment index queries only included up to `engineering_assessed` — finalized applications (and their Print buttons) disappeared from the list
- `AssessmentController::index()` / `occupancyIndex()` now include `billed`; Print button shows for `engineering_assessed` or `billed` in both `assessments/index.blade.php` and `assessments/occupancy-index.blade.php`

### Collections UX — Barcode Search & Cash Change — COMPLETED

- `/collections` search box (autofocused): scanning the barcode from a printed assessment (which encodes the application number) on an exact match redirects straight to that application's payment form; partial text filters the Awaiting Payment list by app number or applicant name
- Payment form shows live **Change** (or **Short**, in red, with warning) as the collector types Amount Received while Payment Mode = Cash; `CollectionController::doStore()` rejects an insufficient cash payment server-side
- Removed the itemized Billing Summary card from the payment page; Application No./Applicant now shown inline at the top of Payment Details
- Redesigned the payment form as a compact POS-style layout (3-column amount strip, segmented Cash/Check/Online control, sticky action bar) so the collector doesn't need to scroll while processing a payment

### Building Permit PDF Redesign (NBC Form B-018) — COMPLETED

- Rebuilt `pdf/building-permit.blade.php` to match the real NBC Form No. B-018 layout: city seal + centered header, NEW/RENEWAL/AMENDATORY checkboxes, labeled field rows, single Building Official signature block
- A4 landscape, 0.5in margin on all four sides, thick double-line border (`.frame { border: 6px double }`) — tuned via content-stream inspection (stroke-coordinate + page-count checks) to avoid a spurious blank second page
- New Settings → General "Logo" upload (`general.logo`, type `file`) — city/LGU seal, GD-resized to max 400px before storage, embedded as base64 in the PDF
- New FSEC No. / FSEC Date Issued fields on the BP application form (`applications.fsec_no`, `fsec_issued_date` — re-added via a fresh guarded migration after discovering migration drift had left the columns missing from the live table despite an earlier migration showing as "Ran")
- New `general.zip_code` setting, used on the printed permit instead of an unreliable barangay→city lookup
- Iterative refinements: seal position/size, header text centering vs. logo position, border thickness, font sizes, footer note condensed to one line, signature line removed, replaced with a Date line

### Occupancy Permit PDF — Certificate of Occupancy Redesign — COMPLETED

- Fully rewrote `pdf/occupancy-permit.blade.php` (previously a generic boxed-table layout) to match the DPWH Certificate of Occupancy form: DPWH gear logo (left, new static asset `public/images/dpwh-logo.png`, background-cleaned via GD) + city seal (right), FULL/PARTIAL checkboxes, FSIC No./fees/OR info, field rows, boxed posted-notice + signature block
- New `occupancy_applications.fsic_no` and `applies_for` columns (two more guarded migrations, needed after discovering the same migration-drift issue affected this table)
- Fixed a routing bug found while testing: the Occupancy Permits list's "Generate Permit" button always posted to the BP-only `permits.generate` route (404 for OP) — `permits/index.blade.php` now branches by `$type`
- FULL/PARTIAL checkbox switched to read from `applicationType->name` (Full/Partial are modeled as OP application types) instead of the unused `applies_for` column, per follow-up request
- FSIC No. later removed from the *create* form only (per follow-up) — the column and print-template reference remain
- Font sizes bumped across both BP and OP templates; signature block spacing adjusted to leave room for a physical signature

### QR Code Permit Verification — COMPLETED

- Installed `endroid/qr-code`; new `permits.verification_token` (UUID, unique, backfilled for existing rows) set by `PermitController::doGenerate()`
- New public route `GET /verify/permit/{token}` (throttled, no auth) → `VerifyController::show()` → `verify/permit.blade.php`, showing permit type/number/status/applicant/project for a valid token, or a graceful "could not be verified" message otherwise
- `PermitController::print()` builds the verification URL from a new `general.domain` setting (falls back to `config('app.url')`) and renders it as a QR code embedded on both permit PDFs, sized up per follow-up request; the "Scan to verify" caption was later removed per follow-up, leaving just the code

### Dashboard — Monthly Transactions Chart & Year Navigator — COMPLETED

- New "Monthly Transactions" chart (grouped bar, BP vs OP) alongside the existing Monthly Revenue chart, sourced from `Collection.applicationable_type`
- Both charts accept `?year=` (prev/next arrows), clamped so it can't exceed the current year; the KPI stat cards above them intentionally stay tied to the live/current period regardless of the selected chart year

### Collections — Exclude Already-Paid Applications from Awaiting Payment — COMPLETED

- Found stale seed data where an application's `status` column stayed `billed` despite already having an active `Collection` (paid) and a generated permit
- Added `whereDoesntHave('collections', fn($q) => $q->where('status', 'active'))` to the Awaiting Payment list query and the barcode/exact-match redirect lookup, as a defensive guard against `status` drift

### Revert / Send-Back Actions for Every Workflow Step — COMPLETED

- New permissions: `revert-submission`, `revert-assessments`, `return-to-zoning`, `revert-zoning`, `revert-permits`
- `ApplicationController::revertSubmission()` / `OccupancyApplicationController::revertSubmission()` — submitted → draft
- `ZoningController::revertZoning()` — un-finalize a zoning assessment; `ZoningController::sendBackForEditing()` — send an application from Engineering back to Planning
- `AssessmentController::revertEngineering()` / `revertEngineeringOp()` — un-finalize a BP/OP engineering assessment
- `AssessmentController::returnToZoning()` — BP only; deletes engineering assessment items, sends application back to Planning
- `AssessmentController::revertToDraftOp()` — OP only; new dedicated action (not a modification of the plain revertSubmission) for an in-progress (`zoning_assessed`, not yet finalized) OP assessment: deletes all occupancy fee entries and the occupancy Assessment, reverts status to `draft`
- `PermitController::revertGenerate()` / `revertGenerateOp()` — soft-delete the generated Permit, roll status back to `paid`; fixed a related permit-counter bug found during implementation
- Every revert action requires password confirmation (`Hash::check()`) via an Alpine modal, matching the existing finalize UX, and writes an `activity()` log entry
- Bug found and fixed post-implementation: the "Return to Zoning" (BP) and "Revert to Draft" (OP) buttons were placed inside the Summary tab's `x-show`-gated content in `assessments/assess.blade.php`, but the assess screen defaults to the first fee-entry tab on load — not Summary — making both buttons invisible by default regardless of item count. Fixed by moving both into the page header, which is always rendered.

### Zoning Fee Print Fix & Application List UX — COMPLETED

- Fixed zoning fees missing from the printed BP Summary of Computation: `ZoningController`'s `AssessmentItem::create()` calls never set `fee_category_id`, so the print template's grouping by `ZONING_LC`/`ZONING_CERT` category code came up empty; added `fee_category_id` to all 6 create-call sites and backfilled existing stale rows
- `/occupancy-applications` index: added Applicant Address (later swapped for Project Title) and Status columns; relabeled `zoning_assessed` to "For Occupancy Assessment" for OP (no zoning department exists for OP) on both `occupancy-applications/index.blade.php` and `assessments/occupancy-index.blade.php`
- Added a **Year filter** (`?year=`, defaults to current year, current + previous year options) to `/applications` and `/occupancy-applications`
- Added a **Turn Around Time** column to both indexes: whole days from `submitted_at` (or `created_at`) to the latest generated Permit's `created_at`, `–` if not yet generated; caught and fixed a Carbon 3 `diffInDays()` regression (defaults to non-absolute, returns a negative float) during verification

---

## Upcoming Tasks

| Task | Priority | Notes |
|------|----------|-------|
| Additional permit types (FP, EP, DP, etc.) | Medium | Currently only BP and OP are active |
| Document requirement upload UI | Low | Model/route exists, UI needs improvement |
| Email notification configuration | Low | SMTP settings, notification templates |
| Annual inspection module (non-mech) | Future | Not in current requirements |
