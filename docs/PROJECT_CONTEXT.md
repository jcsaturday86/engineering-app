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

### Demolition Permit (DP) and Signage Permit (SGP) as Full Parallel Workflows
Two more permit types were added on top of the same `PermitApplicationContract`/`HasPermitApplicationBehavior` pattern, each with its own table (`demolition_applications`, `signage_applications`), model, controller, and morph-map alias (`dp`, `sgp`). Both skip Zoning entirely (same 5-step shape as OP: `draft → submitted → engineering_assessed → billed → paid → permit_generated → released`), and every downstream stage — Assessment, Billing, Collection, Permit generation — reuses the exact same generic private methods BP/OP already share (`doAssess`, `doCreate`, `doStore`, `doGenerate`, `BillingService::generateFor()`, etc.); the `*Dp()`/`*Sgp()` controller methods are thin one-line wrappers, not duplicated business logic.

DP mirrors an official NBC form (NBC Form No. B-08) and carries a full field set — enterprise/ownership, CTC/notarization, Location of Demolition Works, Full-time Inspector/Supervisor, Lot Owner Consent — plus a dedicated `DEMO_FEE` fee category (6 real fee types with seeded rates and a per-type `unit_label`, auto-computed via `addDemolitionItem()`) and all three PDF outputs (application form, assessment summary, final permit).

SGP's form is deliberately minimal (applicant name/address, three Scope-of-Work checkboxes with detail text, Wordings, Premises Of) since no official form or fee schedule was supplied at build time. Two scope decisions were made explicit with the user rather than assumed: **fees are manual-entry only** (an empty `SGP_FEE` category exists so the Assessment tab renders, but with no seeded `FeeType`/`FeeSchedule` rows it falls through to the same generic "pick category, type quantity + unit fee" form every category used before a dedicated fee-schedule form existed — the same path DP itself used before `addDemolitionItem()` was built), and the **application-form print is deferred** (no scanned official form yet, so `SignageApplicationController` has no `printForm()`/print route at all) — the assessment-summary and final-permit-certificate PDFs are both complete, cloned from DP's plain-DomPDF and bordered-frame-certificate templates respectively.

Building SGP surfaced a class of bug worth calling out: several controllers/views branch on permit type via a `match ($application->getPermitTypeCode()) { 'OP' => ..., 'DP' => ..., default => ... }` (or the reverse, `match($permit->permitType->code)`) — every one of these blocks needs a matching arm for *every* permit type, or the `default` case silently misroutes. Four such blocks were found missing an `SGP` arm during end-to-end browser verification (`BillingService::generateFor()` — created the `Billing` row with the wrong `applicationable_type`, orphaning it from the application entirely; `collections/index.blade.php`'s pay-button route and type badge; `verify/permit.blade.php`'s type label), and one — `permits/index.blade.php`'s permit-number link, hardcoded to the BP `applications.show` route regardless of `$type` — turned out to be a **pre-existing bug that had also been silently affecting DP** since it was first built, not something introduced by the SGP work. All five were fixed to branch by permit-type-code/`$type` consistently across all 4 active permit types (now 5, with the later addition of FP — see below).

### Fencing Permit (FP) — Fifth Parallel Workflow, and an Inspector-Section Design Iteration
A fifth permit type, Fencing Permit, was added following the same `PermitApplicationContract`/`HasPermitApplicationBehavior` pattern as DP/SGP: its own table (`fencing_applications`), model (`FencingApplication`), controller (`FencingApplicationController`), and morph-map alias (`fp`). Like DP/SGP it skips Zoning entirely (`draft → submitted → engineering_assessed → billed → paid → permit_generated → released`) and reuses the same generic `doAssess`/`doGenerate`/`BillingService::generateFor()` machinery — `addItemFp()` exists only as an unused generic fallback now that a dedicated fee method covers FP's actual fee types (see below). Unlike SGP, FP was placed in the sidebar between Occupancy Permit and Demolition Permit rather than after Demolition, since it inherited a pre-existing `sort_order = 3` on its `PermitType` seed row from before this session.

The application form mirrors NBC Form No. B-03: Applicant Info + enterprise/ownership, cascading Applicant Address (with an `applicant_ctc_*` triplet added in a follow-up migration), Location of Construction (lot/block/TCT/tax-dec no.), a single-choice Scope of Work (`new_construction`/`erection`/`addition`/`repair`/`others`, with a detail textbox), a fixed Design Professional block (name/address/PRC no./validity/PTR no./date issued/issued at/TIN), an identically-shaped fixed Full-Time Inspector or Supervisor block, and Consent of Lot Owner (name/address/CTC no./date issued/issued at). Unlike DP/SGP at their initial build, FP later gained its own application-form `printForm()` (see below) — added in the same session, not deferred. Every field on the create/edit form is `required` except `owned_by_enterprise` (an optional checkbox) and its two conditional dependents `enterprise_name`/`form_of_ownership_id` (`required_if:owned_by_enterprise,1`, both client- and server-validated), plus `scope_of_work_detail` (`required_if:scope_of_work,repair,others`).

The Inspector section went through a design iteration worth noting as the codebase's first (and so far only) attempt at a repeatable-child-record UI: it was originally built as an Alpine.js "Add Inspector" list backed by a separate `fencing_inspectors` table with an `is_primary` flag to pick which inspector prints on the official form's single Box 3 slot. The user asked to simplify it into a second fixed single block matching Design Professional exactly, which required a migration dropping `fencing_inspectors` and adding 8 flat `inspector_*` columns directly to `fencing_applications`. A "Same as Design Professional" toggle was then added to the Inspector section header (`fencing-applications/form.blade.php`, `copyDesignProfessionalToInspector()`) that copies all 8 Design Professional values into the Inspector fields via plain JS — the same pattern as the existing "Same as PEE" toggle (`copyPeeToSew()`) already used in the BP application form (`resources/views/applications/form.blade.php`) to copy Professional Electrical Engineer fields into Supervisor of Electrical Works.

**FP application-form print** (added after FP's initial build, in a follow-up session): `FencingApplicationController::printForm()` mirrors `DemolitionApplicationController::printForm()`'s background-image-overlay technique, rendering `pdf/fencing-application-form.blade.php` over the two official NBC Form B-03 scans. Getting it right took several calibration rounds, each root-caused via PHP-GD pixel scans of the source images rather than guesswork:
- Several Box 1/2/3/4 fields (Address, Location of Construction's Lot/Blk/TCT/Tax-Dec-No. row, Box 2/3 Design-Professional/Inspector name lines, Box 4's C.T.C. row) had values positioned before, inside, or directly on top of their own printed labels — traced to values being calibrated against assumed label positions instead of pixel-scanned ones. Tax Dec. No.'s value needed a centered band (`text-align:center` over the label's actual blank-line span), not a left-aligned one like its siblings.
- The Page 2 Building Official signature block (dynamic `$boTitle`/`$boName`/`$boDesignation`) rendered with the pre-printed signature line struck through the text — the original `top` offset was based on an eyeballed screenshot estimate rather than a real pixel scan; the actual line sits ~0.6in higher than assumed. Fixed by re-measuring and adding a second designation line, mirroring DP's two-line-above-the-line convention.
- Scope of Work checkboxes were undersized and mispositioned relative to their printed squares by 0.04–0.25in each; the checkmark glyph itself (`&#10004;`) was also silently rendering as "?" because the `.c` class was missing `'DejaVu Sans'` in its font stack — DomPDF's core Arial font has no U+2714 glyph, a class of bug already worked around in every *other* discipline form's PDF template but missed when this one was first written.
- A header letterhead (official seal + national government logo + centered "Republic of the Philippines / [City] / Province of [Province]" text) was added afterward, matching DP's pattern — sized to 0.76in logos (not the requested 1.5x/0.93in, which would have overlapped "OFFICE OF THE BUILDING OFFICIAL"; the user chose the largest size that still fits the available 0.77in header band over two other options offered).
- **Performance**: the template originally referenced `fencing-p1.png`/`fencing-p2.png` directly — a Truecolor+Alpha PNG hits a known-slow path in DomPDF's image-embedding code (full GD decode + alpha handling + Flate re-encode), and this exact bottleneck had already been profiled and fixed for every *other* discipline form by converting their scanned backgrounds to flattened JPEGs (see `docs/TASK.md`'s PDF Print Performance Fix note) — FP was simply built after that fix and missed it. Converting `fencing-p1/p2.png` to `fencing-p1/p2.jpg` (quality 90, flattened onto white) dropped render time from ~10s to ~2.3s, in line with DP. The PNGs remain on disk, unreferenced, as calibration sources — matching every other form's convention.

FP fees are computed through a new, dedicated `FP_FEE` fee category, but it seeds no `FeeType`/`FeeSchedule` rows of its own — `AssessmentController::addFenceItem()` reuses the existing `ASS_FENCE_MASONRY` (range-based, height-in-meters) and `ASS_FENCE_INDIG` (per-unit, linear-meters) rows already seeded under `ACC_FEE` for the Building Permit's Accessory dropdown, plus 7 more codes added later in the same session — `ASS_LINE_GRADE` and six `ASS_GP_*` (Inspect/Excav/Issuance/Found/Other/Encroach) Ground Preparation & Excavation fees — added to the same `ACC_FEE`-reused dropdown for both `addAccFeeItem()` and `addFenceItem()`. Since the two original fence codes only ever needed `per_unit`/`range_based`, wiring in the 7 new codes required adding a `case 'fixed':` branch to `addFenceItem()`'s computation switch. (These same 7 codes were also briefly wired into Zoning's fee dropdown mid-session, then fully reverted at the user's request — Zoning's dropdown is back to its original 4 hardcoded options, `lc`/`lc_manual`/`cert`/`others`, with no FP-related fee category on the Zoning side.)

`pdf/fencing-permit.blade.php` reproduces NBC Form B-03 as a 2-page plain-CSS template (no scanned background image, same approach as SGP's certificate) rather than DP's background-overlay technique. Page 1 covers Boxes 1–5 (Owner/Applicant, Design Professional + Inspector side-by-side via `.box-half .col` inline-block columns, Applicant + Lot Owner Consent signatures, blank Notarization); Page 2 covers Boxes 6–8 (blank Measurements/Type-of-Fencing, blank Progress-Flow, an auto-filled Assessed-Fees table summing all active `FP_FEE` items, and the Building-Official-signed Action-Taken block). Building it surfaced a DomPDF pagination bug — the two-page template was rendering 3 pages because insufficient vertical spacing plus a `display:table`-based two-column layout for `.box-half` caused DomPDF to mis-paginate the content; fixed by tightening vertical margins and switching `.box-half .col` to `display:inline-block` columns instead of a table layout.

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

### Permits List Redesign (`/permits/building`, `/permits/occupancy`, `/permits/demolition`, `/permits/signage`, `/permits/fencing`)
`permits/index.blade.php` (shared by all 5 permit types, keyed by a `$type` variable) gained:
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

### Print Forms Dropdown & Discipline Print Routes
The BP application Show page's single Print button was replaced with a right-aligned "Print Forms" dropdown (Alpine.js) listing the Application Form plus 6 discipline forms (Architectural, Structural, Electrical, Sanitary, Mechanical, Electronics), each numbered. A single generic route/controller method, `ApplicationController::printDiscipline(Application, string $discipline)`, backs all 6 discipline links rather than duplicating a route per discipline — it validates the discipline key against a `DISCIPLINE_FORMS` const map and dispatches to a private `print{Discipline}Form()` renderer for each. **All 6 disciplines now render a real background-image-overlay PDF** (Architectural = NBC Form A-01, Structural = NBC Form A-07 Civil/Structural, Electrical/Sanitary = Form No. 77-001-S, Mechanical = NBC Form A-04, Electronics = NBC Form A-07 Electronics) — the print-forms set is complete; `pdf/discipline-form.blade.php` (blank placeholder) is no longer used by any discipline, kept only as a generic fallback. Separately, `applications/{id}/print` (`printForm()`) was converted from returning browser-print HTML to actually streaming a DomPDF, matching the pattern every other print route in the app already used — this required `defaultMediaType=print` (so `@media print`/`@page` CSS rules apply during PDF rendering, not just browser printing) and `dpi=200` (to match the background scan's true resolution; DomPDF's default 96dpi silently resamples/blurs a higher-resolution `background-image`, though it doesn't affect `<img>` tags, which embed at full resolution regardless).

### Architectural Permit PDF (NBC Form No. A-01)
`pdf/architectural-form.blade.php` reproduces the real NBC Form A-01 (Architectural Permit) as a 2-page background-image overlay — the same technique as the Unified Application Form — using the user's own source PDF scans (`public/images/forms/architectural-p1.png` / `-p2.png`, rasterized via the WinRT `Windows.Data.Pdf.PdfDocument` PowerShell technique, field positions calibrated with PHP GD pixel-scanning rather than eyeballed). Every field on the form maps to an existing column already collected on `Application`: Box 1 (Owner/Applicant name, M.I., TIN, enterprise name, form of ownership, occupancy, address, location of construction, scope-of-work checkboxes), Box 4 (Supervision engineer, from the `engineer_*` fields), Box 5 (Building Owner = the applicant), and Box 6 (Lot Owner consent, from the `owner_*` fields, including CTC No./Date Issued/Place Issued). Box 3 (Design Professional/Architect) is deliberately left blank rather than auto-filled with the same engineer data as Box 4 — the plans may be signed and sealed by a different architect than the engineer of record, so it's meant to be filled in by hand at signing time. Page 2's "PERMIT ISSUED BY:" block reads the generated `Permit`'s `building_official_title`/`_name`/`_designation` snapshot columns (the same immutable-per-permit snapshot the Building/Occupancy Permit PDFs already use), rendered only when a Permit exists for the application; the rest of page 2 (Boxes 7–9, internal office-processing checklists) is pure background image with no data overlay. A readability pass moved several tight-cell field groups (Box 1's name row and address row; Box 6's CTC/Date/Place row) to sit on the blank line *below* their printed label instead of crowding beside it, at a larger font size — the same GD-pixel-measured "find the label's true end position, then find the cell's blank space" technique used throughout the BP form's calibration. One recurring GD gotcha: the source PNGs are palette-indexed, so `imagecolorat()` returns a raw palette index rather than an RGB triple — every darkness-detection helper must resolve through `imagecolorsforindex()` first, or measurements come out silently wrong.

### Structural, Electrical, Sanitary, Mechanical, Electronics Permit PDFs
The remaining 5 discipline forms followed the same background-image-overlay pattern established by Architectural, each with its own quirks: **Structural** (NBC Form A-07 Civil/Structural) and **Electrical** (Form No. 77-001-S) reuse the generic `engineer_*` fields for their supervision boxes since no dedicated professional-engineer columns exist; Electrical additionally fills a "Summary of Electrical Loads/Capacities" section from `total_connected_load`/`total_transformer_capacity`/`total_generator_capacity`. **Sanitary** (Form No. 77-001-S) has a denser layout with separate ADDRESS/LOCATION OF INSTALLATION rows and puts its Building Official signatory block on page 1 instead of page 2 (the only one of the 6 to do so). **Mechanical** (NBC Form A-04) and **Electronics** (NBC Form A-07) had no official source scan — their backgrounds are the user's own clean digitally-generated reference images rather than PDF-rasterized scans; Mechanical is also the only one on an 8.5×14in (legal) page instead of the other five's 8.5×13in. Both leave their "Design Professional"/"Supervisor-In-Charge" boxes and per-installation-type checklists entirely blank since `Application` has no backing columns for them (Mechanical's Scope of Work checkboxes are the exception — all 12 options map against the seeded `scope_of_works` table, the richest mapping of any discipline form). A recurring class of bugs across all these forms: overlay text positioned too close to a row's border reads as a stray strikethrough once DomPDF's line-height is factored in — always pixel-scan the exact border/underscore position via GD rather than eyeballing coordinates from a reference screenshot.

### "Computer-Generated Document" Footer
Every application form and permit form (BP/OP Unified Application, all 6 discipline forms, Building Permit, Occupancy Permit) now prints a small footer on every page: "This is a computer-generated document. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}". No controller changes were needed — every one of these routes is behind `can:view-applications`/`can:print-permits` middleware (or an `Auth::id()`-matching guard on the client-portal downloads), so `auth()->user()` is always populated and can be called directly from the Blade view. On the `.print-page` background-overlay forms it's positioned with `bottom:` (not `top:`) so it anchors to each page's actual bottom edge regardless of page height — this made Mechanical's 14in-vs-13in page size a non-issue with no per-form math.

### Audit Logs Report (Super-Admin Only)
A new `view-audit-logs` permission — granted to `super-admin` alone, not `administrator` or any other role — gates a new `/reports/audit-logs` page (`ReportController::auditLogs()`) that surfaces Spatie's existing `activity_log` table, already populated by Application, OccupancyApplication, Assessment, Collection, Permit, and User activity. The permission is enforced twice: at the route (its own `can:view-audit-logs` middleware, independent of the `reports.` group's blanket `can:view-reports`) and at the sidebar link (`@can('view-audit-logs')`), so a role with general report access but not this specific permission neither sees the link nor can reach the page directly. Filters mirror the app's established index-page conventions: free-text search over the activity description, a causer (user) dropdown, subject-type dropdown (mapped to human-readable labels per model), event dropdown, and a month picker defaulting to the current month.

### Full BP/OP End-to-End QA Pass
A manual walkthrough of the complete Building Permit and Occupancy Permit lifecycles — application creation through permit generation — exercising every workflow/revert transition, all engineering fee categories (spot-checking computed amounts against the fee schedule tables), every payment mode, and every print output, using the same staff-login-curl + WinRT-rasterize + visual-inspection workflow established for verifying the PDF work throughout this project. Included negative/security checks: attempting revert/void/generate actions without the required permission (expect 403), a duplicate OR-number submission, double permit generation, and an IDOR check on print routes as a `client`-role user.

### PDF Print Performance: Font Cache, OPcache, and JPEG Backgrounds
Print requests for the background-image-overlay PDFs (Unified Application Form, Architectural Permit) were taking 5-8 seconds each. Profiled with raw `Dompdf` timing calls (`loadHtml()` vs `render()` vs `output()`) to isolate where the time went:
- **DomPDF font cache directory was missing** (`storage/fonts`, referenced by `font_cache` in `config/dompdf.php`) — every render had to re-parse font metrics (including the DejaVu Sans TTF used for checkmarks/peso signs) from scratch instead of reading cached `.ufm`/`.afm` JSON. Fixed by creating the directory and adding `SelfHealingServiceProvider::ensureFontCacheDirExists()` (a cheap `is_dir()`/`mkdir()` check, safe to run every request) so it self-heals on fresh setups too.
- **PHP OPcache was disabled** in the local XAMPP `php.ini` (default/commented-out) — every request recompiled the entire Laravel + vendor codebase from source. Enabled with `validate_timestamps=1` / `revalidate_freq=0` so edited files are still picked up immediately on a dev machine, no manual cache-clear needed. This sped up every page in the app, not just PDFs.
- **The dominant cost (~95% of render time) was DomPDF embedding large PNG page backgrounds** (the 1700×2600/2800px, 200dpi scans used as full-page `background-image`s). Isolated by re-rendering the same HTML with the background-image rule stripped: 7.2s → 0.3s. DomPDF's CPDF backend has no fast-path for PNG — it must fully GD-decode the image, manually separate any alpha channel, and re-Flate-encode the raw pixel data into the PDF stream. JPEG, by contrast, can often be embedded near-directly (DCTDecode) with far less CPU work. Converting the 4 background images (`unified-bp-form-p{1,2}` and `architectural-p{1,2}`) to JPEG (quality 90, flattened onto white to drop any alpha) and pointing the Blade `background-image` rules at the `.jpg` files cut total render time to ~2s — the same `dpi=200` setting is kept unchanged, so sharpness is unaffected (confirmed by re-rendering and visually inspecting a high-res crop). The original `.png` source scans are kept on disk (unreferenced by the templates) since they're the ground truth used for any future GD pixel-scan recalibration — PNG's lossless pixel values are more reliable for that than a recompressed JPEG.

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
