# Codebase Reference

---

## Directory Structure

```
engineering-app/
├── app/
│   ├── Actions/           (4 action classes)
│   ├── Concerns/          (1 trait: HasPermitApplicationBehavior)
│   ├── Contracts/         (1 interface: PermitApplicationContract)
│   ├── DTOs/              (4 data transfer objects)
│   ├── Enums/             (5 enums)
│   ├── Exports/           (3 Excel export classes)
│   ├── Http/
│   │   ├── Controllers/   (14 + Auth controllers)
│   │   └── Middleware/
│   ├── Models/            (32 Eloquent models)
│   ├── Notifications/     (4 notification classes)
│   ├── Providers/         (AppServiceProvider, SelfHealingServiceProvider)
│   └── Services/          (8 service classes)
├── database/
│   ├── migrations/        (20+ migration files)
│   ├── seeders/           (8 seeders)
│   └── factories/
├── resources/views/       (38+ Blade templates)
│   ├── occupancy-applications/  (index, form, show)
│   └── ...
├── routes/web.php         (100+ routes)
├── config/
├── public/
├── storage/
├── tests/
├── docs/                  (this documentation)
└── CLAUDE.md
```

---

## Models (32)

### Core Transaction Models

| Model | Table | Key Relationships |
|-------|-------|-------------------|
| Application | applications | Implements PermitApplicationContract, uses HasPermitApplicationBehavior. belongsTo: permitType, applicationType, scopeOfWork, formOfOwnership, provinces/cities/barangays (x2), landClassification. morphMany (via trait): assessments, billings, collections, permits, documents, applicationOccupancyGroups, applicationRequirements. hasOne: zoningAssessment. BP-specific: scopeOfWork(), getTotalEstimatedCostAttribute(), getPermitTypeCode() returns 'BP' |
| OccupancyApplication | occupancy_applications | Implements PermitApplicationContract, uses HasPermitApplicationBehavior. belongsTo: applicationType, formOfOwnership, provinces/cities/barangays (x2), landClassification. morphMany (via trait): assessments, billings, collections, permits, documents, applicationOccupancyGroups, applicationRequirements. OP-specific: project_title, bp_number, fsec_no, completion_date. getPermitTypeCode() returns 'OP' |
| ApplicationOccupancyGroup | application_occupancy_groups | morphTo: applicationable (Application or OccupancyApplication), belongsTo: occupancyGroup, occupancySubGroup. Backward-compat getApplicationAttribute() accessor |
| ApplicationRequirement | application_requirements | morphTo: applicationable (Application or OccupancyApplication), belongsTo: reviewedBy (user). Backward-compat getApplicationAttribute() accessor |
| Assessment | assessments | morphTo: applicationable (Application or OccupancyApplication), belongsTo: assessedBy. hasMany: assessmentItems. SoftDeletes, LogsActivity. Backward-compat getApplicationAttribute() accessor |
| AssessmentItem | assessment_items | belongsTo: assessment, feeCategory, feeType. SoftDeletes |
| ZoningAssessment | zoning_assessments | belongsTo: application (1:1, BP only), assessedBy. SoftDeletes |
| Billing | billings | morphTo: applicationable (Application or OccupancyApplication), belongsTo: generatedBy. hasMany: billingItems. SoftDeletes. Backward-compat getApplicationAttribute() accessor |
| BillingItem | billing_items | belongsTo: billing |
| Collection | collections | morphTo: applicationable (Application or OccupancyApplication), belongsTo: billing, collectedBy. hasMany: collectionDetails. hasOne: voidTransaction. SoftDeletes, LogsActivity. Backward-compat getApplicationAttribute() accessor |
| CollectionDetail | collection_details | belongsTo: collection |
| VoidTransaction | void_transactions | belongsTo: collection, voidedBy |
| Permit | permits | morphTo: applicationable (Application or OccupancyApplication), belongsTo: permitType, processedBy, approvedBy. SoftDeletes, LogsActivity. Backward-compat getApplicationAttribute() accessor |
| Document | documents | morphTo: applicationable (Application or OccupancyApplication), belongsTo: generatedBy. Backward-compat getApplicationAttribute() accessor |

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
| LandUseAndZoningFee | land_use_and_zoning_fees | belongsTo: occupancySubGroup. Range-based locational clearance fees (162 rows) |
| CertificationZoningFee | certification_zoning_fees | belongsTo: occupancySubGroup. Flat certification fee (P500) |

### Auth/System Models

| Model | Table | Notes |
|-------|-------|-------|
| User | users | HasRoles (Spatie), LogsActivity, SoftDeletes |
| Province | provinces | hasMany: cities |
| City | cities | belongsTo: province. hasMany: barangays |
| Barangay | barangays | belongsTo: city |

---

## Controllers

### ApplicationController (BP only)
| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /applications | List BP applications with search/status filters |
| create | GET /applications/create | New BP form |
| store | POST /applications | Create BP with occupancy groups, auto-number |
| show | GET /applications/{id} | Detail view with all relationships |
| edit | GET /applications/{id}/edit | Edit form |
| update | PUT /applications/{id} | Update with occupancy groups |
| submit | POST /applications/{id}/submit | draft → submitted (or zoning_assessed) |
| cancel | POST /applications/{id}/cancel | → cancelled with reason |
| printForm | GET /applications/{id}/print | HTML print view |

### OccupancyApplicationController (OP only)
| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /occupancy-applications | List OP applications with search/status filters |
| create | GET /occupancy-applications/create | New OP form |
| store | POST /occupancy-applications | Create OP with occupancy groups, auto-number |
| show | GET /occupancy-applications/{id} | Detail view |
| edit | GET /occupancy-applications/{id}/edit | Edit form |
| update | PUT /occupancy-applications/{id} | Update OP |
| submit | POST /occupancy-applications/{id}/submit | draft → submitted → engineering_assessed (skips zoning) |
| cancel | POST /occupancy-applications/{id}/cancel | → cancelled with reason |
| printForm | GET /occupancy-applications/{id}/print | HTML print view |

### ZoningController
| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /zoning | List BP applications with status for_zoning_assessment |
| assess | GET /zoning/{id} | Zoning assessment form with application details + fee items |
| store | POST /zoning/{id} | Save compliance fields (updateOrCreate) |
| autoCompute | POST /zoning/{id}/auto-compute | Auto-compute zoning fees from land_use_and_zoning_fees + certification_zoning_fees |
| addItem | POST /zoning/{id}/add-item | Manually add fee item |
| removeItem | DELETE /zoning/item/{id} | Remove fee item |
| finalize | POST /zoning/{id}/finalize | Finalize assessment + for_zoning_assessment → zoning_assessed |
| skip | POST /zoning/{id}/skip | Bypass locational clearance |

### ZoningFeeController (Settings)
| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /settings/zoning-fees | Land use & zoning fee settings (grouped by occupancy group/sub-group) |
| update | PUT /settings/zoning-fees/{id} | Update a land_use_and_zoning_fees row |
| store | POST /settings/zoning-fees/{subGroup} | Add new fee schedule row for sub-group |
| updateCert | PUT /settings/zoning-fees/cert/{id} | Update certification fee amount |
| destroy | DELETE /settings/zoning-fees/{id} | Delete a fee schedule row |

### AssessmentController
| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /assessments | BP assessment list |
| occupancyIndex | GET /assessments/occupancy | OP assessment list |
| assess | GET /assessments/{id} | BP fee item entry form |
| addItem | POST /assessments/{id}/item | BP add line item |
| removeItem | DELETE /assessments/item/{id} | Remove item |
| summary | GET /assessments/{id}/summary | BP summary view |
| finalize | POST /assessments/{id}/finalize | BP → engineering_assessed |
| print | GET /assessments/{id}/print | BP PDF summary |
| assessOp | GET /assessments/op/{occupancyApplication} | OP fee item entry form |
| addItemOp | POST /assessments/op/{occupancyApplication}/item | OP add line item |
| finalizeOp | POST /assessments/op/{occupancyApplication}/finalize | OP → engineering_assessed |
| summaryOp | GET /assessments/op/{occupancyApplication}/summary | OP summary view |
| printOp | GET /assessments/op/{occupancyApplication}/print | OP PDF summary |

### BillingController
| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /billing | Applications ready for billing (merges BP + OP) |
| generate | POST /billing/{id}/generate | Create BP billing → billed |
| generateOp | POST /billing/op/{occupancyApplication}/generate | Create OP billing → billed |
| print | GET /billing/{id}/print | PDF billing statement |

### CollectionController
| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /collections | Active collections list (merges BP + OP) |
| create | GET /collections/{id}/pay | BP payment form |
| store | POST /collections/{id}/pay | BP record payment → paid |
| createOp | GET /collections/op/{occupancyApplication}/pay | OP payment form |
| storeOp | POST /collections/op/{occupancyApplication}/pay | OP record payment → paid |
| receipt | GET /collections/{id}/receipt | Official receipt PDF |
| voidForm | GET /collections/void | Void OR form |
| processVoid | POST /collections/void | Process void |

### PermitController
| Method | Route | Purpose |
|--------|-------|---------|
| buildingIndex | GET /permits/building | BP permit list |
| occupancyIndex | GET /permits/occupancy | OP permit list |
| generate | POST /permits/{id}/generate | Generate BP permit → permit_generated |
| generateOp | POST /permits/op/{occupancyApplication}/generate | Generate OP permit → permit_generated |
| print | GET /permits/{id}/print | PDF permit document (uses $permit->applicationable) |
| zoningCertification | GET /permits/{id}/zoning-cert | Zoning cert PDF |
| locationalClearance | GET /permits/{id}/locational | Locational clearance PDF |
| evaluationReport | GET /permits/{id}/evaluation | Evaluation report PDF |

### Other Controllers

| Controller | Key Methods |
|-----------|------------|
| DashboardController | index (KPIs: aggregates both BP + OP tables for stats, revenue, charts) |
| OnlineApplicationController | dashboard, create, store (branches BP/OP), show, uploadRequirements, storeRequirement, track, downloadPermit + OP methods: showOp, trackOp, uploadOp, downloadOp |
| ReportController | permits, revenue, collections, generate (Excel/PDF) |
| SettingsController | index, update, users CRUD, roles, fees, signatories |
| FeeScheduleController | showCategory, showType, storeType, updateType, storeSchedule, updateSchedule, destroySchedule |
| ProfileController | edit, update |

---

## Services (8)

| Service | Key Methods |
|---------|------------|
| ApplicationService | generateApplicationNumber(), create(), update(), submit(), transitionStatus() — BP only |
| OccupancyApplicationService | generateApplicationNumber(), create(), update(), submit(), transitionStatus() — OP only, mirrors ApplicationService for occupancy_applications table |
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

## DTOs (4)

| DTO | Purpose |
|-----|---------|
| ApplicationDTO | Readonly — all BP application fields from request |
| OccupancyApplicationDTO | Readonly — shared + OP-specific fields from request (no cost fields, no engineer/PEE/SEW) |
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

### Application Views (BP)
- `applications/index.blade.php` — BP list with filters (no permit_type filter)
- `applications/form.blade.php` — BP create/edit form
- `applications/show.blade.php` — BP detail view

### Occupancy Application Views (OP)
- `occupancy-applications/index.blade.php` — OP list with filters
- `occupancy-applications/form.blade.php` — OP create/edit form
- `occupancy-applications/show.blade.php` — OP detail view

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
| AppServiceProvider | Standard Laravel provider + morph map registration (bp → Application, op → OccupancyApplication) |
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
