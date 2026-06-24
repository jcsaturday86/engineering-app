# BOPMS Legacy Codebase Reference

> Reference system at `C:\Users\Jay Carlou\Documents\BOPMS\engineering\engineering`
> Framework: Laravel (PHP) | Database: MySQL (`db_ebps`) | Host: 127.0.0.1:3306

---

## Directory Structure

```
engineering/
├── app/
│   ├── Http/Controllers/
│   │   ├── TransactionController.php    (398,500 bytes — ALL transaction logic)
│   │   ├── SettingsController.php       (89,569 bytes — config & fee management)
│   │   ├── ReportController.php         (19,825 bytes — reports & print)
│   │   ├── HomeController.php
│   │   └── Auth/
│   ├── [160+ Model files in app/ root]  (no Models/ namespace)
│   ├── Providers/
│   ├── Exceptions/
│   └── Console/
├── database/
│   ├── backup/
│   │   └── db_engineering.sql           (full schema & sample data)
│   ├── migrations/                      (3 standard Laravel migrations only)
│   ├── factories/
│   └── seeds/
├── routes/
│   ├── web.php                          (41,033 bytes)
│   └── api.php
├── resources/views/                     (148 Blade templates)
├── config/
├── public/
├── storage/
├── .env
└── composer.json
```

---

## Controllers

### TransactionController (~9,000 lines)

Handles ALL business logic for BP, OP, and BFP in one monolithic file.

**Application Methods:**
| Method | Purpose |
|--------|---------|
| `application()` | Display new BP application form |
| `applicationSave()` | Save new BP, auto-generate number, init assessment fees |
| `applicationList()` | List all BP applications (state != 0) |
| `applicationEdit()` / `applicationEditSave()` | Edit BP application |
| `applicationView()` | View BP details |
| `applicationCancel()` | Cancel BP (set state=0) |

**Assessment Methods (BP):**
| Method | Purpose |
|--------|---------|
| `forAssessment()` | Start assessment for a BP |
| `constructionFeeSave()` | Calculate construction fees by division/floor area |
| `assessmentElectrical()` / `electricalFeeSave()` | Electrical fee calculation |
| `assessmentMechanical()` / `mechanicalFeeSave()` | Mechanical fee calculation |
| `assessmentPlumbing()` / `plumbingFeeSave()` | Plumbing fee calculation |
| `assessmentElectronics()` / `electronicsFeeSave()` | Electronics fee calculation |
| `assessmentAccessories()` / `accessoriesBldgFeeSave()` | Accessory building fees |
| `assessmentAccessoryFee()` / `assessmentAccessoryFeeSave()` | Add-on accessory fees |
| `assessmentZoning()` | Zoning/locational clearance fees |
| `assessmentSurchargeFee()` | Surcharge assessment |
| `assessmentFsec()` / `assessmentFsecSave()` | Fire Safety Evaluation Clearance |
| `assessmentBfp()` / `assessmentBfpSave()` | BFP finalization |
| `assessmentSummary()` | Display all calculated fees |

**Occupancy Permit Methods:**
| Method | Purpose |
|--------|---------|
| `occupancyApplication()` | Display OP form |
| `occupancyApplicationSave()` | Save new OP |
| `occupancyAssessment()` | OP fee assessment |
| `assessmentFsic()` / `assessmentFsicSave()` | Fire Safety Inspection Certificate |

**Collection/Payment Methods:**
| Method | Purpose |
|--------|---------|
| `collection()` | List pending collections (state 3 & 4) |
| `collectionPrevious()` | List past collections (state 1) |
| `payment()` | Payment form for BP |
| `paymentSave()` | Process payment, create OR, update state to 5 |
| `payment_occupancy()` | Payment form for OP |

### SettingsController (~2,500 lines)

| Method Group | Purpose |
|-------------|---------|
| `user()`, `userSave()`, `userEdit()`, `userEditSave()` | User management |
| 100+ fee methods | CRUD for each of the 100+ fee tables |
| Group/Division management | Building classification admin |
| Barangay/Signatory settings | Reference data admin |

### ReportController

| Method | Purpose |
|--------|---------|
| `generateBuildingPermit()` | Generate BP document |
| `generateOccupancyPermit()` | Generate OP document |
| `generateFsec()` | Generate FSEC clearance |
| `generateFsic()` | Generate FSIC certificate |
| `generateLocationalClearance()` | Locational clearance PDF |
| `ctoRevenueReport()` | CTO revenue reports |
| `assessmentPrint()` | Print assessment summary |

---

## Models (~160 files)

All models reside in `app/` root (no `Models/` namespace). Most are bare Eloquent models with `EncryptableDbAttribute` trait for personal data fields.

### Application Models
| Model | Table |
|-------|-------|
| `ApplicationBuildingPermits` | `application_building_permits` |
| `ApplicationOccupancyPermits` | `application_occupancy_permits` |
| `ApplicationBuildingPermitsGroups` | `application_building_permits_groups` |
| `ApplicationOccupancyPermitsGroups` | `application_occupancy_permits_groups` |
| `ApplicationTypes` | `application_types` |
| `ApplicationComplexities` | `application_complexities` |

### Assessment Models (BP)
| Model | Table |
|-------|-------|
| `BpAssessmentFees` | `bp_assessment_fees` |
| `BpAssessmentElectricalFees` | `bp_assessment_electrical_fees` |
| `BpAssessmentMechanicalFees` | `bp_assessment_mechanical_fees` |
| `BpAssessmentPlumbingFees` | `bp_assessment_plumbing_fees` |
| `BpAssessmentElectronics` | `bp_assessment_electronics` |
| `BpAssessmentAccessoryBuildingFees` | `bp_assessment_accessory_building_fees` |
| `BpAssessmentAccessoryFees` | `bp_assessment_accessory_fees` |
| `BpAssessmentZoningFees` | `bp_assessment_zoning_fees` |
| `BpAssessmentSurchargeFees` | `bp_assessment_surcharge_fees` |
| `BpAssessmentFsec` | `bp_assessment_fsec` |

### Assessment Models (OP)
| Model | Table |
|-------|-------|
| `OccAssessmentFees` | `occ_assessment_fees` |
| `OccAssessmentFsics` | `occ_assessment_fsics` |

### Fee Schedule Models (~100+)
Organized by discipline: construction, electrical, mechanical, plumbing, electronics, accessories, zoning, BFP, occupancy, annual inspection. Each fee type has its own table and model.

### Collection & Permit Models
| Model | Table |
|-------|-------|
| `Collections` | `collections` |
| `CollectionDetails` | `collection_details` |
| `GenerateBuildingPermit` | `generate_building_permits` |
| `GenerateOccupancyPermit` | `generate_occupancy_permits` |

### Reference Models
`Groups`, `SubGroups`, `Divisions`, `ScopeOfWorks`, `FormOfOwnerships`, `Occupancies`, `BuildingParts`, `Signatories`, `Provinces`, `Cities`, `Barangays`, `LandClassifications`, `Users`, `AccessLevels`

---

## Routes (web.php — 41KB)

### Application Routes
| Method | URI | Controller Method |
|--------|-----|-------------------|
| GET | `/application` | `TransactionController@application` |
| POST | `/application` | `TransactionController@applicationSave` |
| GET | `/applicationList` | `TransactionController@applicationList` |
| GET | `/applicationEdit/{id}` | `TransactionController@applicationEdit` |
| POST | `/applicationEditSave/{id}` | `TransactionController@applicationEditSave` |
| GET | `/applicationView/{id}` | `TransactionController@applicationView` |
| GET | `/applicationCancel/{id}` | `TransactionController@applicationCancel` |

### Assessment Routes
Pattern: `GET /assessment*/{id}` shows form, `POST /assessment*Save/{id}` saves.

`/forAssessment`, `/constructionFee`, `/assessmentElectrical`, `/assessmentMechanical`, `/assessmentPlumbing`, `/assessmentElectronics`, `/assessmentAccessories`, `/assessmentAccessoryFee`, `/assessmentSummary`, `/assessmentBfp`, `/assessmentFsec`, `/assessmentZoning`, `/assessmentSurchargeFee`

### Collection & Payment Routes
| Method | URI | Purpose |
|--------|-----|---------|
| GET | `/collection` | Pending collections |
| GET | `/collectionPrevious` | Past collections |
| GET | `/payment/{id}` | BP payment form |
| POST | `/paymentSave/{id}` | Process BP payment |
| GET | `/payment_occupancy/{id}` | OP payment form |
| GET | `/payment_or/{id}/{collectionId}` | Print Official Receipt |

### Settings Routes (Admin)
100+ routes for fee table CRUD, user management, group management, division management, barangay settings, signatory management.

### Print/Report Routes
`/generateBuildingPermit`, `/generateOccupancyPermit`, `/generateFsec`, `/generateFsic`, `/generateLocationalClearance`, `/ctoRevenueReport`, `/assessmentPrint/{id}`

---

## Views (148 Blade Templates)

| Category | Key Files | Size |
|----------|-----------|------|
| BP Application | `applicationBuildingPermit.blade.php` | 61,736 bytes |
| BP Edit | `applicationBuildingPermitEdit.blade.php` | 79,296 bytes |
| BP View | `applicationBuildingPermitView.blade.php` | 78,001 bytes |
| OP Application | `applicationOccupancyPermit.blade.php` | ~50KB |
| Assessment | `assessment*.blade.php` (12+ files) | Various |
| Collection | `collection.blade.php`, `collectionPrevious.blade.php` | Various |
| Payment | `payment.blade.php`, `paymentOr.blade.php` | Various |
| Fee Settings | 100+ individual fee management views | Various |
| Layout | `layouts/app.blade.php` | Base template |

---

## Middleware (Access Levels)

| Middleware | Access Level | Role |
|-----------|-------------|------|
| `AdminMiddleware` | 1 | Administrator — full access |
| `EasMiddleware` | 2 | Engineering — BP/OP applications + assessments |
| `PlanningMiddleware` | 5 | Planning — zoning/locational clearance |
| `BfpMiddleware` | 3 | BFP — fire safety (FSEC/FSIC) |
| `CtoMiddleware` | 4 | Treasury — collections/payments |

---

## Key Architectural Notes

- **No Service Layer** — All business logic lives in controllers (TransactionController alone is ~9,000 lines)
- **No Soft Deletes** — Records are cancelled by setting `state=0`, not deleted
- **No Activity Logging** — No audit trail of who changed what
- **Encryption** — Uses `betterapp/LaravelDbEncrypter` for PII fields (full_name, TIN, address, contact)
- **No Migrations** — Schema managed via raw SQL (only 3 standard Laravel migrations exist)
- **Separate Tables** — BP and OP have completely separate table hierarchies (applications, assessments, collections, permits)
- **Numeric States** — Workflow uses integer states (1-5) instead of named states
- **No DTOs/Enums** — Data passed directly from request to model
- **No Validation Classes** — Validation done inline in controller methods
