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

### Structural Permit PDF (NBC Form A-07) — COMPLETED

- Second discipline form given a real background-image-overlay PDF (was a blank placeholder): `pdf/structural-form.blade.php`, wired into `ApplicationController::printDiscipline()` via a new `structural` branch → private `printStructuralForm()`, mirroring `printArchitecturalForm()` almost line-for-line
- Backgrounds rasterized from the user's own source PDFs (`3. STRUCTURAL P1.pdf` / `P2.pdf`) via the established WinRT technique; source pages were an unusual 11.33×17.33in (Excel-exported, same 8.5:13 aspect ratio scaled up 4/3×) — rasterized directly to the standard 1700×2600px canvas, then converted to JPEG per the perf fix above
- Extracted the "resolve building official" logic (Permit snapshot, falling back to the active `building_official` Signatory) shared by both `printArchitecturalForm()` and `printStructuralForm()` into a new `resolveBuildingOfficial()` private helper, avoiding duplicating the fallback logic added to Architectural in the prior session
- Same field-mapping conventions as Architectural: Box 1 (owner/applicant/enterprise/address), Location of Construction + Scope of Work (same `scope_of_work_id` 1–5 checkbox mapping), Box 4 "Supervision/In-Charge" filled from the generic `engineer_*` fields (no separate `structural_engineer_*` columns exist), Box 3 "Design Professional" left blank for hand-signing, Box 5/6 (Building Owner / Lot Owner consent, CTC row below-label), page 2 "Permit Issued By" from the Permit-or-Signatory resolution
- Calibration required several correction rounds after the first render revealed systematically wrong row y-coordinates in Box 3/4's title block and Box 5/6's name block — root cause: adjacent form rows on this scan are more tightly spaced than Architectural's, so several rows' true label text turned out to be ~0.3–0.5in away from where an initial coarse full-width darkness scan suggested; resolved by re-measuring with tightly-cropped visual zooms instead of trusting aggregate row-darkness counts alone

### Electrical Permit PDF (Form No. 77-001-S) — COMPLETED

- Third discipline form given a real background-image-overlay PDF: `pdf/electrical-form.blade.php`, wired into `ApplicationController::printDiscipline()` via a new `electrical` branch → private `printElectricalForm()`
- Backgrounds rasterized from the user's own source PDFs (`4. ELECTRICAL PERMIT P1.pdf` / `P2.pdf`, same unusual 11.33×17.33in native size as Structural, rasterized directly to 1700×2600px, converted to JPEG)
- Unlike Architectural/Structural, this form has **real dedicated data fields** rather than reusing the generic professional-in-charge block: Box 2 "Design Professional" is filled from `pee_name`/`pee_prc_no`/`pee_prc_validity`/`pee_ptr_no`/`pee_ptr_date_issued`/`pee_ptr_issued_at`/`pee_address`/`pee_tin` (Professional Electrical Engineer), and a new "Summary of Electrical Loads/Capacities" section is filled from `total_connected_load`/`total_transformer_capacity`/`total_generator_capacity` (KVA) — all pre-existing columns on `Application` that had no prior print output referencing them. Box 3 "Supervisor of Electrical Works" still reuses the generic `engineer_*` fields (no separate supervisor data exists), matching the Architectural/Structural convention.
- This form's Scope of Work checkboxes are entirely different from Architectural/Structural's (New Installation/Reconnection of Service Entrance/Relocation of Service Capacity/Annual Inspection/Separation of Service Entrance/Others/Temporary/Upgrading of Service Entrance) — only "New Installation" maps to an existing `scope_of_work_id` (1 = New Construction); the other 7 have no equivalent stored field and are left unchecked
- No ZIP CODE column exists in this form's ADDRESS row (unlike Architectural/Structural); CITY/MUNICIPALITY in the Location-of-Construction row is pre-printed static text ("CITY OF SAN FERNANDO, LA UNION"), not an overlay field, same as the other two forms
- Test data seeded on Application id=9: `total_connected_load`/`total_transformer_capacity`/`total_generator_capacity` and all `pee_*` fields, previously all NULL
- Two bugs found and fixed during verification: (1) the seal/national-logo `<img>` tags were rendering **twice** on the page — a stray duplicate appeared near the bottom of page 1 — root cause was DomPDF's known quirk where an absolutely-positioned `<img>` without `display:block` gets flowed once inline and once at its absolute position; fixed by adding `display:block` to both image tags. (2) the page-2 "Permit Issued By" name/designation were initially placed overlapping the numbered legal-conditions list — root cause was misreading a GD row-darkness scan (item 7's text and the actual "PERMIT ISSUED BY:" label produced very similar darkness-count signatures at first glance); re-verified with a targeted visual crop and repositioned into the ~1in blank gap between the label and the signature-line caption.

### Sanitary/Plumbing Permit PDF (Form No. 77-001-S) — COMPLETED

- Fourth discipline form given a real background-image-overlay PDF: `pdf/sanitary-form.blade.php`, wired into `ApplicationController::printDiscipline()` via a new `sanitary` branch → private `printSanitaryForm()`
- Backgrounds rasterized from the user's own source PDFs (`5. SANITARY PERMIT P1.pdf` / `P2.pdf`, same 11.33×17.33in native-size-scaled-2x quirk as Structural/Electrical, rasterized directly to 1700×2600px, converted to JPEG)
- This form's layout is denser and structured differently from the other three: Box 1 has separate ADDRESS and LOCATION OF INSTALLATION rows (each with its own city/municipality field — filled from `applicantCity`/a `general.city` fallback respectively, rather than one being pre-printed static text like on the other forms), no lot/block/TCT/tax-dec fields at all, and a large FIXTURES TO BE INSTALLED / WATER SUPPLY section with no backing data on `Application` (left entirely blank, consistent with the "no reliable 1:1 field mapping, don't guess" rule already applied to USE OF TYPE OF OCCUPANCY and most of the other forms' checkbox grids)
- Filled several fields that don't have a printed home on any of the other three discipline forms: `no_of_storeys`, `total_floor_area`, `plumbing_cost` (as "Total Cost of Installation" — the discipline-appropriate cost field), `proposed_construction_date` / `expected_completion_date`
- Box 6 ("Sanitary Engineer/Master Plumber Signed and Sealed Plans Specifications") left blank per the established Design-Professional-may-differ-from-engineer-of-record convention; Box 7 ("...In-Charge of Installation") and Box 8 (Applicant) filled from the generic `engineer_*` and `applicant_*` fields respectively
- This form has no "PERMIT ISSUED BY:" signatory section anywhere on either page (unlike the other three) — `resolveBuildingOfficial()` is not called and the controller doesn't load the `permits` relation
- Two bugs found and fixed during verification: the letterhead's 3-line Republic/City/Province block initially overlapped the pre-printed "OFFICE OF THE BUILDING OFFICIAL" text — this form has noticeably less vertical clearance above it than Architectural/Structural/Electrical, fixed by tightening the line spacing and font size; and a "Total Area" value initially rendered with the pre-printed blank line struck through the middle of the text — nudged up ~0.05in to sit cleanly above the line instead of overlapping it.

### Mechanical Permit PDF (NBC Form A-04) — COMPLETED

- Fifth discipline form given a real background-image-overlay PDF: `pdf/mechanical-form.blade.php`, wired into `ApplicationController::printDiscipline()` via a new `mechanical` branch → private `printMechanicalForm()`
- No official source scan existed for this form (confirmed — Mechanical/Electronics previously shared the generic blank placeholder). Backgrounds are the user's own clean digitally-generated reference images (not a scan) saved directly as `public/images/forms/mechanical-p1.png` / `-p2.png` at their native 1700×2800px (8.5×14in @ 200dpi — legal-size, unlike the other four forms' 8.5×13in), converted to JPEG; `printMechanicalForm()` sets paper size `[0, 0, 612, 1008]` accordingly
- Scope of Work checkboxes map all 12 of this form's options (New Construction/Addition/Renovation/Alteration/Conversion/Repair/Raising/Moving/Demolition/Accessory Structure/Erection/Others) against the seeded `scope_of_works` table via `$sk($id)` — the richest scope-of-work mapping of any discipline form so far (the other forms only map a single "New Installation/Construction" checkbox)
- Box 2 ("Installation and Operation of..." — boiler/pressure vessel/aircon/elevator/escalator/etc.) and Box 3/4 (Professional Mechanical Engineer / Supervisor-In-Charge of Mechanical Works) are left entirely blank: no backing `pme_*`/`sim_*` columns or per-installation-type checkbox columns exist on `Application` (confirmed via full model/migration search) — consistent with the established "don't guess, leave blank" convention already applied to unmapped boxes on the other forms
- Box 5 (Building Owner) / Box 6 (Lot Owner) reuse the generic `applicant_*`/`owner_*` fields, same convention as Architectural/Structural/Electrical; Box 6 sits in a separate right-hand column with its own gutter margin (~0.7in gap) rather than a plain half-page split
- Page 2 Box 9 "Permit Issued By" uses the same `resolveBuildingOfficial()`-sourced fallback as the other forms; Boxes 7/8 (internal office document receipt / progress-flow table) are pure background with no overlay data
- Two rendering bugs found and fixed during verification: the Address row's value initially sat at `top:3.97in`, close enough to the next row's border (`4.06in`) that dompdf's line-height pushed the glyph bottom past the border, visually reading as a stray strikethrough — fixed by moving it up to `top:3.85in`; the Location of Construction Street/Barangay values (positioned over the background's pre-printed underscore blanks, not a separate blank row) initially collided with the underscore at two different offsets (either a strikethrough through mid-glyph or clipped by the row's bottom border) — resolved by pixel-scanning the underscore's exact y-position (`4.46in`) and settling on `top:4.36in`, which clears both the label above and the border below
- Follow-up fixes at user request: the Application No./Building Permit No. overlay values crossed the pre-printed per-digit cell dividers, so a white `.mask` div was added behind each value to hide them without erasing the outer box border (the first attempt on MP No. accidentally spanned the box's exact outer bounds, painting over — and erasing — its left/right border lines too; fixed by insetting the mask 0.02in per side, matching the other two boxes). A letterhead (city seal + national govt logo, doubled to 1.0×1.0in; Republic/City/Province text, bumped to 11pt and tightened to single-line spacing) was added, removed, then restored per two rounds of user direction — the final state keeps it, matching Sanitary's letterhead convention

### Electronics Permit PDF (NBC Form A-07) — COMPLETED

- Sixth and final discipline form given a real background-image-overlay PDF: `pdf/electronics-form.blade.php`, wired into `ApplicationController::printDiscipline()` via a new `electronics` branch → private `printElectronicsForm()` — this completes the full print-forms set (all 6 disciplines now render dedicated views; `discipline-form.blade.php` is no longer used by any discipline, kept only as a generic fallback)
- No official source scan existed for this form either (same situation as Mechanical). Backgrounds are the user's own clean digitally-generated reference images, saved as `public/images/forms/electronics-p1.png` / `-p2.png` at the standard 1700×2600px (8.5×13in @ 200dpi, unlike Mechanical's legal-size quirk)
- Scope of Work has only 3 options (New Installation/Annual Inspection/Others) vs. Mechanical's 12; only New Installation (id=1) and Others (id=13) map to existing `scope_of_works` rows — Annual Inspection has no equivalent and is left unmapped
- Box 2 ("Nature of Installation Works/Equipment System" — telecommunication/broadcasting/TV/IT/security/fire alarm/sound/clock/automation/wiring systems) and Box 3/4 (Design Professional / Supervisor-In-Charge of Electronics Works) left entirely blank — no backing columns exist, same convention as Mechanical's Box 2/3/4
- Box 5 (Building Owner) / Box 6 (Lot Owner) reuse the generic `applicant_*`/`owner_*` fields; confirmed via pixel-scan that Box 6 sits in a separate column with a real ~0.27in gutter margin from Box 5 (left edges at `0.16in` and `4.38in` respectively), same layout quirk discovered on Mechanical
- Page 2 Box 9 "Permit Issued By" uses the same `resolveBuildingOfficial()`-sourced fallback, but this form's background has no "(Signature Over Printed Name)" caption or signature line under it at all — the BO name/designation are placed directly beneath the "PERMIT ISSUED BY:" label with nothing else printed below
- One significant measurement bug found and fixed during the build: Box 1's row boundaries were initially misread by one row (the App/ELP/Building-Permit-No. box's bottom border at `y:2.45in` was mistaken for the Owner/Applicant row's bottom border, when it's actually that row's *top* border — the real bottom sits at `y:3.22in`) — this pushed every Box 1 field one row too high, rendering values on top of the label above them instead of the blank line below. Re-verified every row boundary via fresh pixel scans before repositioning all of Box 1's fields, and fixed two follow-on collisions the same recalibration surfaced: the Box 5/6 "Date" value was overlapping the "Date" label itself (needed to sit beside it on the same baseline, not stacked above), and the Box 5/6 Address value crossed its row's bottom border (moved up 0.02in, dropped to 7pt)

### PDF Print Performance Fix (5-8s → ~2s) — COMPLETED

- Profiled `Dompdf::render()` directly (bypassing HTTP) to find that DomPDF's PNG-embedding path (full GD decode + manual alpha/pixel handling + Flate re-encode) accounted for ~95% of render time on the two background-image-overlay templates (Unified Application Form, Architectural Permit) — stripping the `background-image` rule dropped render from 7.2s to 0.3s
- Converted the 4 full-page background scans (`unified-bp-form-p{1,2}`, `architectural-p{1,2}`) from PNG to JPEG (quality 90, flattened onto white), updated the two Blade templates' `background-image` rules accordingly — `dpi=200` unchanged, sharpness confirmed unaffected by visual crop comparison; total print time dropped to ~2s
- Original PNGs kept on disk (unreferenced) as the lossless source for any future GD pixel-scan recalibration
- Also fixed two supporting issues found during profiling: DomPDF's `font_cache` directory (`storage/fonts`) didn't exist, so font metrics were re-parsed from scratch on every render — created it and added `SelfHealingServiceProvider::ensureFontCacheDirExists()` so it self-heals; and PHP OPcache was disabled in the local XAMPP `php.ini` — enabled it (`validate_timestamps=1`, `revalidate_freq=0` so dev edits still apply immediately), speeding up every page in the app, not just PDFs

### Full BP/OP End-to-End QA Pass — COMPLETED

- Manual walkthrough of the complete Building Permit and Occupancy Permit lifecycles (application creation through permit generation), exercising every workflow/revert transition, all engineering fee categories, all payment modes, and every print output, using the staff-login curl + WinRT-rasterize + visual-read workflow established earlier in the project
- Negative/security checks included: attempting revert/void/generate actions without the required permission (expect 403), duplicate OR number submission, double permit generation, and an IDOR check on print routes as a `client`-role user

### "Computer-Generated Document" Footer — Extended to All Application/Permit Forms — COMPLETED

- The "This is a computer-generated permit. Printed on: {date} | Printed by: {user}" note (previously only on `building-permit.blade.php`/`occupancy-permit.blade.php`, see "Printed Permit Footer Note" above) is now on every page of all 10 application/permit PDFs: BP Unified Application Form, OP Unified Application Form, and all 6 discipline forms (Architectural/Structural/Electrical/Sanitary/Mechanical/Electronics)
- Wording normalized on the original 2 forms from "computer-generated permit" to "computer-generated document" to match the newly-added ones exactly
- No controller changes needed anywhere — audited every route these 10 views are reachable from (`can:view-applications`/`can:print-permits` middleware, or the client-portal downloads' `Auth::id()`-matching `abort_if` guard) and confirmed `auth()->user()` is never null, so `auth()->user()?->full_name` is called directly from each Blade view, same as the original 2 forms already did
- On the 7 `.print-page` background-overlay forms, positioned the footer with `bottom:0.12in` rather than `top:`, since `.print-page` is already `position:relative` — this anchors correctly to each page's actual bottom edge with no per-form height math, which mattered because Mechanical's page is 8.5×14in while the other 6 discipline forms are 8.5×13in
- `occupancy-application-form.blade.php` (the one single-page, margin-based — not `.print-page` — template) got a normal-flow footer div appended after its last content block instead, matching how the original `building-permit`/`occupancy-permit` templates already do it (`.generated-note`, normal flow, not absolutely positioned)

### Demolition Permit (DP) — Full Third Permit Workflow — COMPLETED

- New `demolition_applications` table + `DemolitionApplication` model (implements `PermitApplicationContract`, uses `HasPermitApplicationBehavior`), morph map `dp` registered in `AppServiceProvider`; `getPermitTypeCode()` = `'DP'`. Overrides `buildingBarangay()` to point at `demolition_barangay_id` (the trait's default `building_barangay_id` column doesn't exist on this table), since generic code (`PermitController::print()`) always eager-loads that relation by that fixed method name — `demolitionBarangay()` is kept as an alias.
- `DemolitionApplicationController` — full CRUD (index/create/store/show/edit/update/submit/revertSubmission/cancel/printForm), mirroring `ApplicationController`'s structure with DP-specific fields: applicant + enterprise + address (reused BP address block), Location of Demolition Works (lot/block/TCT/tax-dec/street/barangay), Scope of Work (demolition/others + detail), Full-time Inspector and Supervisor of Demolition Works (name/address/PRC/PTR/TIN), Lot Owner Consent (name/CTC).
- Parallel `*Dp()` wrapper methods added to `AssessmentController`, `CollectionController`, `PermitController` (assessDp/addItemDp/summaryDp/finalizeDp/revertEngineeringDp/revertToDraftDp/printDp/createDp/storeDp/generateDp/revertGenerateDp/restoreRevokeDp), all delegating to the same generic private methods (`doAssess`/`doAddItem`/`doCreate`/`doStore`/`doGenerate`/etc.) already shared by BP/OP — no duplicated business logic. `assess.blade.php` threads a new `$isDp` flag through every route/visibility ternary alongside `$isOp`.
- Workflow **skips Zoning** entirely, same shape as OP: `draft → submitted → engineering_assessed → billed → paid → permit_generated → released`. `AssessmentController::doRevertEngineering()`'s status-revert ternary special-cases DP (and later SGP) to revert to `submitted` instead of `zoning_assessed`.
- New `DEMO_FEE` fee category (own dedicated category, `permit_type_id` scoped to DP — not reusing the pre-existing `ASS_DEMO_*` fee types under BP's `ACC_FEE` category, which are left untouched and still used by BP's Accessory Fee tab) with 6 fee types (`DEMO_FLOOR_AREA`, `DEMO_MECH_EQUIP`, `DEMO_HAND_INCL_FLOORS`, `DEMO_HAND_EXCL_FLOORS`, `DEMO_APPENDAGE`, `DEMO_MOVING`) seeded with real NBC demolition-fee rates. `AssessmentController::addDemolitionItem()` is a dedicated fee-schedule-driven add-item method (auto-computes `amount = quantity × rate` server-side from `FeeSchedule`) — DP originally used the fully-generic `doAddItem()` fallback (manual quantity + unit-fee entry) before this was built.
- New `fee_types.unit_label` column (nullable string) — the physical unit a fee is measured in ("sq.m.", "lineal meter(s)", "unit(s)"), editable inline per fee type on the new `/settings/demolition-fees` page (`DemolitionFeeController`), and read by `assess.blade.php`'s `DEMO_FEE` tab to drive the Quantity field's dynamic unit label — replacing what every other category still does as a hardcoded per-view JS map.
- 3 PDF templates: `pdf/demolition-application-form.blade.php` (background-image overlay of the official NBC Form No. B-08 "Demolition Permit" application scan, 2 pages, same overlay technique as the BP/discipline forms — includes a letterhead with seal + national govt logo + Republic/City/Province, and a Building Official title/name/designation block on page 2 above the signature line, sourced via a `resolveBuildingOfficial()` helper identical in shape to `ApplicationController`'s), `pdf/assessment-summary-dp.blade.php` (plain DomPDF, "DEMOLITION PERMIT ASSESSMENT" summary of computation, barcode + Approved By), `pdf/demolition-permit.blade.php` (final issued-permit certificate, bordered-frame landscape A4 style, QR verification code).
- Sidebar entries added in 3 locations (main nav collapsible section, Assessment flyout, Permits flyout); `PermitType` row (`code = 'DP'`) activated; excluded from `OnlineApplicationController` (online self-service) and from the generic `/settings/fees` listing (`FeeScheduleController`) since DP has its own dedicated fee-schedule settings page.
- `/permits/demolition` (shared `permits/index.blade.php`, `$type = 'demolition'`): Project Title column hidden (DP has no `project_title` field); the application-form Print button was later removed for demolition rows specifically (see below) since the printed application form is filled/signed manually — the underlying route/PDF is untouched, just the button is hidden.

### Demolition Permit: Application Print Removal, Assessment Alignment, Payment Autofocus, Fee-Unit Overhaul — COMPLETED

- Removed the Workflow Actions section from the DP application Show page (redundant with the header action buttons)
- Fixed City/Municipality and Barangay not showing their current selection on the DP edit form — an Alpine.js gotcha where `x-model` on a `<select>` whose `<option>`s are rendered by a nested `x-for` applies its initial value before the matching option exists in the DOM; fixed by switching to `:value` + `@change` + `x-init`/`$watch`-triggered `$nextTick()` reapplication (documented as a general pattern, since every other cascading-address `<select>` in the app — BP/OP/SGP forms — uses the same fix)
- Rebuilt `pdf/demolition-application-form.blade.php` as a full background-image overlay of the official NBC Form B-08 scan the user provided (previously a plain bordered-table layout) — letterhead, all Box 1–3 fields calibrated via the established GD-pixel-scan technique, larger overlay font, and a Building Official title/name/designation block placed above the signature line on page 2
- Removed the "Unit Fee" free-text input from the DP assessment tab and replaced it with `addDemolitionItem()` + `fee_types.unit_label` (see above) — quantity is now entered against a Settings-configured, fee-schedule-driven unit and rate rather than a manually-typed peso amount
- Added `autofocus` to the OR Number field on `collections/create.blade.php` (shared by BP/OP/DP/SGP payment forms) so it's focused the instant the Record Payment page loads
- Removed the "Print" button and "Project Title" column from `/permits/demolition` specifically (application-form printing for DP is a manual/physical process) — the underlying `permits.print` route, `PermitController::print()`, and `pdf/demolition-permit.blade.php` template are untouched and can be re-enabled by removing the `@unless($type === 'demolition')` wrapper

### Signage Permit (SGP) — Full Fourth Permit Workflow — COMPLETED

- New `signage_applications` table + `SignageApplication` model, morph map `sgp` registered. A much simpler form than DP/BP — applicant name (first/middle/last), applicant address (province/city/barangay/street/zip), Scope of Work as three independent checkboxes (Install/Attach/Paint) each with its own detail textbox, plus Wordings and Premises Of free-text fields. Overrides `buildingBarangay()` to alias `applicantBarangay()` (SGP has no separate site-location address, unlike DP's `demolition_barangay_id`).
- Permit code is **`SGP`**, not `SP` — `SP` was already reserved in the seeded `permit_types` table (and `PermitTypeCode` enum) for a future, unbuilt "Sign Permit"/"Sanitary-Plumbing Permit" placeholder.
- `SignageApplicationController` — same CRUD shape as `DemolitionApplicationController`, trimmed (no enterprise/CTC/inspector/lot-owner sections, no occupancy-group selection). Parallel `*Sgp()` wrapper methods added to `AssessmentController`/`CollectionController`/`PermitController`, all thin delegations to the existing generic private methods — **zero new business logic**, purely wiring. `assess.blade.php` threads a new `$isSgp` flag alongside `$isDp`/`$isOp`.
- Workflow skips Zoning, same 5-step shape as DP/OP: `draft → submitted → engineering_assessed → billed → paid → permit_generated → released`.
- **Fees are manual-entry only** (explicit scope decision, confirmed with the user) — a single empty `SGP_FEE` fee category was seeded (`permit_type_id` scoped to SGP) so the Assessment page has a tab to render, but no `FeeType`/`FeeSchedule` rows exist yet; the assessment tab falls through to the fully-generic "Add Fee Item" form (pick category, type description/quantity/unit-fee by hand) — the same fallback every category originally used before dedicated fee-schedule-driven forms were built for BP/OP/DP. A real rate table can be added later the same way DEMO_FEE was.
- **Application-form print is deferred** — no scanned official Signage Permit form has been supplied yet, so `SignageApplicationController` has no `printForm()`/PDF route, unlike every other permit type. Everything else prints normally: `pdf/assessment-summary-sgp.blade.php` (plain DomPDF summary of computation, cloned from DP's) and `pdf/signage-permit.blade.php` (final issued-permit certificate, cloned from DP's bordered-frame landscape style, with Scope of Work/Wordings/Premises-Of fields).
- Sidebar entries added in the same 3 locations as DP, positioned directly below the Demolition Permit entries. `PermitType` row (`code = 'SGP'`) activated; excluded from `OnlineApplicationController` and (unlike DP) **not** excluded from the generic `/settings/fees` listing, since SGP has no dedicated fee-schedule settings page — an admin can add `FeeType`/`FeeSchedule` rows there directly once real rates exist.
- `/permits/signage` (`$type = 'signage'`): Project Title column hidden (no `project_title` field), but unlike DP the **Print button is shown** — SGP's final permit certificate print is fully built, only the upstream application-form print is deferred.
- **4 pre-existing bugs found and fixed during end-to-end browser verification** (all `match($permitCode)`/`match($app->getPermitTypeCode())` blocks elsewhere in the codebase that had a `DP` arm but fell through to the wrong `default` case for the new `SGP` code — several of these same blocks were *also* missing a `DP` arm before this session and would have mis-routed for Demolition too):
  - `BillingService::generateFor()` — billing was silently created with `applicationable_type = 'bp'` instead of `'sgp'`, orphaning it from the application (status still flipped to `billed`, but the `Billing` record was unreachable via the relation). Caught because the payment page showed ₱0.00 due.
  - `collections/index.blade.php` — the "Collect Payment" button's route match and the type-badge color match both lacked an `SGP` arm.
  - `permits/index.blade.php` — the permit-number link was **hardcoded to the BP `applications.show` route regardless of `$type`** (a genuine pre-existing bug affecting DP too, not introduced this session) — fixed to branch by `$type` across all 4 permit types.
  - `verify/permit.blade.php` — the public verification page's permit-type label map.
- Full lifecycle verified live end-to-end in-browser (create → submit → assess with a manual fee item → finalize → bill → pay → generate permit → print both PDFs), with the above bugs caught and fixed mid-verification, then re-run clean. Test data cleaned up afterward.

---

### Fencing Permit (FP) — Full Fifth Permit Workflow — COMPLETED

- New `fencing_applications` table + `FencingApplication` model, morph map `fp` registered; permit code `FP` was a pre-existing inactive `PermitType` row, flipped to active. Same 5-step lifecycle as DP/SGP, no Zoning stage: `draft → submitted → engineering_assessed → billed → paid → permit_generated → released`.
- Form fields: Applicant Info, Enterprise/Ownership, Applicant Address, Location of Construction, Scope of Work (single-choice: new_construction/erection/addition/repair/others), Design Professional, Plans and Specifications (fixed block), Full-Time Inspector or Supervisor (fixed block, identical shape to Design Professional), Consent of Lot Owner. `FencingApplicationController` mirrors `DemolitionApplicationController`'s CRUD shape, plus a dedicated `report()` action (landscape DomPDF via the shared `pdf/report.blade.php` template, not present on DP/SGP's controllers). No `printForm()` — same deferred-application-print precedent as DP/SGP.
- **Inspector-section design iteration**: originally built as a repeatable "Add Inspector" Alpine.js UI backed by a new `fencing_inspectors` child table (`is_primary` flag to resolve which inspector prints on the certificate's single Box 3 slot) — the first repeatable-child-record UI pattern anywhere in this codebase. Per user follow-up request, this was simplified to a second FIXED single block instead: migration drops `fencing_inspectors`, adds 8 flat `inspector_*` columns to `fencing_applications` (mirroring `design_professional_*` exactly), `FencingInspector` model deleted, controller/views/PDF updated to read the flat columns directly. A "Same as Design Professional" pill-toggle was then added to the Inspector section, copying all 8 fields via client-side JS (`copyDesignProfessionalToInspector()`), reusing the existing "Same as PEE" toggle pattern already present on the BP application form.
- **`FP_FEE` fee category** (new, dedicated) reuses existing `ACC_FEE`-scoped `FeeType`/`FeeSchedule` rows rather than seeding new rate data — initially `ASS_FENCE_MASONRY`/`ASS_FENCE_INDIG`, later extended with 7 more codes (`ASS_LINE_GRADE`, `ASS_GP_INSPECT`, `ASS_GP_EXCAV`, `ASS_GP_ISSUANCE`, `ASS_GP_FOUND`, `ASS_GP_OTHER`, `ASS_GP_ENCROACH` — "Line & Grade" / "Ground Preparation & Excavation" fees) via a grouped `<optgroup>` select in `assess.blade.php`'s `FP_FEE` tab. The 7-code addition required a new `case 'fixed':` branch in `AssessmentController::addFenceItem()`'s computation switch (3 of the 7 use fixed-fee computation, unneeded by the original 2-code implementation). These same 7 codes were first mistakenly wired into the Zoning assessment's dropdown instead, then fully reverted per user correction before being correctly added to FP — Zoning's fee dropdown is unchanged from before this session.
- Final permit certificate: `pdf/fencing-permit.blade.php`, a 2-page plain-HTML/CSS reproduction of NBC Form B-03 (not a scanned-background overlay). Two bugs found and fixed during QA: (1) a DomPDF pagination bug rendering 3 pages instead of 2 — root-caused to insufficient CSS vertical-spacing headroom plus a `display:table`-based two-column layout DomPDF mis-paginated, fixed by tightening spacing and switching to CSS-float/inline-block columns; (2) the Assessed Fees table on page 2 only summed the first active fee item instead of all active items, silently dropping a second fee type when both Masonry and Indigenous fencing fees were assessed on the same application.
- Sidebar entries added in all 3 locations, positioned between Occupancy Permit and Demolition Permit in the main collapsible nav (per the pre-existing `sort_order` on the seeded `FP` `PermitType` row) — but listed last (after Demolition and Signage) in the Assessment and Permits flyout submenus. Excluded from `OnlineApplicationController`, same as DP/SGP.
- Full lifecycle verified live end-to-end in-browser (create with both Design Professional/Inspector blocks filled → submit → assess, testing all 3 computation methods → finalize → pay → generate permit → verify 2-page PDF). Test data cleaned up afterward.

---

## Upcoming Tasks

| Task | Priority | Notes |
|------|----------|-------|
| Fix Create/Edit User form (role select + blank-field crash) | High | Currently unusable end-to-end — see "Staff Account Password Complexity" above |
| Signage Permit fee schedule + application-form print | Medium | `SGP_FEE` category exists but has no seeded rates (manual entry only); no scanned official application form supplied yet for the background-overlay print |
| Additional permit types (EP, ELP, MP, PP, ECP) | Medium | BP, OP, DP, SGP, and FP are now active; the rest remain unbuilt placeholders in `permit_types` |
| Document requirement upload UI | Low | Model/route exists, UI needs improvement |
| Email notification configuration | Low | SMTP settings, notification templates |
| Annual inspection module (non-mech) | Future | Not in current requirements |
