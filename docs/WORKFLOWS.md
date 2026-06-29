# Workflows

---

## Building Permit (BP) Workflow

### State Transitions

```
 ┌─────────┐     submit      ┌────────────────────────┐   finalize    ┌─────────────────┐
 │  draft  │ ──────────────→ │ for_zoning_assessment │ ───────────→ │ zoning_assessed │
 └─────────┘                 └────────────────────────┘  (planning)  └─────────────────┘
       │ (skip LC)                                                          │
       └──────────→ ┌───────────┐                                           │ finalize
                    │ submitted │                                            │ (engineering)
                    └───────────┘                                            │
                         │               ┌──────────────────────┐           │
                         └──────────────→│ engineering_assessed │ ←─────────┘
                                         └──────────────────────┘
                                       │
                              ┌────────┐  generate billing
                              │ billed │ ←────────
                              └────────┘
                                  │
                              ┌──────┐  record payment
                              │ paid │ ←────────
                              └──────┘
                                  │
                         ┌───────────────────┐  generate permit
                         │ permit_generated  │ ←────────
                         └───────────────────┘
                                  │
                            ┌──────────┐  manual release
                            │ released │ ←────────
                            └──────────┘

  Any state ──→ [cancelled] (with reason)
```

### Step Details

| Step | Status | Actor | Controller | Action |
|------|--------|-------|-----------|--------|
| 1 | draft | Engineering Staff | ApplicationController::store | Create BP application with occupancy groups |
| 2a | for_zoning_assessment | Engineering Staff | ApplicationController::submit | Submit → routed to Planning Office |
| 2b | submitted | Engineering Staff | ApplicationController::submit | Submit with skip LC → routed to Engineering |
| 3 | zoning_assessed | Planning Staff | ZoningController::finalize | Complete zoning assessment with auto-computed fees |
| 4 | engineering_assessed | Engineering Officer | AssessmentController::finalize | Finalize fee assessment |
| 5 | billed | Finance | BillingController::generate | Generate billing from assessment |
| 6 | paid | Treasury Staff | CollectionController::store | Record payment (OR) |
| 7 | permit_generated | Engineering Officer | PermitController::generate | Generate permit document |
| 8 | released | Engineering Officer | Manual | Release permit to applicant |

> **Note:** ApplicationController handles BP applications only (from the `applications` table).

### Skip Locational Clearance
When `applies_to = "SKIP_LC"`, submission skips the planning office:
- `draft` → `submitted` (bypasses planning, goes directly to Engineering Assessment)

Without skip LC:
- `draft` → `for_zoning_assessment` → `zoning_assessed` (after planning assessment + fee computation)

### Zoning Fee Auto-Compute
When a planning officer opens a zoning assessment, they can click "Auto Compute" to:
1. Look up `land_use_and_zoning_fees` by each occupancy sub-group + total estimated cost
2. Compute: `amount + ((totalCost - excess_of) × percentage)` per sub-group
3. Add `certification_zoning_fees` flat fee (P500)
4. Create assessment items in the `assessment_items` table (assessment_type = 'zoning')

---

## Occupancy Permit (OP) Workflow

### State Transitions

```
 ┌─────────┐     submit      ┌───────────┐   finalize    ┌──────────────────────┐
 │  draft  │ ──────────────→ │ submitted │ ───────────→ │ engineering_assessed │
 └─────────┘                 └───────────┘ (engineering)  └──────────────────────┘
                                                                │
                                                       (same as BP from here)
                                                                ↓
                                               billed → paid → permit_generated → released
```

**Key difference:** OP skips `zoning_assessed` — goes directly from `submitted` to `engineering_assessed`.

### OP Step Details

| Step | Status | Actor | Controller | Action |
|------|--------|-------|-----------|--------|
| 1 | draft | Engineering Staff | OccupancyApplicationController::store | Create OP application (from `occupancy_applications` table) |
| 2 | submitted | Engineering Staff | OccupancyApplicationController::submit | Submit for processing (skips zoning) |
| 3 | engineering_assessed | Engineering Officer | AssessmentController::finalizeOp | Finalize OP fee assessment |
| 4 | billed | Finance | BillingController::generateOp | Generate billing from OP assessment |
| 5 | paid | Treasury Staff | CollectionController::storeOp | Record OP payment (OR) |
| 6 | permit_generated | Engineering Officer | PermitController::generateOp | Generate occupancy permit document |
| 7 | released | Engineering Officer | Manual | Release permit to applicant |

> **Note:** OccupancyApplicationController handles OP applications only (from the `occupancy_applications` table). Downstream controllers (Assessment, Billing, Collection, Permit) have parallel `*Op()` methods for OP processing.

---

## State Machine Implementation

### ApplicationStatus Enum (`app/Enums/ApplicationStatus.php`)

```php
enum ApplicationStatus: string {
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case FOR_ZONING_ASSESSMENT = 'for_zoning_assessment';
    case ZONING_ASSESSED = 'zoning_assessed';
    case ENGINEERING_ASSESSED = 'engineering_assessed';
    case BILLED = 'billed';
    case PAID = 'paid';
    case PERMIT_GENERATED = 'permit_generated';
    case RELEASED = 'released';
    case CANCELLED = 'cancelled';
}
```

### Allowed Transitions

| From | To (allowed) |
|------|-------------|
| draft | submitted, for_zoning_assessment, cancelled |
| submitted | engineering_assessed, cancelled |
| for_zoning_assessment | zoning_assessed, cancelled |
| zoning_assessed | engineering_assessed, cancelled |
| engineering_assessed | billed, cancelled |
| billed | paid, cancelled |
| paid | permit_generated, cancelled |
| permit_generated | released, cancelled |
| released | (terminal) |
| cancelled | (terminal) |

Validated via `ApplicationStatus::canTransitionTo()`.

---

## Fee Computation Flow

### Computation Methods

| Method | Description | Example |
|--------|-------------|---------|
| `fixed` | Flat fee amount | Filing fee: 200.00 |
| `per_unit` | Fee × quantity | 5.00/sqm × 150 sqm = 750.00 |
| `range_based` | Lookup fee by range | Floor area 101-200: fee = 500.00 |
| `cumulative_range` | Sum across all ranges up to value | Tiered rate structure |
| `percentage` | Percentage of base amount | 1% of construction cost |
| `formula` | Custom formula evaluation | Stored as text |

### Fee Schedule Lookup

```
FeeCategory (by permit_type)
  └── FeeType (computation_method, has_excess, has_minimum, has_maximum)
       └── FeeSchedule (range_from/to, fixed_fee, fee_per_unit, excess_threshold/fee/every)
            └── Optional: occupancy_division_id, occupancy_sub_group_id
```

### Assessment Item Creation Flow

#### Construction Tab (BOPMS-style)
```
1. Select Part of Building (Building Residential, Building Area Office, Carport, Others)
2. Select Division (auto-filtered by application's occupancy groups)
3. Enter Area (sq.m.)
4. Server looks up FeeType by CONST_{division.code}, finds FeeSchedule by area range
5. Compute: amount = area × fee_per_unit
6. Create assessment_item with computed amount + computation_details JSON
```

#### Electrical Tab (BOPMS-style)
```
1. Select Electrical Fee Type (7 options):
   - Total Connected Load (kVA) → ELEC_TCL
   - Total Transformer Capacity (kVA) → ELEC_TRANS
   - Total UPS/Generator Capacity (kVA) → ELEC_UPS
   - Power Supply Pole Location → ELEC_POLE
   - Guying Attachment → ELEC_POLE
   - Electric Meter Fee → ELEC_MISC_METER
   - Wiring Permit Issuance → ELEC_MISC_WIRING
2. For kVA types: enter capacity → server looks up FeeSchedule by range
   Compute: base_fee = fixed_fee + (kva × fee_per_unit)
3. For pole/misc types: select sub-type → server looks up fixed_fee
4. Inspection fee auto-computed: base_fee × percentage (from settings)
5. Total amount = base_fee + inspection_fee
```

#### Other Tabs (Generic)
```
1. Select fee type from category dropdown
2. Enter quantity and unit fee
3. Amount = quantity × unit_fee
4. Create assessment_item
```

---

## Role-Based Access Per Step

| Workflow Step | Required Permission | Typical Role |
|--------------|-------------------|--------------|
| Create application | `create-applications` | engineering-staff |
| Submit application | `submit-applications` | engineering-staff |
| Zoning assessment | `create-zoning`, `finalize-zoning` | planning-staff/officer |
| Skip locational | `skip-zoning` | planning-officer |
| Engineering assessment | `create-assessments`, `finalize-assessments` | engineering-staff/officer |
| Generate billing | `generate-billing` | engineering-officer |
| Record payment | `create-collections` | treasury-staff |
| Void payment | `void-collections` | treasury-officer |
| Generate permit | `generate-permits` | engineering-officer |
| Print permit | `print-permits` | engineering-staff |
| Release permit | `release-permits` | engineering-officer |

---

## Online Application Flow (Client Portal)

```
Client registers (/register)
  ↓
Client logs in (/login)
  ↓
Client creates application (/online/apply)
  ↓ status = 'submitted' (skips draft)
Client uploads requirements (/online/application/{id}/upload)
  ↓
Client tracks status (/online/application/{id}/track)
  ↓ (staff processes through workflow)
Client downloads permit (/online/application/{id}/download)
  ↓ (only when status = released)
```

---

## Document Generation

### PDF Templates (`resources/views/pdf/`)

| Template | Purpose | Trigger |
|----------|---------|---------|
| `application-form` | Print application form | ApplicationController::printForm |
| `building-permit` | Building permit document | PermitController::print (BP) |
| `occupancy-permit` | Occupancy permit document | PermitController::print (OP) |
| `assessment-summary` | Assessment fee summary | AssessmentController::print |
| `billing-statement` | Billing statement | BillingController::print |
| `official-receipt` | Official receipt (OR) | CollectionController::receipt |
| `zoning-certification` | Zoning certification | PermitController::zoningCertification |
| `locational-clearance` | Locational clearance | PermitController::locationalClearance |
| `evaluation-report` | Evaluation report | PermitController::evaluationReport |
| `report` | Generic report export | ReportController::generate |

### Notification Classes

| Notification | Trigger | Recipients |
|-------------|---------|-----------|
| `ApplicationSubmittedNotification` | Application submitted (BP or OP) | Engineering users |
| `AssessmentCompleteNotification` | Assessment finalized (BP or OP) | Applicant |
| `PaymentPostedNotification` | Payment recorded (BP or OP) | Applicant |
| `ApplicationApprovedNotification` | Permit generated (BP or OP) | Client user |

> All 4 notification classes accept `Model` instead of `Application` to support both BP and OP application types.
