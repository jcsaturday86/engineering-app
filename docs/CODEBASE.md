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
│   ├── Http/Controllers/  (14 + Auth controllers)
│   ├── Models/            (32 Eloquent models)
│   ├── Notifications/     (4 notification classes)
│   ├── Providers/         (AppServiceProvider, SelfHealingServiceProvider)
│   └── Services/          (8 service classes)
├── database/
│   ├── migrations/        (22+ migration files)
│   ├── seeders/           (9 seeders)
│   └── factories/
├── resources/views/       (40+ Blade templates)
├── routes/web.php         (100+ routes)
├── docs/                  (this documentation)
└── CLAUDE.md
```

---

## Models (32)

### Core Transaction Models

| Model | Table | Notes |
|-------|-------|-------|
| Application | applications | BP only. Implements PermitApplicationContract + HasPermitApplicationBehavior. getPermitTypeCode() = 'BP' |
| OccupancyApplication | occupancy_applications | OP only. Same contract/trait. getPermitTypeCode() = 'OP' |
| ApplicationOccupancyGroup | application_occupancy_groups | morphTo: applicationable |
| ApplicationRequirement | application_requirements | morphTo: applicationable |
| Assessment | assessments | morphTo: applicationable. hasMany: assessmentItems. SoftDeletes, LogsActivity |
| AssessmentItem | assessment_items | belongsTo: assessment, feeCategory, feeType. SoftDeletes |
| ZoningAssessment | zoning_assessments | belongsTo: application (1:1, BP only). SoftDeletes |
| Billing | billings | morphTo: applicationable. hasMany: billingItems. SoftDeletes |
| BillingItem | billing_items | belongsTo: billing |
| Collection | collections | morphTo: applicationable. hasMany: collectionDetails. hasOne: voidTransaction. SoftDeletes, LogsActivity |
| CollectionDetail | collection_details | belongsTo: collection |
| VoidTransaction | void_transactions | belongsTo: collection |
| Permit | permits | morphTo: applicationable. SoftDeletes, LogsActivity |
| Document | documents | morphTo: applicationable |

### Reference/Lookup Models

| Model | Table |
|-------|-------|
| PermitType | permit_types |
| ApplicationType | application_types |
| ScopeOfWork | scope_of_works |
| FormOfOwnership | form_of_ownerships |
| OccupancyGroup | occupancy_groups |
| OccupancySubGroup | occupancy_sub_groups |
| OccupancyDivision | occupancy_divisions |
| BuildingPart | building_parts |
| Signatory | signatories |
| LandClassification | land_classifications |

### Fee Schedule Models

| Model | Table | Notes |
|-------|-------|-------|
| FeeCategory | fee_categories | hasMany: feeTypes |
| FeeType | fee_types | hasMany: feeSchedules |
| FeeSchedule | fee_schedules | standard rate rows |
| LandUseAndZoningFee | land_use_and_zoning_fees | 162 rows, locational clearance rates |
| CertificationZoningFee | certification_zoning_fees | P500 flat cert fee |
| LandUseAndZoningOtherFee | land_use_and_zoning_other_fees | Variance/Non-Conforming |

### Auth/System Models
User (HasRoles, LogsActivity, SoftDeletes), Province, City, Barangay.

---

## Controllers

### ApplicationController (BP only)
index, create, store, show, edit, update, submit, cancel, printForm

### OccupancyApplicationController (OP only)
index, create, store, show, edit, update, submit, cancel, printForm

### ZoningController
index, assess, store, autoCompute, addItem, removeItems (bulk), removeItem, finalize, skip

**Private helpers:** `zoningAssessmentIsFinalized()` / `abortIfZoningFinalized()` — store, autoCompute, addItem, and remove methods abort 403 once the zoning assessment is finalized.

### ZoningFeeController (Settings)
index, update, store, updateCert, updateOther, destroy

### AssessmentController

| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /assessments | BP assessment list |
| occupancyIndex | GET /assessments/occupancy | OP list |
| assess | GET /assessments/{id} | Tabbed fee entry (Construction/Electrical/Mechanical/…) |
| addConstructionItem | POST /assessments/{id}/construction-item | BOPMS-style: Part+Division+Area → auto fee lookup |
| addElectricalItem | POST /assessments/{id}/electrical-item | BOPMS-style: 7 types, auto inspection % |
| addMechanicalItem | POST /assessments/{id}/mechanical-item | BOPMS-style: equipment type+unit → base fee + NBC inspection fee |
| addPlumbingItem | POST /assessments/{id}/plumbing-item | BOPMS-style: 22 PLUMB_* types, per_unit + range_based |
| addElectronicsItem | POST /assessments/{id}/electronics-item | BOPMS-style: 11 ELECT_* types |
| addAccessoryItem | POST /assessments/{id}/accessory-item | ACC_BLDG tab |
| addAccFeeItem | POST /assessments/{id}/acc-fee-item | ACC_FEE tab |
| addSurchargeItem | POST /assessments/{id}/surcharge-item | SURCHARGE tab |
| addItem | POST /assessments/{id}/item | Generic item for other tabs |
| removeItem | DELETE /assessments/item/{id} | Remove item (guarded when finalized) |
| finalize | POST /assessments/{id}/finalize | BP → engineering_assessed; redirects to ?tab=SUMMARY |
| summary | GET /assessments/{id}/summary | BP summary view |
| print | GET /assessments/{id}/print | PDF summary (barcode + building_official signatory) |
| assessOp | GET /assessments/op/{op} | OP fee entry |
| addItemOp | POST /assessments/op/{op}/item | OP generic item |
| addOccupancyFeeItem | POST /assessments/op/{op}/occupancy-fee | BOPMS-style: 8 OCC_* types; range_based (excess_every), per_unit, percentage |
| finalizeOp | POST /assessments/op/{op}/finalize | OP → engineering_assessed; redirects to ?tab=SUMMARY |
| summaryOp | GET /assessments/op/{op}/summary | OP summary |
| printOp | GET /assessments/op/{op}/print | OP PDF |

**Private helpers:**
- `resolveInspectionFee(string $code, float $unit): array` — maps MECH_* code → INSP_* fee type (MECH_INSP category), does range or first-row lookup, returns {fee, excess_threshold, excess_fee, every, method}. Three methods: flat (range-band fixed), per_unit (rate × unit), tiered (cumulative for elevators).
- `calculateTotals(Assessment $assessment): array` — returns subtotal, inspection, filing, processing, total.
- `redirectIfFinalized(Assessment, PermitApplicationContract): ?RedirectResponse` — called by every add/remove method; when assessment status = finalized, redirects to the assess page `?tab=SUMMARY` with an error flash. `doPrint()` also generates a Code 128 barcode (picqer BarcodeGeneratorPNG, base64) and loads the `building_official` signatory for the PDF.

### BillingController
index, generate (BP), generateOp (OP), print

### CollectionController
index, create/store (BP), createOp/storeOp (OP), receipt, voidForm, processVoid

### PermitController
buildingIndex, occupancyIndex, generate (BP), generateOp (OP), print, zoningCertification, locationalClearance, evaluationReport

### Other Controllers
DashboardController, OnlineApplicationController, ReportController, SettingsController, FeeScheduleController, ProfileController

---

## Services (8)

| Service | Purpose |
|---------|---------|
| ApplicationService | BP CRUD, numbering, status transitions |
| OccupancyApplicationService | OP CRUD, numbering, status transitions |
| AssessmentService | finalize() — recalculate totals, mark finalized |
| FeeComputationService | computeFee() — 6 methods with excess/min/max |
| BillingService | generateBilling() — create billing from assessments |
| CollectionService | recordPayment() — create collection, update billing |
| PermitService | generatePermit() — create permit with auto-numbering |
| SettingService | get(), set() — system settings |

## Actions (4)
CreateApplicationAction, FinalizeAssessmentAction, GeneratePermitAction, ProcessPaymentAction

## DTOs (4)
ApplicationDTO, OccupancyApplicationDTO, AssessmentItemDTO, CollectionDTO

## Enums (5)

| Enum | Values |
|------|--------|
| ApplicationStatus | draft, submitted, for_zoning_assessment, zoning_assessed, engineering_assessed, billed, paid, permit_generated, released, cancelled |
| AssessmentType | building, occupancy, zoning |
| ComputationMethod | fixed, per_unit, range_based, cumulative_range, percentage, formula |
| PaymentMode | cash, check, online |
| PermitTypeCode | BP, OP, FP, EP, DP, SP, ELP, MP, PP, ECP |

---

## Views

### Layouts / Partials
`layouts/app.blade.php`, `layouts/guest.blade.php`, `partials/sidebar-nav.blade.php`

### Application Views
BP: `applications/index`, `form`, `show`
OP: `occupancy-applications/index`, `form`, `show`

### Assessment Views
`assessments/assess.blade.php` — tabbed: Construction, Electrical, Mechanical, Plumbing, Electronics, Accessories, Accessory, Surcharges, Summary. Excluded from tabs: ZONING_LC, ZONING_CERT, ANN_INSP, VIOLATION, MECH_INSP.

### Other Views
`zoning/`, `billing/`, `collections/`, `permits/`, `online/`, `dashboard/`, `settings/`, `reports/`, `auth/`

### PDF Templates (`resources/views/pdf/`)
application-form, building-permit, occupancy-permit, assessment-summary, billing-statement, official-receipt, zoning-certification, locational-clearance, evaluation-report, report

---

## Providers

| Provider | Purpose |
|----------|---------|
| AppServiceProvider | Morph map: bp → Application, op → OccupancyApplication |
| SelfHealingServiceProvider | Auto DB + migrations + seeds on boot |

## Seeders (9)

| Seeder | Data |
|--------|------|
| RolePermissionSeeder | 9 roles, 30+ permissions |
| ReferenceDataSeeder | Permit types, app types, scopes, ownerships, building parts, land classifications, signatories, fee categories (incl. MECH_INSP) |
| OccupancyGroupSeeder | 10 groups A–J, 40+ sub-groups |
| FeeScheduleSeeder | Complete fee structure: CONST, ELEC, MECH, MECH_INSP (29 INSP_* types/55 schedules from BOPMS ann_inspection_f* tables), PLUMB, ELEC_INSP, OCC, SURCHARGE, ZONING fee tables |
| GeoDataSeeder | ~42K barangays (Philippine PSA data, 2.5MB) |
| SettingsSeeder | System settings (electrical_inspection_percentage, filing/processing defaults) |
| AdminUserSeeder | Default admin user |
| ApplicationSeeder | 5 BP + 5 OP test records |
| AssessmentTestSeeder | Assessment test data |

## Third-Party Packages

| Package | Purpose |
|---------|---------|
| spatie/laravel-permission | RBAC |
| spatie/laravel-activitylog | Audit trail |
| barryvdh/laravel-dompdf | PDF generation |
| maatwebsite/excel | Excel exports |
| picqer/php-barcode-generator | Code 128 barcode on assessment PDF |
