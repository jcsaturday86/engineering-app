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

### Permit Revocation Redesign: Retain, Block Regeneration, Restore — COMPLETED

- Revoking a permit now tags `Permit.status = 'revoked'` (plus a required `revoke_reason`) in addition to the existing soft-delete, instead of just soft-deleting — the row and its permit number are preserved forever, not silently discarded
- `PermitController::doGenerate()` refuses to create a new permit for an application with a revoked permit on file, closing the gap where revoking then re-generating produced a brand-new, differently-numbered permit
- New `restoreRevoke()` / `restoreRevokeOp()` + `permits.restorePermit` / `permits.restorePermit.op` routes — un-trash the exact same Permit row, `status` back to `generated`, application back to `permit_generated`; password-confirm only (no reason), consistent with every other revert/undo action in the app
- `permits/index.blade.php`: "Permit Revoked" status badge, revoked permit number shown in red strikethrough instead of `-`, "Generate Permit" replaced entirely by "Restore Permit" for revoked applications
- Added Search/Status(incl. new `revoked` pseudo-status)/Year filters to `/permits/building` and `/permits/occupancy`, matching the application indexes; Permit No. promoted to the first/primary column (was Application No.); added a TTA column beside Date; shortened action button labels ("Restore"/"Generate"/"Print"/"Revoke")

### Building Official Snapshot on Permit Generation — COMPLETED

- New `permits.building_official_name/_title/_designation/_license_no` columns, populated once by `doGenerate()` from the active `building_official` Signatory and never re-fetched — survives later Signatory edits, revoke, and restore
- Both printed PDF templates and the public verification page (`verify/permit.blade.php`, new "Issued By" row) now read this snapshot instead of the live Signatory row
- Pre-existing permits best-effort backfilled with the then-current official in the same migration, since there's no historical record of who held the role at each past generation time

### Permit Report Enhancements & Peso Sign Fix — COMPLETED

- `/reports/permits` (PDF + Excel) now filters to Permit Generated/Revoked applications only (previously unfiltered by status), and adds Permit No. + TTA columns and a combined application-date→permit-date Date range
- Fixed the peso sign (₱) rendering as a missing-glyph box in the PDF — DomPDF's default Helvetica/Arial substitute lacks the U+20B1 glyph; switched to the bundled `DejaVu Sans` font (confirmed via `FontLib` glyph-map inspection to include it)
- Added explicit `<colgroup>` column widths + `table-layout: fixed` to the report table after the new columns caused text-wrapping misalignment in the 10-column layout

### DPWH Logo Setting & Settings File-Upload Path Bug — COMPLETED

- New `general.dpwh_logo` file-type setting (Settings → General), used by the Occupancy Permit PDF, falling back to the static `public/images/dpwh-logo.png` asset when empty
- Fixed a bug this surfaced: `SettingsController::update()` hardcoded every file-upload setting to the same storage path (`logos/city-seal.png`) — a second file setting would have silently overwritten (or been overwritten by) the first; paths are now derived per setting key

### Staff Account Password Complexity — COMPLETED

- `SettingsController::storeUser()` now validates (`Password::min(8)->mixedCase()->numbers()->symbols()`, `confirmed`) and actually applies the admin-supplied password — previously the Create User form collected a password + confirmation but the controller silently discarded both and hardcoded `password123` for every new staff account
- `settings/user-form.blade.php` ported the same live strength bar, 5-item complexity checklist, and match indicator already used on the client registration page, plus show/hide toggles on both password fields
- **Found but not fixed (tracked separately):** the role `<select>` sends numeric IDs while validation expects role names (`exists:roles,name`), and `User::create()` throws "Undefined array key" whenever middle_name/phone/department/position are left blank — together these make Create/Edit User fail on every submission regardless of the password fix. Verified the password logic in isolation by working around both bugs in test requests.

### Session/URL Handling & Login UX Polish — COMPLETED

- `Route::fallback()` redirects any unmatched URL to the role-appropriate home (or `login` for guests) instead of a 404
- A CSRF-expired (419) request now redirects to `login`/`staff.login` with a flash message instead of Laravel's default "Page Expired" screen — required matching on the wrapped `HttpException`'s status code, since Laravel converts `TokenMismatchException` to a generic `HttpException(419)` before render callbacks run
- Added the existing registration-page password show/hide toggle to client login and staff login

### Printed Permit Footer Note — COMPLETED

- Both permit PDFs show "This is a computer-generated permit. Printed on: {date} | Printed by: {user's full name}" below the existing legal footer, computed fresh on every render
- Fixed a page-overflow regression this caused on the Building Permit's single fixed-height page by trimming other vertical margins (signature block, footer spacing) by a matching amount
- Bumped the note's font from 8px to 10px for readability, re-verified it still fits on one page

### Unified Application Form — Background-Image Overlay Rebuild — COMPLETED

- Rebuilt `resources/views/pdf/application-form.blade.php` from a semantic HTML/table replica to a **background-image overlay**: `public/images/forms/unified-bp-form-p1.png` / `-p2.png` (scanned official 2-page form) are full-page CSS backgrounds, with ~84 dynamic fields (applicant/owner data, scope-of-work and occupancy-group checkmarks, costs, dates, signatories) absolutely positioned on top in inch units, so the printed output is visually near-identical to the government form
- No PDF-rasterization tooling (Ghostscript/ImageMagick/poppler) was available or installed on the dev machine — background PNGs were produced via Windows' built-in WinRT `Windows.Data.Pdf.PdfDocument` API from PowerShell instead; field positions were calibrated by scanning the source PNG for exact line/border pixel coordinates with PHP GD rather than by eyeballing screenshots
- Official city seal (top-left, page 1) now renders **dynamically** from `Setting` (`group=general`, `key=general.logo`), base64-embedded — same pattern as `$sealImage` in `PermitController` — instead of a hardcoded file; wired into both `ApplicationController::printForm()` and `OccupancyApplicationController::printForm()`
- Page 2 overlay adds the Building Official's name (bold, underlined) and designation, 15px, centered below the Terms and Conditions box, sourced from `Signatory` (`role=building_official`)

### OP Application Form — Dedicated DomPDF Template & Print Fix — COMPLETED

- `occupancy-applications/{id}/print` crashed by reusing the BP `application-form` view (`$application->permitType->code` — no such relation on `OccupancyApplication`, plus dozens of BP-only fields); new dedicated `pdf/occupancy-application-form.blade.php` rendered via DomPDF (A4 portrait, 0.75in margins, locational-clearance CSS pattern) reproducing the official "Unified Application Form for Certificate of Occupancy"
- Two-logo header: Official Seal (`general.logo`) left, National Government Logo (new `general.national_govt_logo` file setting) right, Republic/City/Province centered
- FULL/PARTIAL checkbox reads `applicationType->name` (Full/Partial application types), FSIC checkbox from `fsic_no`; requirements checklist rendered as 5 static unchecked boxes (no backing data model, per decision)
- Signatory block iterated against user-supplied reference mockups into a two-column layout: left "Inspected by:" — blank signature line, then the `building_official` Signatory's name + designation; right "Submitted by:" — applicant name over its line, CTC fields, then "Attested by: / FULL-TIME INSPECTOR OR SUPERVISOR OF CONSTRUCTION" with a blank ARCHITECT OR CIVIL ENGINEER line, Date line, and a blank PRC/PTR/TIN/CTC table (3-cell last row); both columns' signature lines mirrored/vertically aligned

### BP Unified Application Form — New Backgrounds & Overlay Letterhead — COMPLETED

- Both background scans replaced (`public/images/forms/unified-bp-form-p1.png` / `-p2.png`); p1 kept identical dimensions/registration (title band pixel-verified at the same row) so no field recalibration was needed below the header
- New p1 has no pre-printed header — letterhead is now overlaid: seal (`general.logo`) left, National Government Logo right, "Republic of the Philippines / {general.city} / Province of {general.province}" centered (settings values updated from Sample City/Province to City of San Fernando / La Union, in DB + seeder)
- Area No. digit box now filled from `application.area_number` with fallback to the `general.area_number` setting (fixed LGU district code — was always blank before)
- Readability audit of all 83 overlay fields: PTR-issued-at and Gov't ID fields re-tuned to fit; the two physically-too-narrow Place Issued cells now use `text-overflow: ellipsis`; Enterprise Name overlay removed entirely — pixel measurement proved the "FOR CONSTRUCTION OWNED BY AN ENTERPRISE" label occupies its whole cell, so the overlay printed on top of label text
- New p2 (1700×2600 = exactly 8.5×13in — needs its own `background-size: 8.5in 13in` override vs p1's Legal-crop sizing) ends with "SIGNATURE OVER PRINTED NAME OF OWNER/APPLICANT"; the Building Official name/designation overlay was removed and replaced by the applicant's name centered above that line (position pixel-matched to the scan)

### Dynamic Branding: Favicon Setting & Seal on Every Printed Document — COMPLETED

- New `Setting::general()` and `Setting::imageDataUri()` static helpers centralize the settings-fetch + base64-data-URI pattern used by every PDF controller
- New `general.favicon` file setting; `partials/favicon.blade.php` (included in `layouts/app`, `layouts/guest`, `auth/staff-login`, `verify/permit`) resolves favicon → seal → static `favicon.ico`, replacing the default Laravel tab icon on all pages
- Audit of all 12 PDF/print-producing controller methods found and fixed: Official Receipt, Billing Statement, BP/OP Assessment Summaries, and Evaluation Report had **no seal at all** (now render it, headers restructured); `OnlineApplicationController::doDownloadPermit()` rendered the same permit templates as the staff print path but passed **none** of `settings`/`sealImage`/`dpwhLogo`/`qrImage` (client-downloaded permits silently lost all branding + QR — now fully wired); `building-permit.blade.php` never rendered the `$dpwhLogo` the controller always built (now shown in the header's right cell)
- BP + OP assessment summary PDFs: seal enlarged (42→68px) and all font sizes bumped ~15-20% for readability, verified single-page

### Collections Page: My-Collections Scope, Month Filter, Void Button Removal — COMPLETED

- `/collections` Payment History ("My Collections") now shows only the logged-in collector's own transactions (`collected_by = Auth::id()`), filtered by month (`?month=YYYY-MM`, auto-submitting `<input type=month>`, defaults to current month); verified by reassigning a collection to another user (row count dropped) then restoring
- "Void Collection" header button removed from the index (route/page still exist)

### Application Show Pages: Cancel Hidden After Permit Generation — COMPLETED

- `applications/{id}` and `occupancy-applications/{id}`: the Cancel button's status exclusion list gained `permit_generated` (alongside cancelled/paid/released) — an application with a generated permit must go through permit revocation instead

### BP Test Data — COMPLETED

- Created BP-2026-07-00006 (id 8, status `submitted`) with every required field populated: full applicant/address/ID data, project + location (lot/block/TCT/tax dec), complete cost breakdown (₱2.05M), engineer + PEE professional blocks, owner block, FSEC reference, electrical loads, occupancy group A1 — for end-to-end print/assessment testing

### Print Forms Dropdown & Discipline Print Routes — COMPLETED

- BP application Show page: single Print button replaced with a right-aligned "Print Forms" dropdown (Alpine.js) listing 7 numbered items — 1. Application Form, 2–7. Architectural/Structural/Electrical/Sanitary/Mechanical/Electronics
- New generic route `applications/{id}/print-discipline/{discipline}` → `ApplicationController::printDiscipline()`; `DISCIPLINE_FORMS` const maps each discipline key to a form title. Structural/Electrical/Sanitary/Mechanical/Electronics render a shared blank placeholder (`pdf/discipline-form.blade.php`, DomPDF A4) — no official source form was available for those disciplines yet
- `applications/{id}/print` (`printForm()`) converted from a browser-print HTML view to an actual DomPDF stream — `defaultMediaType` set to `print` (needed for `@media print` `@page` rules to apply during PDF render, not just browser printing) and `dpi` set to 200 to match the background scan's true resolution (DomPDF's default 96 dpi silently downsamples/blurs a higher-resolution `background-image`)

### Architectural Permit PDF (NBC Form No. A-01) — COMPLETED

- `printDiscipline()` special-cases `architectural` → `ApplicationController::printArchitecturalForm()` → new `pdf/architectural-form.blade.php`, a real 2-page background-image-overlay PDF (same technique as the Unified Application Form) instead of the shared blank placeholder
- Background PNGs (`public/images/forms/architectural-p1.png` / `-p2.png`, 1700×2600 @ 200dpi) rasterized from the user's own NBC Form A-01 source PDFs via the WinRT `Windows.Data.Pdf.PdfDocument` PowerShell technique; every field position calibrated against the source scan with PHP GD pixel-scanning (border/label detection), not eyeballed
- All fields sourced from the `Application` record already on file: Box 1 (Owner/Applicant, enterprise name, form of ownership, occupancy, address, location of construction, scope-of-work checkboxes), Box 4/5/6 (Supervision engineer, Building Owner, Lot Owner consent — names, addresses, PRC/PTR/CTC + date/place-issued fields), plus a dynamic letterhead (seal + National Government logo + Republic/City/Province from Settings)
- Box 3 (Design Professional/Architect) intentionally left blank — the plans may be signed and sealed by an architect different from the engineer of record, so it's filled in by hand rather than auto-populated
- Page 2's "PERMIT ISSUED BY:" block reads the generated Permit's `building_official_title`/`_name`/`_designation` snapshot columns (same snapshot used by the Building/Occupancy Permit PDFs), rendered only when a Permit exists for the application
- Readability pass: Box 1's Last Name/First Name/M.I./TIN and Address values moved to sit on the blank line *below* their printed labels (rather than crowding beside them) at a larger font size; Box 6's CTC No./Date Issued/Place Issued given the same below-label treatment with a full 4-digit year
- Fixed a GD gotcha hit repeatedly during calibration: the source PNGs are palette-indexed, so `imagecolorat()` returns a raw palette index, not an RGB triple — must resolve via `imagecolorsforindex()` before comparing brightness, or measurements silently come out wrong

### Audit Logs Report (Super-Admin Only) — COMPLETED

- New `view-audit-logs` permission, granted only to `super-admin` (not `administrator` or any other role) in `RolePermissionSeeder`
- `ReportController::auditLogs()` — `GET /reports/audit-logs` (`can:view-audit-logs` middleware, independent of the group's blanket `can:view-reports`), queries Spatie's `Activity` model with `search` (description), `causer_id`, `subject_type` (Application/OccupancyApplication/Assessment/Collection/Permit/User), `event`, and a month filter (defaults to current month), paginated
- New `reports/audit-logs.blade.php` view; sidebar link gated by `@can('view-audit-logs')` inside the existing Reports section, so it's invisible to every role except super-admin even if they otherwise have `view-reports`

### Full BP/OP End-to-End QA Pass — COMPLETED

- Manual walkthrough of the complete Building Permit and Occupancy Permit lifecycles (application creation through permit generation), exercising every workflow/revert transition, all engineering fee categories, all payment modes, and every print output, using the staff-login curl + WinRT-rasterize + visual-read workflow established earlier in the project
- Negative/security checks included: attempting revert/void/generate actions without the required permission (expect 403), duplicate OR number submission, double permit generation, and an IDOR check on print routes as a `client`-role user

---

## Upcoming Tasks

| Task | Priority | Notes |
|------|----------|-------|
| Fix Create/Edit User form (role select + blank-field crash) | High | Currently unusable end-to-end — see "Staff Account Password Complexity" above |
| Additional permit types (FP, EP, DP, etc.) | Medium | Currently only BP and OP are active |
| Document requirement upload UI | Low | Model/route exists, UI needs improvement |
| Email notification configuration | Low | SMTP settings, notification templates |
| Annual inspection module (non-mech) | Future | Not in current requirements |
