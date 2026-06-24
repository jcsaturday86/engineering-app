# Workflows

---

## Building Permit (BP) Workflow

### State Transitions

```
 ┌─────────┐     submit      ┌───────────┐   finalize    ┌─────────────────┐
 │  draft  │ ──────────────→ │ submitted │ ───────────→ │ zoning_assessed │
 └─────────┘                 └───────────┘  (planning)   └─────────────────┘
                                                                │
                              ┌──────────────────────┐          │ finalize
                              │ engineering_assessed │ ←────────┘ (engineering)
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
| 1 | draft | Engineering Staff | ApplicationController::store | Create application with occupancy groups |
| 2 | submitted | Engineering Staff | ApplicationController::submit | Submit for processing |
| 3 | zoning_assessed | Planning Staff | ZoningController::finalize | Complete zoning assessment |
| 4 | engineering_assessed | Engineering Officer | AssessmentController::finalize | Finalize fee assessment |
| 5 | billed | Finance | BillingController::generate | Generate billing from assessment |
| 6 | paid | Treasury Staff | CollectionController::store | Record payment (OR) |
| 7 | permit_generated | Engineering Officer | PermitController::generate | Generate permit document |
| 8 | released | Engineering Officer | Manual | Release permit to applicant |

### Skip Locational Clearance
When `applies_to = "SKIP_LC"`, submission skips the planning office:
- `draft` → `submitted` → **`zoning_assessed`** (automatic, bypasses planning)

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

---

## State Machine Implementation

### ApplicationStatus Enum (`app/Enums/ApplicationStatus.php`)

```php
enum ApplicationStatus: string {
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
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
| draft | submitted, cancelled |
| submitted | zoning_assessed, engineering_assessed, cancelled |
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

```
1. Select fee category (e.g., "Construction Fees")
2. Select fee type (e.g., "Division A1 - Residential")
3. Enter quantity (e.g., floor area = 150 sqm)
4. FeeComputationService calculates:
   a. Find matching fee_schedule row (range_from <= 150 <= range_to)
   b. Apply computation method
   c. Apply excess if value > excess_threshold
   d. Apply min/max constraints
5. Create assessment_item with computed amount
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
| `ApplicationSubmittedNotification` | Application submitted | Engineering users |
| `AssessmentCompleteNotification` | Assessment finalized | Applicant |
| `PaymentPostedNotification` | Payment recorded | Applicant |
| `ApplicationApprovedNotification` | Permit generated | Client user |
