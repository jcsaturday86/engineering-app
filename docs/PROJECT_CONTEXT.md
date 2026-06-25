# Project Context

## Overview

The **Engineering Permit Management System (EPMS)** is a web application for the City of San Fernando, La Union, Philippines. It manages the full lifecycle of building and occupancy permits — from application intake through assessment, billing, payment, and permit issuance.

This system replaces the legacy BOPMS (Building and Occupancy Permit Management System). See `docs/BOPMS_*.md` for legacy reference.

---

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend | PHP 8.2+ / Laravel 12 |
| Database | MariaDB 12.3 |
| Frontend | Blade + Tailwind CSS (CDN) + Alpine.js (CDN) |
| Charts | Chart.js |
| PDF | DomPDF (barryvdh/laravel-dompdf) |
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
`app/Contracts/PermitApplicationContract.php` defines the shared interface. `app/Concerns/HasPermitApplicationBehavior.php` provides a trait with shared accessors and polymorphic relationships (assessments, billings, collections, permits, documents, etc.). Both `Application` and `OccupancyApplication` implement the contract and use the trait, keeping BP-specific logic (scope of work, cost fields, zoning, engineer/PEE/SEW) in `Application` only.

### Enum-Based State Machine
`app/Enums/ApplicationStatus.php` defines the complete workflow with `allowedTransitions()` and `allowedTransitionsFor(string $permitTypeCode)` for strict state validation. BP applications without skip LC go to `for_zoning_assessment` status. OP flow skips zoning entirely. No invalid transitions possible.

### Consolidated Fee Schedule
BOPMS had 100+ individual fee tables. Engineering-app consolidates into 3 tables:
- `fee_categories` — groups by permit type (BP construction fees, OP occupancy fees, etc.)
- `fee_types` — individual fee items with computation method
- `fee_schedules` — rate rows with ranges, fixed fees, excess thresholds

Six computation methods: `fixed`, `per_unit`, `range_based`, `cumulative_range`, `percentage`, `formula`.

### Dedicated Zoning Fee Tables
Zoning fees use dedicated tables matching BOPMS naming:
- `land_use_and_zoning_fees` — locational clearance fees by occupancy sub-group with range-based + excess computation (162 rows across 52 sub-groups, 6 fee patterns)
- `certification_zoning_fees` — flat certification fee (P500)

Auto-compute in `ZoningController::autoCompute()` queries these tables directly, matching BOPMS `TransactionController::zoningAutoCompute()` logic.

### Self-Healing Service Provider
`SelfHealingServiceProvider` auto-creates database, runs migrations, and seeds roles/settings/admin if missing on every application boot. Ensures the system works even on a fresh install.

### Spatie Permission (RBAC)
9 roles with 30+ granular permissions. Each route protected with `middleware('can:permission-name')`.

### Spatie Activitylog (Audit Trail)
Activity logging on Application, Assessment, Collection, Permit models. Tracks who changed what, when.

---

## Environment Setup

| Setting | Value |
|---------|-------|
| Server | XAMPP on Windows 11 |
| MariaDB | `C:\Program Files\MariaDB 12.3\bin\` |
| DB Host | 127.0.0.1 |
| DB Name | `epms_db` |
| DB User | root |
| DB Password | sfcity98 |
| App URL | http://localhost:8100 (artisan serve) |
| Alt URL | http://localhost/engineering-app/public |

### Running

```bash
php artisan serve --port=8100
```

### Testing

```bash
php artisan test
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
- **No BFP module** — Fire safety (FSEC/FSIC) is intentionally excluded from this system
- **Separate portals** — Staff login (`/staff/login`) and client login (`/login`) are separate
- **CDN dependencies** — Tailwind CSS and Alpine.js loaded via CDN (no build step)
