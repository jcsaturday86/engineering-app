# Codebase Reference

---

## Directory Structure

```
engineering-app/
├── app/
│   ├── Actions/           (4 action classes)
│   ├── DTOs/              (3 data transfer objects)
│   ├── Enums/             (5 enums)
│   ├── Exports/           (3 Excel export classes)
│   ├── Http/
│   │   ├── Controllers/   (13 + Auth controllers)
│   │   └── Middleware/
│   ├── Models/            (31 Eloquent models)
│   ├── Notifications/     (4 notification classes)
│   ├── Providers/         (AppServiceProvider, SelfHealingServiceProvider)
│   └── Services/          (7 service classes)
├── database/
│   ├── migrations/        (20+ migration files)
│   ├── seeders/           (8 seeders)
│   └── factories/
├── resources/views/       (35+ Blade templates)
├── routes/web.php         (83 routes)
├── config/
├── public/
├── storage/
├── tests/
├── docs/                  (this documentation)
└── CLAUDE.md
```

---

## Models (31)

### Core Transaction Models

| Model | Table | Key Relationships |
|-------|-------|-------------------|
| Application | applications | belongsTo: permitType, applicationType, scopeOfWork, formOfOwnership, provinces/cities/barangays (×2), landClassification. hasMany: assessments, billings, collections, permits, documents, applicationOccupancyGroups, applicationRequirements. hasOne: zoningAssessment |
| ApplicationOccupancyGroup | application_occupancy_groups | belongsTo: application, occupancyGroup, occupancySubGroup |
| ApplicationRequirement | application_requirements | belongsTo: application, reviewedBy (user) |
| Assessment | assessments | belongsTo: application, assessedBy. hasMany: assessmentItems. SoftDeletes, LogsActivity |
| AssessmentItem | assessment_items | belongsTo: assessment, feeCategory, feeType. SoftDeletes |
| ZoningAssessment | zoning_assessments | belongsTo: application (1:1), assessedBy. SoftDeletes |
| Billing | billings | belongsTo: application, generatedBy. hasMany: billingItems. SoftDeletes |
| BillingItem | billing_items | belongsTo: billing |
| Collection | collections | belongsTo: application, billing, collectedBy. hasMany: collectionDetails. hasOne: voidTransaction. SoftDeletes, LogsActivity |
| CollectionDetail | collection_details | belongsTo: collection |
| VoidTransaction | void_transactions | belongsTo: collection, voidedBy |
| Permit | permits | belongsTo: application, permitType, processedBy, approvedBy. SoftDeletes, LogsActivity |
| Document | documents | belongsTo: application, generatedBy |

### Reference/Lookup Models

| Model | Table | Notes |
|-------|-------|-------|
| PermitType | permit_types | hasMany: feeCategories, applications |
| ApplicationType | application_types | belongsTo: permitType |
| ScopeOfWork | scope_of_works | Simple lookup |
| FormOfOwnership | form_of_ownerships | Simple lookup |
| OccupancyGroup | occupancy_groups | hasMany: subGroups, divisions |
| OccupancySubGroup | occupancy_sub_groups | belongsTo: occupancyGroup |
| OccupancyDivision | occupancy_divisions | belongsTo: occupancyGroup |
| BuildingPart | building_parts | Simple lookup |
| Signatory | signatories | Simple lookup |
| LandClassification | land_classifications | Simple lookup |

### Fee Schedule Models

| Model | Table | Notes |
|-------|-------|-------|
| FeeCategory | fee_categories | belongsTo: permitType. hasMany: feeTypes |
| FeeType | fee_types | belongsTo: feeCategory. hasMany: feeSchedules |
| FeeSchedule | fee_schedules | belongsTo: feeType, occupancyDivision, occupancySubGroup |

### Auth/System Models

| Model | Table | Notes |
|-------|-------|-------|
| User | users | HasRoles (Spatie), LogsActivity, SoftDeletes |
| Province | provinces | hasMany: cities |
| City | cities | belongsTo: province. hasMany: barangays |
| Barangay | barangays | belongsTo: city |

---

## Controllers

### ApplicationController
| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /applications | List with search/status/type filters |
| create | GET /applications/create | New form (filtered by permit type) |
| store | POST /applications | Create with occupancy groups, auto-number |
| show | GET /applications/{id} | Detail view with all relationships |
| edit | GET /applications/{id}/edit | Edit form |
| update | PUT /applications/{id} | Update with occupancy groups |
| submit | POST /applications/{id}/submit | draft → submitted (or zoning_assessed) |
| cancel | POST /applications/{id}/cancel | → cancelled with reason |
| printForm | GET /applications/{id}/print | HTML print view |

### ZoningController
| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /zoning | List submitted BP applications |
| assess | GET /zoning/{id} | Zoning assessment form |
| store | POST /zoning/{id} | Save assessment (updateOrCreate) |
| finalize | POST /zoning/{id}/finalize | → zoning_assessed |
| skip | POST /zoning/{id}/skip | Bypass locational clearance |

### AssessmentController
| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /assessments | BP assessment list |
| occupancyIndex | GET /assessments/occupancy | OP assessment list |
| assess | GET /assessments/{id} | Fee item entry form |
| addItem | POST /assessments/{id}/item | Add line item |
| removeItem | DELETE /assessments/item/{id} | Remove item |
| summary | GET /assessments/{id}/summary | Summary view |
| finalize | POST /assessments/{id}/finalize | → engineering_assessed |
| print | GET /assessments/{id}/print | PDF summary |

### BillingController
| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /billing | Applications ready for billing |
| generate | POST /billing/{id}/generate | Create billing → billed |
| print | GET /billing/{id}/print | PDF billing statement |

### CollectionController
| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /collections | Active collections list |
| create | GET /collections/{id}/pay | Payment form |
| store | POST /collections/{id}/pay | Record payment → paid |
| receipt | GET /collections/{id}/receipt | Official receipt PDF |
| voidForm | GET /collections/void | Void OR form |
| processVoid | POST /collections/void | Process void |

### PermitController
| Method | Route | Purpose |
|--------|-------|---------|
| buildingIndex | GET /permits/building | BP permit list |
| occupancyIndex | GET /permits/occupancy | OP permit list |
| generate | POST /permits/{id}/generate | Generate permit → permit_generated |
| print | GET /permits/{id}/print | PDF permit document |
| zoningCertification | GET /permits/{id}/zoning-cert | Zoning cert PDF |
| locationalClearance | GET /permits/{id}/locational | Locational clearance PDF |
| evaluationReport | GET /permits/{id}/evaluation | Evaluation report PDF |

### Other Controllers

| Controller | Key Methods |
|-----------|------------|
| DashboardController | index (KPIs: applications, revenue, charts) |
| OnlineApplicationController | dashboard, create, store, show, uploadRequirements, storeRequirement, track, downloadPermit |
| ReportController | permits, revenue, collections, generate (Excel/PDF) |
| SettingsController | index, update, users CRUD, roles, fees, signatories |
| FeeScheduleController | showCategory, showType, storeType, updateType, storeSchedule, updateSchedule, destroySchedule |
| ProfileController | edit, update |

---

## Services (7)

| Service | Key Methods |
|---------|------------|
| ApplicationService | generateApplicationNumber(), create(), update(), submit(), transitionStatus() |
| AssessmentService | finalize() — recalculate totals, mark finalized |
| FeeComputationService | computeFee() — 6 computation methods with excess/min/max |
| BillingService | generateBilling() — create billing from assessments |
| CollectionService | recordPayment() — create collection, update billing |
| PermitService | generatePermit() — create permit with auto-numbering |
| SettingService | get(), set() — system settings |

## Actions (4)

| Action | Purpose |
|--------|---------|
| CreateApplicationAction | Wraps ApplicationService.create(), logs activity |
| FinalizeAssessmentAction | Wraps AssessmentService.finalize(), transitions status |
| GeneratePermitAction | Wraps PermitService.generatePermit(), transitions to PERMIT_GENERATED |
| ProcessPaymentAction | Wraps CollectionService.recordPayment(), transitions to PAID |

## DTOs (3)

| DTO | Purpose |
|-----|---------|
| ApplicationDTO | Readonly — all application fields from request |
| AssessmentItemDTO | Fee item data for assessment |
| CollectionDTO | Payment data for collection |

## Enums (5)

| Enum | Values |
|------|--------|
| ApplicationStatus | draft, submitted, zoning_assessed, engineering_assessed, billed, paid, permit_generated, released, cancelled |
| AssessmentType | building, occupancy, zoning |
| ComputationMethod | fixed, per_unit, range_based, cumulative_range, percentage, formula |
| PaymentMode | cash, check, online |
| PermitTypeCode | BP, OP, FP, EP, DP, SP, ELP, MP, PP, ECP |

---

## Views

### Layout
- `layouts/app.blade.php` — authenticated staff layout
- `layouts/guest.blade.php` — auth pages layout
- `partials/sidebar-nav.blade.php` — navigation menu

### Application Views
- `applications/index.blade.php` — list with filters
- `applications/form.blade.php` — create/edit (shared BP/OP with @if toggles)
- `applications/show.blade.php` — detail view

### Assessment Views
- `assessments/index.blade.php` — BP assessment list
- `assessments/occupancy-index.blade.php` — OP assessment list
- `assessments/assess.blade.php` — fee item entry
- `assessments/summary.blade.php` — summary view

### Other Views
- `zoning/index.blade.php`, `zoning/assess.blade.php`
- `billing/index.blade.php`, `billing/print.blade.php`
- `collections/index.blade.php`, `collections/create.blade.php`, `collections/void.blade.php`
- `permits/index.blade.php`
- `online/dashboard.blade.php`, `online/apply.blade.php`, `online/show.blade.php`, `online/track.blade.php`
- `dashboard/index.blade.php`
- `settings/*`, `reports/*`, `auth/*`

### PDF Templates (`resources/views/pdf/`)
application-form, building-permit, occupancy-permit, assessment-summary, billing-statement, official-receipt, zoning-certification, locational-clearance, evaluation-report, report

---

## Providers

| Provider | Purpose |
|----------|---------|
| AppServiceProvider | Standard Laravel provider |
| SelfHealingServiceProvider | Auto-creates DB, runs migrations, seeds roles/settings/admin on boot |

## Seeders

| Seeder | Data |
|--------|------|
| RolePermissionSeeder | 9 roles, 30+ permissions |
| ReferenceDataSeeder | Permit types, application types, scopes, ownerships, parts, land classifications, signatories, fee categories |
| OccupancyGroupSeeder | 10 groups (A-J) with 40+ sub-groups |
| FeeScheduleSeeder | Comprehensive fee structure (72KB) |
| GeoDataSeeder | Philippine provinces/cities/barangays (2.5MB) |
| SettingsSeeder | Default system settings |
| AdminUserSeeder | Default admin user |

## Third-Party Packages

| Package | Purpose |
|---------|---------|
| spatie/laravel-permission | Role-based access control |
| spatie/laravel-activitylog | Audit trail |
| barryvdh/laravel-dompdf | PDF generation |
| maatwebsite/excel | Excel exports |
