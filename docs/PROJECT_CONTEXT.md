# Project Context

## Overview

The **Engineering Permit Management System (EPMS)** is a web application for the City of San Fernando, La Union, Philippines. It manages the full lifecycle of building and occupancy permits — from application intake through assessment, billing, payment, and permit issuance.

This system replaces the legacy BOPMS (Building and Occupancy Permit Management System).

---

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend | PHP 8.2+ / Laravel 12 |
| Database | MariaDB 12.3 |
| Frontend | Blade + Tailwind CSS (CDN) + Alpine.js (CDN) |
| Charts | Chart.js |
| PDF | DomPDF (barryvdh/laravel-dompdf) |
| Barcode | picqer/php-barcode-generator (Code 128) |
| QR Code | endroid/qr-code (permit verification) |
| Excel | Maatwebsite Excel |
| RBAC | Spatie Laravel-Permission |
| Audit | Spatie Laravel-Activitylog |
| Auth | Laravel Breeze (customized) |

---

## Architecture Decisions

### Service Layer Pattern
Business logic lives in `app/Services/`, controllers are thin. Services handle: application creation/numbering, assessment finalization, billing generation, payment processing, permit generation, fee computation.

### DTOs for Data Transfer
`app/DTOs/` contains readonly classes for type-safe data transfer from requests to services (`ApplicationDTO`, `AssessmentItemDTO`, `CollectionDTO`).

### Action Classes
`app/Actions/` wraps complex multi-step operations: `CreateApplicationAction`, `FinalizeAssessmentAction`, `GeneratePermitAction`. Actions call services, log activity, and handle state transitions.

### Separate BP and OP Tables with Polymorphic Relationships
Building Permit (BP) and Occupancy Permit (OP) applications live in separate database tables: `applications` (BP only) and `occupancy_applications` (OP only). Seven downstream tables (assessments, billings, collections, permits, documents, application_requirements, application_occupancy_groups) use polymorphic columns (`applicationable_type`, `applicationable_id`) to reference either model. A morph map (`bp` → Application, `op` → OccupancyApplication) is registered in `AppServiceProvider`.

### Interface + Trait Pattern for Shared Behavior
`app/Contracts/PermitApplicationContract.php` defines the shared interface. `app/Concerns/HasPermitApplicationBehavior.php` provides a trait with shared accessors and polymorphic relationships. Both `Application` and `OccupancyApplication` implement the contract and use the trait, keeping BP-specific logic in `Application` only.

### Enum-Based State Machine
`app/Enums/ApplicationStatus.php` defines the complete workflow with `allowedTransitions()` and `allowedTransitionsFor(string $permitTypeCode)` for strict state validation.

### Consolidated Fee Schedule
BOPMS had 100+ individual fee tables. Engineering-app consolidates into 3 tables:
- `fee_categories` — groups by permit type (CONST, ELEC, MECH, PLUMB, etc.)
- `fee_types` — individual fee items with computation method
- `fee_schedules` — rate rows with ranges, fixed fees, excess thresholds

Six computation methods: `fixed`, `per_unit`, `range_based`, `cumulative_range`, `percentage`, `formula`.

### MECH_INSP Fee Category (Hidden)
A special `MECH_INSP` fee category holds 29 `INSP_*` fee types that mirror BOPMS `ann_inspection_f*` tables — the NBC mechanical permit inspection fee rates. This category is excluded from the assessment tab bar; inspection fees are auto-computed by `AssessmentController::resolveInspectionFee()` whenever a mechanical item is added. `amount` stores the base permit fee only; `inspection_fee` stores the NBC inspection fee separately so the grand total formula (`sum(amount) + sum(inspection_fee)`) works correctly across all categories.

### Dedicated Zoning Fee Tables
Zoning fees use dedicated tables matching BOPMS naming:
- `land_use_and_zoning_fees` — locational clearance fees by occupancy sub-group with range-based + excess computation (162 rows across 52 sub-groups, 6 fee patterns)
- `certification_zoning_fees` — flat certification fee (P500)
- `land_use_and_zoning_other_fees` — Variance/Non-Conforming fees

Auto-compute in `ZoningController::autoCompute()` queries these tables directly, matching BOPMS `TransactionController::zoningAutoCompute()` logic.

### BOPMS-Style Assessment Tabs
The BP assessment page uses tabbed navigation with fee category tabs plus a Summary tab. Each implemented tab matches the original BOPMS UI:

- **Construction tab** — Part of Building + Division (filtered by occupancy groups) + Area → server-side fee lookup. Formula: `amount = area × fee_per_unit`.
- **Electrical tab** — 7 fee types with conditional fields. Range-based kVA: `amount = fixed_fee + (kva × fee_per_unit)`. Inspection fee = `base × electrical_inspection_percentage` (setting, default 10%). Total = base + inspection.
- **Mechanical tab** — Select mechanical equipment type + unit count → auto-computes base permit fee (MECH schedules) + NBC inspection fee (MECH_INSP schedules via `resolveInspectionFee()`). Three inspection formulas: flat (range band), per_unit (rate × count), tiered (cumulative for elevators). `amount` = base only; `inspection_fee` stored separately.
- **Plumbing tab** — 22 PLUMB_* fee types (installation / fixtures / special fixtures / range-based), dynamic unit label per type.
- **Electronics tab** — 11 ELECT_* fee types with per-type unit labels.
- **Accessories (ACC_BLDG), Accessory Fees (ACC_FEE), Surcharge (SURCHARGE) tabs** — dedicated BOPMS-style forms and add methods.
- **Occupancy Fee tab (OP assessment)** — 8 OCC_* fee types; Unit label switches by type (Costing ₱ / Area sq.m / Amount ₱ / Meters-Units). Server-side computation honors `range_based` (with `excess_every` "per ₱1M or fraction thereof"), `per_unit`, and `percentage` methods.
- **Other tabs** — Generic fallback form: select Fee Type, enter Quantity + Unit Fee.

### Assessment Finalization Locking
Finalize requires password confirmation and redirects to the Summary tab. Once finalized, all mutating actions are blocked at both UI level (forms/buttons hidden, amber banner shown) and server level (`AssessmentController::redirectIfFinalized()` for BP/OP items; `ZoningController::abortIfZoningFinalized()` returns 403 for zoning).

### Assessment PDF (Summary of Computation)
`doPrint()` dispatches by permit type. BP renders `pdf/assessment-summary` (Zoning + Building sections). OP renders a separate `pdf/assessment-summary-op` titled "OCCUPANCY PERMIT ASSESSMENT" with only an Occupancy Fees section — no Zoning, Building, Electrical, Mechanical, Other Fees, Filing, or Processing, since none of those apply to an occupancy assessment. Both templates render a real Code 128 barcode (picqer/php-barcode-generator, base64 PNG for DomPDF) above the application number, and "Approved By" sourced from the `signatories` table (`role = building_official`). Fire Code Fees are excluded from the BP template (BFP is out of scope).

### Billing Is Auto-Generated, Not a Manual Step
There is no Billing menu or manual "Generate Billing" action. `AssessmentController::doFinalize()` calls `BillingService::generateFor(PermitApplicationContract)` immediately after an assessment is finalized, which creates the `billings` + `billing_items` records and moves the application straight from `engineering_assessed` to `billed`. `BillingController` only serves the billing statement PDF (`billing.print`). Because finalized applications now sit at `billed` instead of stopping at `engineering_assessed`, the assessment index queries and Print-button visibility checks include both statuses.

### Collections: Barcode Scan & POS-Style Payment Form
`/collections` has an autofocused search box: scanning the barcode from a printed assessment (which encodes the application number) exact-matches a billed application and redirects straight to its payment form; partial text filters the Awaiting Payment list. The payment form itself is a compact, single-screen layout — a 3-column Amount Due/Amount Received/Change strip (live Alpine calculation, switches to a red "Short" warning if underpaid, and the server rejects an insufficient cash payment) plus a segmented Cash/Check/Online control and a sticky bottom action bar — designed so a collector never has to scroll mid-transaction. The Awaiting Payment query (and the barcode/exact-match redirect) also excludes any application that already has an **active** `Collection` record, as a defensive guard against a `status` column that didn't transition cleanly to `paid`.

The Payment History table ("My Collections") is scoped to the **logged-in collector's own** transactions (`collected_by = Auth::id()`) and filtered by month via an auto-submitting `<input type=month>` picker (`?month=`, defaults to the current month). The header's "Void Collection" button was removed (the `/collections/void` route itself remains).

### Permit PDF Redesign (NBC/DPWH Form Style)
`pdf/building-permit.blade.php` and `pdf/occupancy-permit.blade.php` were rebuilt to match the real government forms they represent — NBC Form No. B-018 (Building Permit) and the DPWH Certificate of Occupancy — instead of a generic table layout. Both share the same DomPDF technique: A4 landscape, `@page { margin: 0.5in }` with the CSS reset scoped to `body, div, p, span, img` (never `*`/`html`, which silently wipes DomPDF's `@page` margin), and a thick double-line border (`.frame { border: 6px double }`) tuned so the frame sits exactly 0.5in from all four page edges without spilling to a spurious second page.
- **Building Permit**: city seal (left, from the `general.logo` setting) beside a centered header ("Republic of the Philippines / {city} / {province} / OFFICE OF THE BUILDING OFFICIAL"), NEW/RENEWAL/AMENDATORY checkboxes, FSEC No./Date Issued fields, ZIP Code from the `general.zip_code` setting.
- **Occupancy Permit**: DPWH gear logo (left, static asset `public/images/dpwh-logo.png`) + city seal (right) flanking the centered header, FULL/PARTIAL checkboxes driven by the application's `applicationType` (Full/Partial), FSIC No. field, "Group" from the occupancy group's `code`.
- Both templates embed a QR code (see below) near the signature block, with blank space above the printed signatory name for a physical signature.

### QR Code Permit Verification
Every generated `Permit` gets a `verification_token` (UUID, unique) set in `PermitController::doGenerate()`. `PermitController::print()` builds a verification URL — `{domain}/verify/permit/{token}`, where `{domain}` comes from the `general.domain` setting (falls back to `config('app.url')` if blank) — and renders it as a QR code (`endroid/qr-code`) embedded on both PDF templates. `GET /verify/permit/{token}` is a public, throttled route (`VerifyController::show`, no auth) that renders `resources/views/verify/permit.blade.php`: a standalone page showing permit type, number, status, applicant, and project for a valid token, or a graceful "could not be verified" message for an invalid one.

### Revert / Send-Back Actions (Backward Workflow Transitions)
Every forward-only workflow step now has a matching backward action, each gated by its own permission and a password-confirmation modal (`Hash::check()` against the acting user's password), following the same UX pattern as finalize:
- **Submission revert** — `ApplicationController::revertSubmission()` / `OccupancyApplicationController::revertSubmission()` (`revert-submission` permission) send a submitted application back to `draft` from its Show page.
- **Zoning revert** — `ZoningController::revertZoning()` (`revert-zoning`) un-finalizes a zoning assessment. `ZoningController::sendBackForEditing()` (reuses `revert-submission`) sends an application back from Engineering to Planning for zoning re-work.
- **Engineering assessment revert** — `AssessmentController::revertEngineering()` / `revertEngineeringOp()` (`revert-assessments`) un-finalize a BP/OP engineering assessment. `AssessmentController::returnToZoning()` (`return-to-zoning`) sends a BP application from Engineering back to Planning, deleting its engineering assessment items. `AssessmentController::revertToDraftOp()` (reuses `revert-submission`, OP only) reverts an in-progress OP assessment (status `zoning_assessed`, not yet finalized) all the way back to `draft`, deleting all occupancy fee entries — a dedicated action separate from the plain status-revert, since it also purges fee data.
- **Permit generation revert** — `PermitController::revertGenerate()` / `revertGenerateOp()` (`revert-permits`) soft-delete a generated `Permit` and roll the application status back to `paid`.

All revert actions soft-delete the records they remove (never hard-delete) and write an `activity()` log entry, consistent with the rest of the app's audit trail. Buttons for these actions in `assessments/assess.blade.php` live in the page **header** (not inside a tab-gated Summary pane) so they're visible immediately regardless of which fee-entry tab is active by default.

### Application List UX: Turn Around Time, Year Filter, Status Labels
`/applications` and `/occupancy-applications` add:
- **Year filter** (`?year=`, defaults to the current year) — `whereYear('created_at', $year)`, dropdown offers current + previous year only.
- **Turn Around Time column** — per row, `submitted_at` (falling back to `created_at`) → the latest `Permit`'s `created_at` (via the eager-loaded `permits()` relation), shown as whole days (`–` if no permit generated yet). Computed in the view, not persisted — no new columns. Note: Carbon 3's `diffInDays()` defaults to non-absolute and can return a negative float, so the calculation forces `diffInDays($end, true)` and floors to an int.
- **OP-specific status labels** — since Occupancy Permits have no zoning/planning stage, `zoning_assessed` is relabeled "For Occupancy Assessment" everywhere it's displayed for OP (both `occupancy-applications/index.blade.php` and `assessments/occupancy-index.blade.php`), instead of the generic BP-oriented "Zoning assessed".
- OP index shows **Project Title** (not applicant address) as its own column alongside Status.

### On-Demand Barangay Lookup (GeoController)
The ~42K-row barangay dataset used to be shipped in full to the BP/OP application form and filtered client-side by city. `GeoController::barangaysForCity(City $city)` (`GET /geo/barangays/{city}`) now returns only the active barangays for the selected city, fetched via Alpine on `@change` of the City select (and on `init()` if a city is already selected, e.g. editing an existing application). This avoids embedding the full barangay list in every form page load.

### Dashboard: Year-Navigable Charts
The dashboard's two Chart.js charts (Monthly Revenue, Monthly Transactions — the latter a grouped BP-vs-OP bar chart sourced from `Collection.applicationable_type`) accept an optional `?year=` query param (`DashboardController::index()`), clamped so it can never exceed the real current year. Prev/Next arrows above the charts let a user page back through prior years' monthly breakdowns. The KPI stat cards above the charts (Total Applications, Pending, For Payment, Released, Revenue, Today's Transactions) are intentionally **not** affected by `?year=` — they always reflect the live/current period, per explicit design choice.

### Unknown-URL Fallback & Session-Expired Redirect
`Route::fallback()` (end of `routes/web.php`) catches any unmatched URL and redirects to the role-appropriate home (client → `online.dashboard`, staff → `dashboard`) if authenticated, or `login` if a guest — the same logic already used by the `/` route. Separately, `bootstrap/app.php`'s `withExceptions()` renders any `HttpException` with status 419 (CSRF token mismatch / expired session — Laravel maps `TokenMismatchException` to a generic `HttpException(419, ...)` before render callbacks run, so the callback must match on status code, not the original exception class) by redirecting to `staff.login` or `login` (detected via `$request->is('staff/*')`) with a flashed "Your session has expired" status message, instead of the default whitesreen "Page Expired" page.

### Permit Revocation: Retain, Block Regeneration, Restore
Revoking a generated permit (`PermitController::doRevertGenerate()`) tags the `Permit` row's `status` as `revoked` (with `revoke_reason`, required) and soft-deletes it — the row and its permit number are preserved, never overwritten. The application drops back to `paid`, leaving payment/collection records untouched. Unlike the original implementation, `doGenerate()` now refuses to create a brand-new permit for an application whose only permit was revoked (`permits()->onlyTrashed()->where('status', 'revoked')->exists()`) — the "Generate Permit" button in `permits/index.blade.php` is replaced entirely by a **Restore Permit** button (password-confirm only, no reason — same convention as every other revert/undo action in the app). `PermitController::restoreRevoke()` / `restoreRevokeOp()` un-trashes the exact same `Permit` row, sets its `status` back to `generated`, and returns the application to `permit_generated` — the original permit number is never regenerated with a different number.

### Permits List Redesign (`/permits/building`, `/permits/occupancy`)
`permits/index.blade.php` (shared by both BP and OP) gained:
- **Filters** — Search (app number/applicant/project title), Status (Paid, Permit generated, Released, **Revoked** — the latter matched via `status = 'paid'` + a trashed permit with `status = 'revoked'`, since the application's own `status` column doesn't distinguish "never generated" from "revoked"), Year (defaults to current year, like the application indexes).
- **Permit No. is now the primary column** (replacing the old first "Application No." column) — links to the application's Show page but displays the permit number; shows the revoked permit's number in red strikethrough for revoked rows, or `-` if no permit has ever been generated.
- **TTA column** — same submitted→generated day-count logic as the application indexes, added beside Date.
- **"Permit Revoked" status badge** (red) shown instead of the generic "Paid" badge when an application's only permit is a revoked one.
- Action button labels shortened to drop the redundant word "Permit" ("Restore", "Generate", "Print", "Revoke") since the column header/context already says Permit No.

### Building Official Snapshot on Permit Generation
`permits` gained 4 nullable snapshot columns (`building_official_name`, `_title`, `_designation`, `_license_no`), populated once by `PermitController::doGenerate()` from the currently-active `Signatory` (`role = building_official`) at the moment a permit is generated — and never re-fetched afterward, including through revoke/restore. Both printed PDF templates (`pdf/building-permit.blade.php`, `pdf/occupancy-permit.blade.php`) and the public verification page (`verify/permit.blade.php`, new "Issued By" row) read from this snapshot instead of the live `Signatory` row. This means changing the Building Official in Settings only affects permits generated *after* the change — every previously-generated permit keeps showing whoever signed it at the time, both when reprinted and when verified. Pre-existing permits were best-effort backfilled with the then-current official in the same migration.

### Permit Report Enhancements (`/reports/permits`)
The Permit Report (PDF via `pdf/report.blade.php` and Excel via `PermitReportExport`) now: filters to only `permit_generated` or revoked (`paid` + a trashed revoked permit) applications, matching the same status semantics as the Permits List; adds **Permit No.** and **TTA** columns; combines the Date column into an application-date → permit-date range (e.g. "Jul 06, 2026 - Jul 07, 2026"). The peso sign (₱) was rendering as a missing-glyph box in the PDF because DomPDF's default Helvetica/Arial substitute font doesn't include the U+20B1 glyph — fixed by switching the report's `font-family` to `'DejaVu Sans'` (bundled with DomPDF, confirmed via `FontLib` glyph-map inspection to cover the peso sign), the same general fix applicable to any other PDF template hitting the same missing-glyph issue.

### DPWH Logo Setting
A second file-type setting, `general.dpwh_logo`, was added alongside the existing `general.logo` (city seal) so the DPWH logo on the printed Occupancy Permit is admin-configurable instead of a hardcoded static asset (`public/images/dpwh-logo.png`, kept as the fallback when the setting is empty). This surfaced a latent bug in `SettingsController::update()`: every file-type setting upload was hardcoded to the same storage path (`logos/city-seal.png`), meaning uploading a DPWH logo would have silently overwritten (or been overwritten by) the city seal file — fixed by deriving the storage path per setting key (`match ($key) { 'general.logo' => ..., 'general.dpwh_logo' => ..., default => ... }`).

### Staff Account Password Complexity
`SettingsController::storeUser()` now enforces the same password policy used everywhere else in the app (`Password::min(8)->mixedCase()->numbers()->symbols()`, `confirmed`) and actually uses the admin-supplied password — previously the create-user form collected a password + confirmation but the controller silently discarded both and hardcoded every new account to `password123`. `settings/user-form.blade.php` also gained the same live strength bar, complexity checklist, and match indicator already used on the client registration page (`auth/register.blade.php`), reusing the identical Alpine.js scoring logic, plus show/hide toggles on both password fields. `must_change_password` still forces a change on first login regardless, as defense in depth. **Known pre-existing bugs found (not fixed, tracked separately):** the role `<select>` sends numeric role IDs but validation expects role names (`exists:roles,name`), and `User::create()` crashes with "Undefined array key" whenever `middle_name`/`phone`/`department`/`position` are left blank — together these currently make the Create/Edit User form unusable end-to-end regardless of the password fix.

### Login Password Visibility
Client login (`auth/login.blade.php`) and staff login (`auth/staff-login.blade.php`) gained the same show/hide password toggle already present on the registration page, using the same vanilla-JS `togglePassword(inputId, btn)` pattern (each page defines its own local copy, per the existing convention).

### Printed Permit Footer Note
Both permit PDF templates append a small `.generated-note` line below the existing legal footer note: *"This is a computer-generated permit. Printed on: {date} | Printed by: {logged-in user's full name}"* — computed fresh every time the PDF is rendered (not stored), so a reprint always shows the current print date/user. Fitting this extra line back onto Building Permit's single fixed-height page (`.frame { height: 6.82in }`) required trimming a few other vertical margins (signature block spacing, footer-note margins) by a matching amount.

### Unified Application Form (BP) — Background-Image Overlay
`pdf/application-form.blade.php` (`ApplicationController::printForm()` — **BP only** since the OP form got its own template, see below) is a browser-print HTML page, not a DomPDF template like the rest of `resources/views/pdf/` — it's opened directly with a print toolbar (`window.print()`). It was rebuilt from a semantic HTML/table replica of the official "Unified Application Form for Building Permit" into a **background-image overlay**: `public/images/forms/unified-bp-form-p1.png` / `-p2.png` are scans of the actual 2-page government form set as full-page CSS backgrounds (`print-color-adjust: exact` so the browser actually prints them), with ~84 dynamic fields (owner/applicant data, scope-of-work and occupancy-group checkmarks, costs, dates, signatories) absolutely positioned on top in inch units — the image supplies every border, label, and printed checkbox outline; the overlay renders only the data and a checkmark where applicable. No PDF-rasterization tool (Ghostscript/ImageMagick/poppler) was available on the dev machine; the background PNGs were produced via Windows' built-in WinRT `Windows.Data.Pdf.PdfDocument` API from PowerShell, and field coordinates were calibrated by scanning the source PNG for exact pixel positions with PHP GD rather than eyeballing screenshots.

Both background scans were later replaced with cleaner versions and the overlay recalibrated:
- **Page 1** (1700×2800, 8.5×14in Legal source printed at true scale on 8.5×13in long bond) no longer has a pre-printed header — the letterhead is now an overlay: Official Seal (`general.logo`) top-left, National Government Logo (`general.national_govt_logo`) top-right, and "Republic of the Philippines / {general.city} / Province of {general.province}" centered between them. The Area No. digit box is filled from `application.area_number`, falling back to the `general.area_number` setting (the fixed LGU district code). The Enterprise Name overlay was removed — pixel measurement showed the "FOR CONSTRUCTION OWNED BY AN ENTERPRISE" label fills its entire cell on the printed form, so any overlay there printed on top of the label text. Narrow ID/Place-Issued fields use `text-overflow: ellipsis` at tuned font sizes so long values end cleanly instead of being hard-clipped.
- **Page 2** (1700×2600 — exactly 8.5×13in, so it gets its own `background-size: 8.5in 13in` override instead of page 1's Legal-crop sizing) replaced the Building Official signature block with "SIGNATURE OVER PRINTED NAME OF OWNER/APPLICANT"; the overlay now prints the applicant's name centered directly above that line (position pixel-matched to the scan), and the Building Official overlay was removed entirely.

### Occupancy Permit Application Form — Dedicated DomPDF Template
`occupancy-applications/{id}/print` previously reused the BP `application-form` view and crashed (`$application->permitType->code` — `OccupancyApplication` has no `permitType` relation, plus dozens of BP-only fields). It now renders its own `pdf/occupancy-application-form.blade.php` via DomPDF (A4 portrait, 0.75in margin + `.content` padding, same pattern as `locational-clearance`), reproducing the official "Unified Application Form for Certificate of Occupancy": two-logo header (Official Seal left, National Government Logo right, Republic/City/Province centered), FULL/PARTIAL checkboxes driven by `applicationType->name`, FSIC checkbox from `fsic_no`, BP/FSEC reference numbers, applicant/project details, a 5-item static (unchecked) requirements checklist, and a two-column signatory block — left: "Inspected by:" over a blank signature line with the `building_official` Signatory's name/designation below; right: "Submitted by:" with the applicant's name over its line, CTC fields, then "Attested by: / FULL-TIME INSPECTOR OR SUPERVISOR OF CONSTRUCTION" over a blank line captioned "ARCHITECT OR CIVIL ENGINEER (Signed and Sealed Over Printed Name) / Date___" and a PRC/PTR/TIN/CTC table (blank — no such fields exist on `OccupancyApplication`). The signatory layout was iterated several times against user-supplied reference mockups; both columns' signature lines are kept vertically aligned by mirroring the same structure/margins.

### Dynamic Branding Everywhere (Seal, Logos, Favicon)
`Setting::general()` and `Setting::imageDataUri()` (static helpers on the `Setting` model) centralize the "fetch general settings + build a base64 data-URI from an uploaded file setting" pattern. An audit found several official documents with no seal, or with a controller that never passed the variables its template expected — all fixed so changing a logo in Settings → General propagates to every printed document:
- **Official Receipt** (`CollectionController::receipt`), **Billing Statement** (`BillingController::print`), **BP/OP Assessment Summaries** (`AssessmentController::doPrint`/`doPrintOp`), and **Evaluation Report** (`PermitController::evaluationReport`) now all render the Official Seal in their headers (none had any branding before).
- **Client-portal permit download** (`OnlineApplicationController::doDownloadPermit`) rendered the same `building-permit`/`occupancy-permit` templates as `PermitController::print()` but passed **none** of `settings`/`sealImage`/`dpwhLogo`/`qrImage` — the downloaded PDF silently lost its seal, logo, QR code, and city/province text. Now fully wired, identical to the staff print path.
- **Building Permit PDF** header's empty right spacer cell now renders the DPWH logo (the controller always built `$dpwhLogo`; the template just never used it).
- **Browser favicon**: `partials/favicon.blade.php` (included in `layouts/app`, `layouts/guest`, `auth/staff-login`, `verify/permit`) resolves `general.favicon` → `general.logo` → static `favicon.ico`, replacing the default Laravel icon on every page.

### Self-Healing Service Provider
`SelfHealingServiceProvider` auto-creates database, runs migrations, and seeds roles/settings/admin if missing on every application boot.

### Spatie Permission (RBAC)
9 roles with 30+ granular permissions. Each route protected with `middleware('can:permission-name')`.

### Spatie Activitylog (Audit Trail)
Activity logging on Application, Assessment, Collection, Permit models.

---

## Environment Setup

| Setting | Value |
|---------|-------|
| Server | XAMPP on Windows 11 |
| MariaDB | `C:\Program Files\MariaDB 12.3\bin\` |
| DB Name | `epms_db` |
| DB User/Pass | root / sfcity98 |
| App URL | http://localhost:8100 (artisan serve) |

```bash
php artisan serve --port=8100
```

---

## Default Credentials

| Portal | Email | Password | Role |
|--------|-------|----------|------|
| Staff | admin@epms.local | password123 | administrator |

> Must change password on first login.

---

## Roles & Permissions

| Role | Scope |
|------|-------|
| super-admin | ALL permissions |
| administrator | All except online-* |
| engineering-officer | Applications, assessments, billing, permits, reports |
| engineering-staff | Applications (view/create/edit), assessments (view/create/edit) |
| planning-officer | Applications (view), zoning (all), reports |
| planning-staff | Applications (view), zoning (view/create/edit) |
| treasury-officer | Applications (view), billing, collections (all), reports |
| treasury-staff | Applications (view), billing (view), collections (view/create) |
| client | Online portal: apply, upload, track, download |

---

## Key Design Principles

- **Thin controllers** — Business logic in services, not controllers
- **Soft deletes** — All transaction tables use soft deletes for audit trail
- **Activity logging** — All major model changes tracked
- **State machine** — Strict workflow validation via enum-based transitions
- **No BFP module** — there is no fire-safety assessment/inspection workflow. FSEC No./Date Issued (BP, OP) and FSIC No. (OP) are simple reference text/date fields shown on printed permits, entered manually — not a validated BFP integration
- **Separate portals** — Staff login (`/staff/login`) and client login (`/login`) are separate
- **CDN dependencies** — Tailwind CSS and Alpine.js loaded via CDN (no build step)
