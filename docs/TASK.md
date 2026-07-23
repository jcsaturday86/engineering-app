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

### Fencing Permit Application-Form Print — Added, Calibrated, and Performance-Fixed — COMPLETED

- **Added `printForm()`** (`FencingApplicationController`, route `fencing-applications.print`) — mirrors `DemolitionApplicationController::printForm()`'s background-image-overlay approach: `pdf/fencing-application-form.blade.php` layered over the two official NBC Form B-03 scans, `public/images/forms/fencing-p1.png`/`fencing-p2.png` at the time. Unlike DP/SGP's still-deferred application-form prints, this one was built and shipped. A Print button was added to the application `show` page; the Activity Log section was removed from the same page in the same pass, and the `/permits/fencing` list's Print button was hidden (Generate only), matching DP's existing behavior.
- **Applicant CTC fields**: a follow-up migration added `applicant_ctc_no`/`applicant_ctc_date_issued`/`applicant_ctc_issued_at` to `fencing_applications` (model/form/show page/print form all updated), matching the pattern already used for Design Professional, Full-Time Inspector, and Consent of Lot Owner — DP has no equivalent applicant-level CTC block.
- **Field-position bugs, found via user screenshots of the real generated PDF and fixed via PHP-GD pixel scans of the source scans** (not eyeballed estimates — each fix re-measured the actual label/line boundaries in `fencing-p1.png`/`fencing-p2.png` before repositioning):
  - Box 1's Address value was sitting on the "LOCATION OF CONSTRUCTION" label below it; root cause was `.sm`'s CSS `font-size` override not resetting `line-height` (DomPDF doesn't correctly recompute unitless line-height against an overridden font-size), fixed by giving `.sm` its own explicit `font:` shorthand. The same fix was applied file-wide since it's a systemic bug, not a one-off.
  - Box 2/3's Design Professional/Inspector name lines were centered ~0.33–0.47in right of their actual signature lines (the `.ctr` band's width/left didn't match the line's real span) — recentered against pixel-scanned line coordinates.
  - Box 4's C.T.C. row (both Applicant and Lot Owner halves) had values overlapping their own "Date Issued"/"Place Issued" labels, worst on the Owner side where `owner_ctc_no` sat squarely inside the "Date Issued" label's own span — all 6 values repositioned into their actual blank-line gaps.
  - The Location of Construction row (Lot/Blk/TCT/Tax Dec. No.) had all four values positioned before or inside their own labels — same root cause, same fix. Tax Dec. No.'s value additionally needed centering (`text-align:center` over the blank line's true span) rather than left-alignment, per user follow-up request.
  - Page 2's Building Official title/name/designation block rendered with the pre-printed signature line struck through the text. The original `top:11.24in` offset was based on a rough visual read of a screenshot, not a pixel scan — the actual signature line sits at `10.72in`, not the assumed `11.32in`. Re-measured and repositioned both a title+name line and a new designation line above the real line, matching DP's two-line-above-the-line convention (DP never displayed `$boDesignation` before despite the controller already computing it).
  - Scope of Work's 5 checkboxes (New Construction/Erection/Addition/Repair/Others) were off their printed squares by 0.04–0.25in each and undersized (0.11×0.10in vs. the real 0.13×0.13in squares); the `&#10004;` checkmark glyph was also silently rendering as "?" because `.c`'s font stack lacked `'DejaVu Sans'` — DomPDF's core Arial font has no U+2714 glyph, a workaround already present in every *other* discipline form's print template (`demolition-`, `electrical-`, `mechanical-`, `architectural-`, `electronics-`, `sanitary-`, `structural-`, `application-form.blade.php`) but missed when this one was first written.
- **Header letterhead added**, matching DP's exact pattern: official seal + national government logo (`Setting::imageDataUri()`, keys `general.logo`/`general.national_govt_logo` — already computed by the controller but unused by the view until now) plus centered "Republic of the Philippines / {city} / Province of {province}" text, positioned in the blank band between "NBC FORM NO. B-03" and "OFFICE OF THE BUILDING OFFICIAL" (confirmed via pixel scan: y:0.40–1.165in). User later asked for the logos "1.5x bigger" (0.62in → 0.93in); a literal 1.5x would overlap "OFFICE OF THE BUILDING OFFICIAL" by ~0.22in, so 3 options were presented via `AskUserQuestion` — user chose the largest safe size, 0.76in (~1.23x), `top` moved from `0.46in` to `0.40in` to use the full available band.
- **Performance fix**: `fencing-p1.png`/`fencing-p2.png` are Truecolor+Alpha PNGs, which hit the exact DomPDF PNG-embedding slow path already profiled and fixed for every other discipline form (see "PDF Print Performance Fix" above) — this form was simply built after that fix and never got the PNG→JPEG conversion. Measured render time: 9.96s before, 2.35s after converting to flattened `fencing-p1.jpg`/`fencing-p2.jpg` (quality 90) and updating the two `background-image` rules — in line with Demolition Permit's 1.81s. Verified visually via rasterized re-render that image quality is unaffected. Original PNGs kept on disk, unreferenced, as calibration sources.
- **Application-form validation**: at user request, every `validateApplication()` rule was changed from `nullable` to `required`, except `owned_by_enterprise` (stays an optional boolean checkbox), `enterprise_name`/`form_of_ownership_id` (`required_if:owned_by_enterprise,1` — required only when the enterprise checkbox is checked), and `scope_of_work_detail` (`required_if:scope_of_work,repair,others` — a judgment call, since blanket-requiring it would break submission for the 3 scope choices that don't show the field). `remarks` was left `nullable` since it has no corresponding form input at all — forcing it required would make the form permanently unsubmittable. The Blade form got matching `required` HTML attributes, red asterisks, and `@error` blocks on every field, plus an Alpine `:required="ownedByEnterprise"` binding on the two conditional fields so the browser's native validation UI reflects the same conditional logic as the server. Verified end-to-end in-browser: an empty submission returns exactly 41 required-field errors with the enterprise pair correctly absent from the list; checking the enterprise checkbox flips their `required` attribute live.
- All fixes verified via the established render-to-PDF + WinRT-PowerShell-rasterize-to-PNG + crop-and-view workflow (no working `preview_start`/Chrome DevTools browser pane available mid-session — it hung on both the local file and a simple external URL, unrelated to these changes; fell back to the rasterization method already proven earlier in the project). All temp render scripts/PDFs/PNGs cleaned up after each verification round.

### Fencing Permit (FP) — Fifth Full Permit Workflow — COMPLETED

- New `fencing_applications` table + `FencingApplication` model, morph map `fp`, `getPermitTypeCode()` = `'FP'`. Same 5-step no-zoning shape as DP/SGP. Full field set mirroring NBC Form B-03: applicant/enterprise, cascading address + `applicant_ctc_*` triplet, Location of Construction, single-choice Scope of Work, Design Professional block, Full-Time Inspector or Supervisor block, Consent of Lot Owner.
- Inspector section design iteration: originally a repeatable Alpine "Add Inspector" UI backed by a `fencing_inspectors` child table with an `is_primary` flag (this codebase's first repeatable-child-record UI) — simplified per user request into a second fixed block matching Design Professional exactly (8 flat `inspector_*` columns added to `fencing_applications`, `fencing_inspectors` dropped). A "Same as Design Professional" toggle (`copyDesignProfessionalToInspector()`) was added, reusing the BP form's existing "Same as PEE" JS pattern.
- Application-form print (`printForm()`, `pdf/fencing-application-form.blade.php`) added in a follow-up session — background-image overlay over 2 NBC Form B-03 scans, calibrated via several rounds of PHP-GD pixel scanning (Box 1/2/3/4 field-vs-label overlaps, Page 2 Building Official signature-line strikethrough, Scope of Work checkbox sizing/glyph, header letterhead). Converted from PNG to JPEG backgrounds for the same DomPDF performance fix applied to every other discipline form (~10s → ~2.3s).
- `FP_FEE` fee category reuses existing `ACC_FEE`-scoped rate data (`ASS_FENCE_MASONRY`/`ASS_FENCE_INDIG`, plus 7 more Line & Grade / Ground Preparation codes added later) rather than seeding new rates; required a new `case 'fixed':` branch in `addFenceItem()`'s computation switch.
- `pdf/fencing-permit.blade.php` — 2-page plain-HTML/CSS reproduction of NBC Form B-03 (not background-overlay, since no scanned certificate background exists); fixed a DomPDF 3-page mis-pagination bug (`display:table`-based two-column layout → `inline-block`) and an Assessed-Fees summing bug (only the first active fee item's amount was shown instead of the sum of all active items).
- Sidebar entries (positioned between Occupancy and Demolition, per its inherited `sort_order`); `PermitType` activated; excluded from online self-service.

### Mechanical Permit (MP) — Sixth Permit Workflow (Original Build) — COMPLETED, Later Renamed/Rebuilt

- New `mechanical_applications` + `mechanical_permit_units` tables, `MechanicalApplication` model (morph `mp`, `getPermitTypeCode()` = `'MP'`), minimal form (owner/location + New/Yearly `application_kind` toggle).
- 5 equipment-group assessment tabs (AC/Machinery/Escalator/Elevator/Generator Set), each reusing existing BP `MECH_*`/`INSP_*` `FeeType` codes via `addMechanicalUnitItem()`, branching New vs. Yearly to select `MECH_*` vs. `INSP_*` fee codes. New `MECH_GENSET`/`INSP_GENSET` fee types seeded (no existing Generator Set schedule).
- Multi-permit generation: one `Permit` per equipment item (all AC items bundled into a single certificate; one permit each for Machinery/Escalator/Elevator/Genset), via the `MechanicalPermitUnit` bridge table — `PermitController::doGenerateMp()`/`doRevertGenerateMp()`/`doRestoreRevokeMp()` (the latter looping to restore *all* trashed-revoked permits for an application, unlike the shared single-permit `doRestoreRevoke()`).
- `pdf/mechanical-permit.blade.php` (per-unit certificate, AC group itemizes all bundled items) + `pdf/assessment-summary-ai.blade.php`; sidebar entries positioned last (after Fencing Permit); excluded from online self-service + the generic fee-schedule settings editor.
- **This entire module was superseded in the same overall effort** — see the "Annual Inspection (AI) — Rename + Fee Schedule Rebuild" entry below.

### Building Permit Inspection Fees Removed (Electrical & Mechanical) — COMPLETED

- Per explicit request, the BP assessment's `ELEC`/`MECH` tabs no longer compute or display an inspection fee on top of the base permit fee — `addElectricalItem()` dropped its `electrical_inspection_percentage`-based calculation, `addMechanicalItem()` dropped its `resolveInspectionFee()` call; both now hardcode `inspection_fee = 0`. `resolveInspectionFee()` itself was **not** touched (still used by the separate Mechanical Permit / Annual Inspection module).
- Removed the "Inspection" table columns from both BP tabs, gated the shared Summary tab's "Inspection Fees" row behind the MP/AI-only flag, simplified the surcharge computation's dead `inspection_fee` reference, and removed the Electrical/Mechanical/Electronics inspection-fee line items from `pdf/assessment-summary.blade.php` (BP only — the other permit types' assessment-summary PDFs never had this section).
- New `php artisan bp:remove-inspection-fees --dry-run|--run` command retroactively corrects already-assessed/billed/paid BP applications: zeroes `AssessmentItem.inspection_fee`, then cascades the reduction through `Assessment.total_amount` (recomputed via the same formula `doFinalize()` uses), `Billing`/`BillingItem` (recomputed per-fee-code group, which incidentally fixed a pre-existing double-count bug in ELEC's billing-item total), and `Collection`/`CollectionDetail` (only auto-updated when the current `amount_due` still matches the old `Billing.total_amount` — otherwise flagged for manual review, never silently overwritten). Applications already paid in full may show a nonzero `change_amount` after the fix (an overpayment/refund-owed signal) — a real financial consequence, not a bug.

### Annual Inspection (AI) — Rename from "Mechanical Permit" + Fee Schedule Rebuild — COMPLETED

- Full rename: `mechanical_applications` table → `annual_inspection_applications`, `MechanicalApplication`/`MechanicalApplicationController` → `AnnualInspectionApplication`/`AnnualInspectionApplicationController`, `mechanical-applications/*` views → `annual-inspection-applications/*`, `PermitType.code` `MP` → `AI`, morph alias `mp` → `ai`, routes `/assessments/mp/*` → `/assessments/ai/*`, `/permits/mechanical` → `/permits/annual-inspection` — via a dedicated rename migration plus updates across `AppServiceProvider`, `PermitTypeCode` enum, both seeders, `routes/web.php`, and every controller referencing the old names. `MechanicalPermitUnit`/`mechanical_permit_units` renamed to `AnnualInspectionPermitUnit`/`annual_inspection_permit_units` but kept as a dormant, unused table (explicit "leave dormant, don't delete" decision) once multi-permit generation was replaced (see below).
- Rebuilt the assessment tabs around the official "Annual Inspection Fees" rate schedule (previously-dormant `ANN_INSP` fee category) instead of the module's original 5 equipment tabs:
  - Diffed the user's official rate document line-by-line against 49 seeded `AINSP_*` `FeeType`/`FeeSchedule` rows; found and fixed a real bug (Floor Area's 2nd bracket seeded at ₱120 instead of ₱240, final bracket capped instead of open-ended, breaking any area over 1,000 sq.m); rebuilt Refrigeration/Centralized AC/Diesel-Gasoline-Generating-Units as flat brackets; rebuilt Internal Combustion Engines and a new "Water, Sump and Sewage Pumps" code as lump-sum-plus-excess; dropped 2 obsolete items (old "Escalator by kW", "Gas Meter Range"); relabeled 1 existing row (`AINSP_FXV_PUMP`, whose numbers already matched the new document's "Other Machinery" table).
  - Fixed a companion bug in `addAnnualInspectionFeeItem()`'s `range_based` case: the excess formula ignored `excess_every`, multiplying raw excess directly by `excess_fee` instead of `ceil(excess / excess_every) * excess_fee` — broke Floor Area's "every 1,000 sq.m or portion thereof" rule; no-op fix for every other row (`excess_every = 1`).
  - Confirmed via `AskUserQuestion`: tiered brackets use simple single-bracket lookup, not graduated/cumulative summation, matching every other tiered fee in the app.
  - Added a new `AINSP_ELEC` category/tab reusing the existing BP `ELEC_*` schedule by code (`addAnnualInspectionElectricalItem()`, mirrors `addElectricalItem()`'s 3-way branch).
  - Deleted the module's original 5 equipment categories (`AI_AC`/`AI_MACH`/`AI_ESC`/`AI_ELEV`/`AI_GENSET`) from seeder + DB, deleted `addAnnualInspectionUnitItem()` and its route, removed the equipment-tab Blade branch — leaving exactly 4 tabs: `AINSP_GEN`, `AINSP_ELECTRONICS`, `AINSP_MECH`, `AINSP_ELEC`.
  - A full data reset of the 3 existing test AI applications (including 2 already finalized/billed/paid/permitted) was performed via `withTrashed()->forceDelete()` (a plain `->delete()` only soft-deletes on these SoftDeletes-using tables) so they could be reassessed cleanly under the new tab structure.
- Removing the equipment tabs broke the old multi-permit generation logic (it grouped items by the now-deleted categories) — Permit Generation was switched to **single-permit-per-application**, matching every other permit type: `generateAi()`/`revertGenerateAi()`/`restoreRevokeAi()` became thin wrappers around the shared `doGenerate()`/`doRevertGenerate()`/`doRestoreRevoke()`; the old ~115-line `doGenerateAi()` multi-permit builder was deleted. `PermitController::doGenerate()`'s `$morphType` match was missing an `'AI'` arm at one point during this switch — caught before shipping (would have silently created `Permit` rows with `applicationable_type = 'bp'`). `pdf/annual-inspection-permit.blade.php` was rewritten from a per-equipment-unit multi-certificate layout to a single itemized fee table (grouped by the 4 `AINSP_*` categories) with one grand total; `permits/index.blade.php`'s `mechanical` type reverted from its multi-permit display branches back to the standard single-permit-per-row pattern.
- Verified end-to-end via direct DB queries, `php artisan tinker` + `pdftotext` PDF-content checks, and live browser interaction at every stage.

### Annual Inspection — Quantity (Equipment Count) Field + Table Column Split — COMPLETED

- Added a second "Quantity (no. of units)" input, separate from the existing "Unit" (measurement) input, for the 15 Mechanical + 3 Electrical fee codes priced by a continuous physical measurement (kW/ton(s)/kVA/lineal meter(s)/cu.m.) — confirmed via `AskUserQuestion`, explicitly excluding codes already priced as a discrete count (unit(s)/head(s)/outlet(s)/pole(s)/attachment(s)). `amount = round($baseFee * $quantityCount, 2)`, `$quantityCount` stored in `computation_details['quantity_count']` (default 1 elsewhere — zero regression for every other code). Verified in-browser: Boilers @ 50kW × Quantity 3 → Qty column shows 3, amount = 3× the 50kW base fee.
- The assessment items table for the 4 AI tabs was then split into separate **Unit** and **Qty** columns (previously one combined "Qty" column doubled as both the measurement and the count): quantity-eligible codes show the measurement + its unit label in Unit ("50.00 kW") and the real equipment count in Qty ("3"); discrete-count codes show only the unit-of-measurement label in Unit ("unit(s)", "head(s)", no number) and the actual entered quantity (which *is* the count for these items) in Qty. Driven by a per-fee-code eligibility list (`$aiQuantityEligibleCodes`) and a unit-label lookup map (`$aiUnitLabels`), both declared fresh inside the `@foreach($tabCategories as $cat)` loop in `assess.blade.php` to avoid the variable carrying a stale value from a previous category iteration into an unrelated tab's render.
- Removed the earlier "— N unit(s)" description-suffix approach (used before the column split existed) now that the count has its own dedicated column.

### Dashboard — Add DP/FP/SGP/AI to Stats, Charts, Recent Applications — COMPLETED

- `DashboardController::index()` originally only aggregated Building Permit and Occupancy Permit into the KPI cards (Total/Pending/Released/For Payment), the Monthly Transactions chart, and Recent Applications — Demolition, Signage, Fencing, and Annual Inspection (all added in later sessions) were never wired in
- KPI totals now loop all 4 additional permit types via their morph-map keys; the Monthly Transactions chart gained 4 more grouped-bar datasets (same `Collection.applicationable_type` sourcing as BP/OP); Recent Applications merges all 6 types' latest records into one combined, timestamp-sorted list, resolving each type's display name and correct `show`-route link (Annual Inspection uses `owner_name`, since it has no applicant first/middle/last split)
- Verified in-browser: dashboard now shows AI/FP/SGP/DP entries in Recent Applications with working links, and the stat cards/charts reflect all 6 types

### Annual Inspection Permit Generation — Bug Fix Sweep — COMPLETED

- **Billing number counter collision**: `BillingService::generateFor()`'s `billing_number` counter was `count(billings this month) + 1` — collided with an existing (soft-deleted) number whenever the monthly sequence had a gap, throwing a unique-constraint violation that left one application's finalize stuck mid-transaction (the assessment itself finalized in its own transaction; billing generation is a separate call that rolled back independently, leaving `engineering_assessed` status with no billing). Fixed by deriving the next number from the actual max existing `billing_number` for the year/month prefix — cannot collide regardless of historical gaps. Retried the stuck application's billing generation directly; it correctly reached `billed`.
- **Assessment Summary PDF category bug**: `pdf/assessment-summary-ai.blade.php` was still grouping its printed sections by the module's original, since-deleted equipment categories (`AI_AC`/`AI_MACH`/`AI_ESC`/`AI_ELEV`/`AI_GENSET`) instead of the 4 real `AINSP_*` categories that actually hold assessment items — every section printed ₱0.00 regardless of real data. Fixed by correcting the section-code mapping to `AINSP_GEN`/`AINSP_ELECTRONICS`/`AINSP_MECH`/`AINSP_ELEC`; re-verified the PDF now shows the correct ₱7,292.00 total for a real test application.
- **Summary tab Unit/Qty column bug**: the generic Summary tab table (shared across all permit types) rendered one plain Qty/Unit-Fee column pair, unaware of the AI-specific Unit/Qty split already built for AI's own 4 tabs — AI items showed the raw ambiguous `quantity` value in Summary instead of the measurement+label/count split their own tab displayed. Fixed by detecting the 4 AI categories in the Summary loop and reusing the identical split logic (redeclared fresh inside that loop, not relying on cross-loop PHP variable leakage). Verified the Summary tab's Mechanical row now matches the Mechanical tab's own table output exactly.

### Annual Inspection — Switch to Multi-Certificate Permit Generation — COMPLETED

- Reactivated the module's original multi-permit design (dormant since the single-permit interim): AI permit generation now produces **up to 6 certificates per application**, grouped by inspection discipline — 1 bundling General+Electrical (`AINSP_GEN`+`AINSP_ELEC`), 1 for Electronics, 1 bundling the remaining Machinery items (excluding Elevators/Escalators/Aircon-Refrigeration), 1 bundling ALL Aircon/Refrigeration items regardless of count, and one certificate **per unit** for each individual Elevator and each individual Escalator/Funicular/Cable-Car item — confirmed via `AskUserQuestion` (Elevators and Escalators get separate, distinctly-labeled certificate types, not one shared "vertical transport" label). A group with zero assessed items generates no certificate.
- Migration added a nullable `assessment_item_id` FK to `annual_inspection_permit_units` (reactivated bridge table), set only for the two per-unit group types so print-time lookup never has to re-derive "which unit is this."
- New `PermitController::buildAiCertificateGroups()` private helper is the single source of truth for the 6-group derivation, called by generation to create rows and (indirectly, via the frozen `AnnualInspectionPermitUnit` snapshot) by print to render each certificate's content.
- `doGenerateAi()` loops the derived groups once per generation, creating one `Permit` + one `AnnualInspectionPermitUnit` per group with a single shared incrementing counter (every certificate in one action gets a unique `AI-YYYY-MM-NNNNN` number). `revertGenerateAi()`/`restoreRevokeAi()` were rewritten to act on **all** of an application's permits at once (loop revoke/soft-delete, or loop restore) — this is the exact one-at-a-time gap the shared single-permit `doRevertGenerate()`/`doRestoreRevoke()` has always had for every other permit type, now fixed specifically for AI.
- `PermitController::print()`'s AI branch now looks up the specific `AnnualInspectionPermitUnit` tied to the `$permit->id` being printed; `pdf/annual-inspection-permit.blade.php` was rewritten to render one certificate at a time (itemized table for bundle-type certs, single equipment line for per-unit certs) instead of one grand-total table for the whole application.
- `annual-inspection-applications/show.blade.php` regained its "Generated Permits (N)" panel (ported from the original multi-permit build) and `permits/index.blade.php`'s `mechanical` type regained its multi-permit-aware UI ("N permit(s) generated"/"View Permits", pluralized revoke/restore copy) — both had been reverted to single-permit display during the interim single-permit period and are now restored.
- The 3 existing test AI applications (already permit-generated under the interim single-certificate scheme) had their old permits force-deleted via `tinker` (a soft-delete would have left a "revoked permit on file" guard blocking regeneration) so they could be regenerated under the new scheme through the browser.
- Verified end-to-end in-browser: generated 3 certificates (General+Electrical/Electronics/Machinery) for a test application matching its 3 populated fee categories, confirmed correct labels/amounts/OR data, confirmed the bundle-type PDF renders (200 OK), and confirmed Revoke/Restore both act on all 3 certificates atomically in one action.

### Annual Inspection — "Equipment / Items to be Inspected" Checklist — COMPLETED

- New optional section on the AI application form capturing a declared checklist of equipment to be inspected (Elevators, Escalators/Funiculars/Cable Cars, Air Conditioning & Refrigeration, Other Machinery, Electronics Equipment) — confirmed via `AskUserQuestion`: each row's equipment type is chosen from a dropdown of the real equipment-count fee codes already used in assessment (not free text), and at the assessment stage this list is a **read-only reference panel only** (no auto-generation of assessment items) — consistent with how 100% of fee-item entry already works in this app.
- New `annual_inspection_equipment_items` table + `AnnualInspectionEquipmentItem` model, carrying a `CATEGORIES` const (the 5 groups, `fee_code => label`) and `labelFor()`/`allCodes()` static helpers reused by the form dropdown, show page, and assessment panel.
- First live repeatable-row Alpine.js UI in the codebase (an earlier attempt, Fencing Permit's "Add Inspector," was collapsed to a fixed block before shipping, leaving no surviving example) — the row list starts with **zero rows** and is populated only via "+ Add Equipment/Item," synced on `store()`/`update()` via delete-and-recreate.
- The declared list is shown on the application's `show` page and as an amber "Declared Equipment (Basis of Assessment)" panel above the tab bar on the Engineering Assessment page (`assess.blade.php`, visible on every AI tab, zero interaction with the existing manual add-item forms).
- Follow-up refinement: the free-text field beside Quantity was renamed from "Remarks" to **"Specification"** (its real purpose), the underlying column renamed via raw `ALTER TABLE ... RENAME COLUMN` (no `doctrine/dbal` dependency for Laravel's native `renameColumn()`), and Equipment + Quantity were made required per row — safely, since the zero-default-rows design means every row that exists in a submission was deliberately added by the user, so `required`/`required_with:equipment` never blocks a no-equipment submission.
- Verified in-browser: created an application with 0 equipment rows (submits fine, section omitted from show page); added rows with Equipment left blank (native HTML5 validation blocks submission); added a row with Specification left blank (saves with `specification = null`); edited an application with existing rows (pre-fills correctly, add/remove syncs cleanly); confirmed the show page and assessment panel both display "Specification" with correct values.

### Mobile Sidebar Scroll Fix — COMPLETED

- The mobile `<aside>` in `layouts/app.blade.php` was missing `flex flex-col` and its `<nav>` was missing `flex-1 min-h-0`, so `overflow-y-auto` had no bounded height to scroll within on small screens — fixed by matching the desktop sidebar's already-working layout classes.

### Annual Inspection — Mechanical Assessment Spec Fields — COMPLETED

- Added category-specific spec fields to the `AINSP_MECH` add-item form on `/assessments/ai/{id}`, required per category (confirmed via `AskUserQuestion` — the assessor is always looking at real equipment at this stage, so unconditional `required` is safe, unlike the application-time equipment checklist's zero-rows-by-default design): Elevator (Workload in Kilograms, No. of Passengers), Aircon/Refrigeration (Description, Tons or HP), Escalator/Funicular/Cable Car (8 fields: Rated Load, Capacity Per Hour, Speed, Effective Width, Tread Width, Floors Served, Floor Height, Motor Horsepower), Other Machinery (Description).
- New static code-set helpers (`elevatorCodes()`, `escalatorCodes()`, `acRefCodes()`, `otherMachineryCodes()`) added to `AnnualInspectionEquipmentItem`, derived from the existing `CATEGORIES` const, reused by both the controller's category detection and the Blade form's Alpine conditionals.
- `AssessmentController::addAnnualInspectionFeeItem()` runs a second category-specific `$request->validate()` and stores the specs under a new `computation_details['specs']` sub-key; both the AINSP_MECH tab's item table and the shared Summary tab's table gained an extra details row showing the specs when present, using a local label map.
- Fixed two bugs during implementation: Blade's `@json()` doesn't HTML-escape quotes, which silently broke the `x-data` attribute (and all Alpine reactivity on the component) when embedding a JSON array of code strings — switched to the file's existing single-quoted-array-literal convention instead; and Aircon/Other-Machinery's spec fields collided on the same `name="spec_equipment_description"`, so PHP's `$_POST` silently clobbered the visible field's value with the hidden field's — fixed by renaming Other Machinery's field to `spec_machinery_description`.
- Not yet reflected on any printed PDF (assessment summary or certificate) — display is currently scoped to the assessment item list only, per explicit scope confirmation.

### Signatories — 15 Annual Inspection Roles (Edit-Only) — COMPLETED

- Seeded 15 new `ai_*`-prefixed `Signatory` roles for Annual Inspection sign-off (Locational Zoning of Land Use, Line and Grade (Geodetic), Architectural, Civil/Structural, Electrical, Mechanical, Sanitary, Plumbing, Electronics, Interior Design, Accessibility, Fire Safety, Chief Inspection and Enforcement Division, Chief Processing and Evaluation Division, City Engineer) via `updateOrCreate` in `ReferenceDataSeeder`, each with `title` set to the discipline label and `name` left blank for staff to fill in.
- Create/Delete UI and routes were built for `/settings/signatories` and then explicitly reverted at the user's follow-up request ("everything is fixed just edit only") — the existing edit-only flow was deemed sufficient; the 15 seeded rows were kept.

### Annual Inspection — Character of Occupancy (Single-Select) — COMPLETED

- Added a "Character of Occupancy" field to `/annual-inspection-applications/create`/edit, matching Building Permit's field conceptually but implemented as a **single-select radio group** (not BP's multi-select checkbox grid), per explicit follow-up request to convert from an initial multi-select build.
- No migration needed: reuses the existing polymorphic `application_occupancy_groups` table (via `HasPermitApplicationBehavior`'s generic `applicationOccupancyGroups()` relation, already shared by BP/OP/DP/SGP/FP/AI) — single-select is purely an application-logic constraint (write exactly one row) rather than a schema constraint.
- `/annual-inspection-applications/{id}` displays the selection as two separate labeled fields, **Group** and **Subgroup**, in a grid matching the Location Address section's style — replacing an earlier arrow-joined single-line ("Group → Subgroup") display per follow-up request.

### Annual Inspection Test Data Reset — COMPLETED

- All existing Annual Inspection records were force-deleted and replaced with exactly 3 fresh test records, each progressed only through Engineering Assessment (submitted + fee items added, not finalized/billed/paid/permit-generated) — for clean manual testing of the spec-fields and Character-of-Occupancy features above.
- Note: MySQL/MariaDB `AUTO_INCREMENT` doesn't reset on `DELETE` (only `TRUNCATE`), so the 3 new records got fresh non-sequential IDs rather than 1/2/3 — expected, not a bug.

### "General, Occupancy & Electrical" Certificate — NBC Form B-19 Background-Overlay Rebuild — COMPLETED

- Replaced the GE-group Annual Inspection certificate (only — other groups ELN/MACH/ACREF/ELEV/ESC are unaffected) with a pixel-accurate reproduction of the official NBC Form No. B-19 "Certificate of Annual Inspection," using the user-supplied form image as a DomPDF background instead of the shared generic itemized-table template.
- New `pdf/annual-inspection-permit-ge.blade.php`, wired into `PermitController::print()` via an `isAiGe` branch (`$permit->permitType->code === 'AI' && $aiUnit->group_code === 'GE'`) that selects this template and applies a scoped `dpi=200` override instead of the controller's usual `setPaper('a4','landscape')` call, leaving every other template's paper/dpi handling untouched.
- Built iteratively: initially two A4-portrait pages, then collapsed to **one A4-landscape page** using the full source image as background — made possible because A3-landscape (the original two-page composite's dimensions) and A4-landscape share the identical `1:√2` aspect ratio, so the two-page coordinate set was transformed with a uniform `1/√2` scale plus an x-shift for former page-2 fields, rather than being recalibrated from scratch.
- Fixed a background-quality issue: the source PNG was a 256-color indexed/palette image, causing visible banding; converted to a truecolor PNG (`nbc-form-b19-hq.png`) via `imagepalettetotruecolor()`, and confirmed `dpi=200` was needed to avoid DomPDF's default 96dpi softening a background this dense.
- Follow-up rounds: centered the Republic of the Philippines/Province/City letterhead and enlarged all fonts; fixed a remaining label/value overlap on the Name/Character/Located rows (root-caused as a horizontal-clearance problem, not vertical, and fixed by widening the gap rather than chasing an exact tight measurement); enlarged the official logo 1.5x and moved it right; tightened the letterhead's vertical position to sit directly above the pre-printed "OFFICE OF THE BUILDING OFFICIAL" line.
- All overlay text is bound from the saved application/assessment/permit: Owner name, Location, Character of Occupancy Group/Subgroup, the 12 discipline signatories (`ai_locational_zoning` through `ai_fire_safety`), 2 Chief signature blocks, and the Building Official block (read from the permit's immutable snapshot columns, same convention as every other certificate in the app — not a live Signatory lookup).
- Calibration was done via `php artisan tinker` direct-controller rendering (bypassing HTTP) combined with the `Read` tool's native PDF rendering, plus custom PHP GD ruler-overlay images (gridlines + inch labels burned onto a copy of the background) for objective pixel-to-inch measurement instead of eyeballing renders.

### GE Certificate Follow-ups: Performance, Title+Name, Signatory Locking, QR Code, Occupancy Fields — COMPLETED

- **Performance fix**: the background image was swapped from the truecolor PNG (~0.88MB) to a flattened JPEG (quality 90) — the identical DomPDF PNG-embedding slow path already diagnosed and fixed for every other background-overlay form in this app (Structural/Electrical/Sanitary/Fencing permits, unified BP/architectural forms). Render time measured 3.23s before, 1.53s after; `dpi=200` left unchanged; original PNG kept on disk, unreferenced, as a calibration source per the established convention.
- **Peso sign fix**: `Fee Paid` rendered a missing-glyph box instead of ₱ — the same DomPDF/Arial-substitute bug already fixed elsewhere in this app for the exact same `&#8369;` entity. Fixed by leading the font stack with `'DejaVu Sans'`.
- **Multi-round alignment pass**: the seal/logo initially overlapped the page's pre-printed top border (measured via GD pixel sampling — border's true bottom edge is ~0.22in — then moved/shrunk to clear it while staying above the letterhead, iterated twice more per follow-up feedback for additional clearance); the Republic/Province/City letterhead was shifted down to restore a visible gap above "OFFICE OF THE BUILDING OFFICIAL"; and, across several rounds, Fee Paid, Official Receipt No., Date Paid, Date Issued, the permit-number "No." field, Name of Owner/Lessee, Character of Occupancy, Group, and Located at/along were each individually re-measured (GD darkness-scan for each field's true underline row) and nudged to sit cleanly above their lines instead of on/through them. One round of this surfaced a genuine overlap bug: the Group value's `left` position landed directly on top of the pre-printed "Group" label text (label measured at x≈8.84–9.16in, value started at x=8.9in) — fixed by moving the value clear of the label.
- **Title + Name signatory display**: all 14 signatory blocks (12 discipline rows + 2 Chief blocks) previously rendered `Signatory.name` only, dropping each row's populated `title` field (e.g. "Engr", "Arch", "SP"). New `$sigFull()` closure renders `"{title} {name}"` for all 14; the old name-only `$sig()` closure became fully dead code and was removed.
- **Signature dates removed**: the 2 Chief blocks' and Building Official's auto-filled "Date" lines (previously sourced from `$permit->issued_date`) were removed at the user's request — these are meant to be filled in by hand when physically signed, matching the convention already used for every other hand-signed date on this certificate and elsewhere in the app.
- **Signatories locked at permit-generation time**: previously all 14 discipline/Chief signatories were resolved via a **live** `Signatory::where('is_active', true)->get()->keyBy('role')` lookup re-run on every print — editing a signatory's name/title after a permit was generated silently changed what printed on every past and future reprint of that certificate, unlike Building Official (already snapshotted into `permits.building_official_*` at generation time). New nullable JSON column `permits.signatories_snapshot` (`{role: {title, name}}`) is populated once in `doGenerateAi()`, scoped to the 14 `ai_*` roles only (no other permit type or template reads them); `$sigFull()` reads the snapshot first, falling back to the live lookup only when it's null (permits generated before this feature existed). Verified end-to-end: generated a fresh permit, confirmed its snapshot captured all 14 roles' current values, edited a live signatory's name, then reprinted both the new permit (kept the old locked name) and an older pre-lock permit (correctly picked up the live edit) — both behaviors exactly as designed.
- **Verification QR code added**: the certificate gained a scannable QR code linking to the same public `/verify/permit/{token}` page every other permit type already uses. No controller changes were needed — `$qrImage` was already being computed and passed into every permit template by the single `PermitController::print()` call, just never rendered by this particular template. Placed in a large blank area at the bottom-left of the right half (located via a GD blank-space grid scan across the background), with no caption, matching the Building Permit's existing QR styling; moved right on a follow-up request so it sits clear of the page's decorative border and roughly under the certificate's body-paragraph column.
- **Occupancy No. / Issued Date fields** (new small feature in the same continuation): two optional fields, `annual_inspection_applications.occupancy_no` (string) and `.occupancy_issued_date` (date), added via migration; wired into the AI application create/edit form under Character of Occupancy, shown on the show page only when set, and bound into the GE certificate's "Certificate of Occupancy No. ___ issued on ___" line — previously left permanently blank since no data source existed for it. The line's exact blank-segment positions (a "No." blank and a separate "issued on" blank on the same wrapped paragraph row) were found the same GD pixel-scan way as every other field on this template.
- **Verify-permit page bug fix**: `/verify/permit/{token}` labeled every non-OP/DP/SGP permit "Building Permit" regardless of actual type — its `match($permit->permitType->code)` only explicitly listed `OP`/`DP`/`SGP`, so both `FP` and `AI` silently fell into the `default` case. Reported for an AI permit; fixed by adding `'FP' => 'Fencing Permit'` and `'AI' => 'Annual Inspection Permit'` — the FP gap was caught and fixed incidentally since it had the identical bug.
- Full 3-test-application seed re-run (all form fields including new optionals, assessment items across all 4 tabs with full spec data, finalize → bill → pay → generate 6 certificates each → print all 18) confirmed the whole pipeline still works end-to-end after every fix above; `storage/logs/laravel.log` checked clean after each change (aside from one pre-existing, unrelated stale error from a prior session).

---

## Upcoming Tasks

| Task | Priority | Notes |
|------|----------|-------|
| Fix Create/Edit User form (role select + blank-field crash) | High | Currently unusable end-to-end — see "Staff Account Password Complexity" above |
| Signage Permit fee schedule + application-form print | Medium | `SGP_FEE` category exists but has no seeded rates (manual entry only); no scanned official application form supplied yet for the background-overlay print |
| Additional permit types (EP, ELP, PP, ECP) | Medium | BP, OP, DP, SGP, FP, and AI (formerly MP) are now active; the rest remain unbuilt placeholders in `permit_types` |
| Document requirement upload UI | Low | Model/route exists, UI needs improvement |
| Email notification configuration | Low | SMTP settings, notification templates |
