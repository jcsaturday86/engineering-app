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
