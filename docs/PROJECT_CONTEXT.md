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
`/collections` has an autofocused search box: scanning the barcode from a printed assessment (which encodes the application number) exact-matches a billed application and redirects straight to its payment form; partial text filters the Awaiting Payment list. The payment form itself is a compact, single-screen layout — a 3-column Amount Due/Amount Received/Change strip (live Alpine calculation, switches to a red "Short" warning if underpaid, and the server rejects an insufficient cash payment) plus a segmented Cash/Check/Online control and a sticky bottom action bar — designed so a collector never has to scroll mid-transaction.

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
- **No BFP module** — Fire safety (FSEC/FSIC) is intentionally excluded
- **Separate portals** — Staff login (`/staff/login`) and client login (`/login`) are separate
- **CDN dependencies** — Tailwind CSS and Alpine.js loaded via CDN (no build step)
