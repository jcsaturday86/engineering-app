# Tasks

---

## Current Task: Separate BP and OP into Different Database Tables

### Background

Currently both Building Permit (BP) and Occupancy Permit (OP) applications are stored in a single `applications` table, differentiated by `permit_type_id`. This causes:
- The `applications` table has 80+ columns, many of which are BP-only or OP-only
- Downstream tables (assessments, billings, collections, permits) all use a single `application_id` FK
- BP fields (scope_of_work, building costs, engineer details, electrical data) are null for OP records
- OP fields (bp_number, fsec_no, completion_date) are null for BP records

### What BOPMS Does (Reference)

The legacy system uses completely separate table hierarchies:
- `application_building_permits` — BP applications
- `application_occupancy_permits` — OP applications
- `application_building_permits_groups` — BP occupancy groups
- `application_occupancy_permits_groups` — OP occupancy groups
- Separate assessment tables: `bp_assessment_*` vs `occ_assessment_*`
- Separate collection FKs: `application_bp_id` vs `application_op_id`
- Separate permit tables: `generate_building_permits` vs `generate_occupancy_permits`

### Target Architecture

- `applications` table → BP only (remove OP-specific columns)
- New `occupancy_applications` table → OP only (with OP-specific columns)
- Downstream tables use polymorphic `applicationable_type` + `applicationable_id`
- Separate models, controllers, routes, and views for each

### Implementation Phases

#### Phase 1: Database Schema
- Create `occupancy_applications` migration with OP-specific fields
- Create `occupancy_application_occupancy_groups` pivot table
- Alter downstream tables (assessments, billings, collections, permits, documents) to add polymorphic columns
- Data migration: move existing OP records from `applications` to `occupancy_applications`

#### Phase 2: Model Layer
- Create `OccupancyApplication` model with relationships
- Create shared trait or interface for common application behavior
- Update Assessment, Billing, Collection, Permit, Document models for polymorphic relationships

#### Phase 3: Service Layer
- Create `OccupancyApplicationService`
- Update AssessmentService, BillingService, CollectionService, PermitService to handle both model types

#### Phase 4: Controller Layer
- Create `OccupancyApplicationController` (CRUD + submit/cancel/print)
- Update AssessmentController (separate BP/OP handling)
- Update BillingController, CollectionController, PermitController for polymorphic resolution

#### Phase 5: Routes
- Add `/occupancy-applications` resource routes with permissions
- Update assessment/billing/collection/permit routes

#### Phase 6: Views
- Create `resources/views/occupancy-applications/` (index, form, show)
- Update or create OP-specific assessment views
- Update PDF templates for OP

#### Phase 7: Testing & Verification
- Test BP workflow end-to-end (should be unchanged)
- Test OP workflow end-to-end (new table/controller)
- Verify all downstream operations (assessment, billing, payment, permit) work for both
- Verify online portal works for both BP and OP

### Files to Create

| File | Purpose |
|------|---------|
| `database/migrations/xxxx_create_occupancy_applications_table.php` | OP table |
| `database/migrations/xxxx_add_polymorphic_to_downstream_tables.php` | Polymorphic FKs |
| `database/migrations/xxxx_migrate_op_data.php` | Data migration |
| `app/Models/OccupancyApplication.php` | OP model |
| `app/Http/Controllers/OccupancyApplicationController.php` | OP controller |
| `app/Services/OccupancyApplicationService.php` | OP service |
| `app/DTOs/OccupancyApplicationDTO.php` | OP data transfer |
| `resources/views/occupancy-applications/index.blade.php` | OP list |
| `resources/views/occupancy-applications/form.blade.php` | OP create/edit |
| `resources/views/occupancy-applications/show.blade.php` | OP detail |

### Files to Modify

| File | Changes |
|------|---------|
| `routes/web.php` | Add occupancy-application routes |
| `app/Models/Assessment.php` | Polymorphic relationship |
| `app/Models/Billing.php` | Polymorphic relationship |
| `app/Models/Collection.php` | Polymorphic relationship |
| `app/Models/Permit.php` | Polymorphic relationship |
| `app/Models/Document.php` | Polymorphic relationship |
| `app/Http/Controllers/AssessmentController.php` | Handle both model types |
| `app/Http/Controllers/BillingController.php` | Handle both model types |
| `app/Http/Controllers/CollectionController.php` | Handle both model types |
| `app/Http/Controllers/PermitController.php` | Handle both model types |
| `app/Http/Controllers/DashboardController.php` | Query both tables for stats |
| `app/Http/Controllers/OnlineApplicationController.php` | Handle OP creation |
| `app/Services/AssessmentService.php` | Accept both model types |
| `app/Services/BillingService.php` | Accept both model types |
| `app/Services/CollectionService.php` | Accept both model types |
| `app/Services/PermitService.php` | Accept both model types |
| `app/Enums/ApplicationStatus.php` | May need OP-specific transitions |
| `database/seeders/ReferenceDataSeeder.php` | Update for new table |
| `resources/views/pdf/occupancy-permit.blade.php` | Update references |
| `resources/views/pdf/application-form.blade.php` | Handle OP model |

### Risk Areas

- **Data migration** — Existing OP records must be moved without breaking FK relationships
- **Polymorphic complexity** — All downstream queries must be updated to resolve correct model
- **Dashboard/Reports** — Must aggregate across both tables
- **Online portal** — Must handle both BP and OP creation flows
- **Activity log** — Historical logs reference old `Application` model for OP records

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
