# Tasks

---

## Completed Tasks

### Separate BP and OP into Different Database Tables -- COMPLETED

BP and OP applications now live in separate database tables with polymorphic downstream relationships. Implementation summary:

- **Database:** `applications` table is BP-only (OP-specific columns removed). New `occupancy_applications` table for OP. 7 downstream tables (assessments, billings, collections, permits, documents, application_requirements, application_occupancy_groups) use polymorphic `applicationable_type` + `applicationable_id` columns. Morph map: `bp` → Application, `op` → OccupancyApplication.
- **Models:** New `OccupancyApplication` model. Shared behavior via `PermitApplicationContract` interface + `HasPermitApplicationBehavior` trait. 7 downstream models use `MorphTo` with backward-compat accessor. Total: 32 models.
- **Controllers:** New `OccupancyApplicationController` for OP CRUD. AssessmentController has parallel `*Op()` methods. BillingController/CollectionController/PermitController have `*Op()` methods. DashboardController aggregates both tables. OnlineApplicationController branches BP/OP. Total: 14 controllers.
- **Services/DTOs:** New `OccupancyApplicationService` (8 total). New `OccupancyApplicationDTO` (4 total).
- **Routes:** `/occupancy-applications/*` for OP CRUD. Parallel OP routes for assessment/billing/collection/permit. Total: 100+ routes.
- **Views:** New `occupancy-applications/` directory (index, form, show). Sidebar has separate BP and OP nav sections. Assessment views are route-aware ($isOp flag).
- **Notifications:** 4 notification classes accept `Model` instead of `Application`.
- **Enums:** `ApplicationStatus::allowedTransitionsFor(string $permitTypeCode)` for OP flow (skips zoning_assessed).

### Zoning Assessment Fee Auto-Compute & Settings -- COMPLETED

Added zoning fee auto-compute matching BOPMS `zoningAutoCompute()` logic, with dedicated fee tables and settings UI:

- **Database:** New `land_use_and_zoning_fees` table (162 rows, 52 sub-groups, 6 fee patterns) and `certification_zoning_fees` table (P500 flat). Migrated data from generic `fee_schedules`. Made `application_id` nullable on 7 downstream tables. Added `project_title` to `occupancy_applications`.
- **Models:** New `LandUseAndZoningFee`, `CertificationZoningFee`.
- **Controllers:** `ZoningController` updated with `autoCompute()` (queries new tables directly), `addItem()`, `removeItem()`. New `ZoningFeeController` for settings CRUD.
- **View:** Zoning form restyled to BP/OP card pattern (numbered badges). Section 5 (Evaluation) removed. Fee items table with Auto Compute button, per-row delete, and manual add form. New `/settings/zoning-fees` page with accordion by occupancy group.
- **Workflow:** New `for_zoning_assessment` status for BP apps routed to planning. `submitted` status now means skip-LC (direct to engineering).
- **Validation:** Backend validation aligned with HTML required fields on BP/OP forms. Error summary banner, section card highlighting, auto-scroll to errors.
- **Other:** Browser autofill disabled on all 41 forms. `ApplicationSeeder` creates 5 BP + 5 OP test records. `FeeComputationService::applyExcess()` fixed for percentage-based excess.

### Zoning Assessment UX Improvements -- COMPLETED

Enhanced the zoning assessment page with BOPMS-matching features:

- **Fee type selector:** 4 fee types (Locational Clearance, LC Manual Entry, Certification, Others) matching BOPMS, with conditional form fields per type.
- **Other zoning fees:** New `land_use_and_zoning_other_fees` table (Variance, Non-Conforming) with settings UI.
- **Checkbox select-all / bulk delete:** Multi-select with fetch API for bulk item removal.
- **Password confirmation on finalize:** Modal with `Hash::check()` validation before finalizing.
- **HTML form nesting fix:** Resolved issue where modal forms submitted to wrong route due to browser HTML parsing.

### BP Assessment Tabbed Navigation & BOPMS-Style Forms -- COMPLETED

Redesigned the BP assessment page with tabbed navigation and BOPMS-matching forms:

- **Tabbed navigation:** 8 fee category tabs (Construction, Electrical, Mechanical, Plumbing, Electronics, Accessories, Accessory, Surcharges) + Summary tab with item count badges.
- **Construction tab (BOPMS-style):** Part of Building + Division (filtered by occupancy groups) + Area → server-side fee lookup from `fee_schedules`. Formula: `amount = area × fee_per_unit`. Total Area row in table footer.
- **Electrical tab (BOPMS-style):** 7 fee type options with conditional fields. Split `ELEC_TUG` into `ELEC_TRANS` + `ELEC_UPS` matching BOPMS. Range-based kVA computation: `amount = fixed_fee + (kva × fee_per_unit)`. Fixed fees for pole/meter/wiring types. Inspection fee auto-computed from `assessment.electrical_inspection_percentage` setting (default 10%). Total amount = base fee + inspection fee.
- **Seeder updates:** Building parts changed to BOPMS values. Fee category names shortened. Electrical fee schedules updated with `.99` range boundaries matching BOPMS. Old `ELEC_TUG` deactivated.
- **New setting:** `assessment.electrical_inspection_percentage` — configurable in Settings > Assessment group.
- **New routes:** `POST /assessments/{id}/construction-item`, `POST /assessments/{id}/electrical-item`.

---

## Upcoming Tasks

| Task | Priority | Notes |
|------|----------|-------|
| Additional permit types (FP, EP, DP, etc.) | Medium | Currently only BP and OP are active |
| Enhanced fee schedule seeding | Medium | Some fee categories may need more data |
| Document requirement upload UI | Low | Model exists, UI needs improvement |
| Email notification configuration | Low | SMTP settings, notification templates |
| Advanced reporting | Low | More report types, dashboard charts |
| Annual inspection module | Future | Not in current requirements |
