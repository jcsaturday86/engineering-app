# Workflows

---

## Building Permit (BP) Workflow

### State Transitions

```
 ┌─────────┐     submit      ┌────────────────────────┐   finalize    ┌─────────────────┐
 │  draft  │ ──────────────→ │ for_zoning_assessment  │ ───────────→ │ zoning_assessed │
 └─────────┘                 └────────────────────────┘  (planning)  └─────────────────┘
       │ (skip LC)                                                          │
       └──────────→ ┌───────────┐                                           │ finalize
                    │ submitted │                                            │ (engineering)
                    └───────────┘                                            │
                         │               ┌──────────────────────┐           │
                         └──────────────→│ engineering_assessed │ ←─────────┘
                                         └──────────────────────┘
                                                   │ generate billing
                                              ┌────────┐
                                              │ billed │
                                              └────────┘
                                                   │ record payment
                                               ┌──────┐
                                               │ paid │
                                               └──────┘
                                                   │ generate permit
                                        ┌───────────────────┐
                                        │ permit_generated  │
                                        └───────────────────┘
                                                   │ manual release
                                            ┌──────────┐
                                            │ released │
                                            └──────────┘

  Any state ──→ [cancelled] (with reason)
```

### Step Details

| Step | Status | Actor | Controller | Action |
|------|--------|-------|-----------|--------|
| 1 | draft | Engineering Staff | ApplicationController::store | Create BP |
| 2a | for_zoning_assessment | Engineering Staff | ApplicationController::submit | Route to Planning |
| 2b | submitted | Engineering Staff | ApplicationController::submit | Skip LC → Engineering |
| 3 | zoning_assessed | Planning Staff | ZoningController::finalize | Complete zoning + fees |
| 4 | engineering_assessed | Engineering Officer | AssessmentController::finalize | Finalize fee assessment |
| 5 | billed | Finance | BillingController::generate | Generate billing |
| 6 | paid | Treasury Staff | CollectionController::store | Record payment (OR) |
| 7 | permit_generated | Engineering Officer | PermitController::generate | Generate permit PDF |
| 8 | released | Engineering Officer | Manual | Release to applicant |

### Skip Locational Clearance
When `applies_to = "SKIP_LC"`: `draft → submitted` (bypasses planning).
Without skip LC: `draft → for_zoning_assessment → zoning_assessed`.

### Zoning Fee Auto-Compute
1. Look up `land_use_and_zoning_fees` by occupancy sub-group + total estimated cost
2. Compute: `amount + ((totalCost - excess_of) × percentage)` per sub-group
3. Add `certification_zoning_fees` flat fee (P500)
4. Create assessment items (assessment_type = 'zoning')

---

## Occupancy Permit (OP) Workflow

```
draft → submitted → engineering_assessed → billed → paid → permit_generated → released
```

OP skips `zoning_assessed` entirely. Parallel `*Op()` methods in Assessment/Billing/Collection/Permit controllers.

---

## State Machine

### ApplicationStatus Enum (`app/Enums/ApplicationStatus.php`)

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
| released / cancelled | (terminal) |

---

## Fee Computation Flow

### Computation Methods

| Method | Description | Example |
|--------|-------------|---------|
| `fixed` | Flat fee × quantity | ₱5,000/elevator × 2 = ₱10,000 |
| `per_unit` | Rate × quantity | ₱40/ton × 80 = ₱3,200 |
| `range_based` | Lookup fee by range band | Floor area 101–200 → ₱500 flat |
| `cumulative_range` | Tiered: first N at rate A, excess at rate B | Elevators: 5@₱500 + excess@₱50 |
| `percentage` | % of base amount | 10% of electrical fee |
| `formula` | Custom formula (stored as text) | |

### Assessment Item Creation Flow

#### Construction Tab
```
Select Part of Building + Division (filtered by occupancy groups) + Area
→ FeeType lookup by CONST_{division.code}
→ FeeSchedule by area range
→ amount = area × fee_per_unit
```

#### Electrical Tab
```
Select fee type (TCL / Transformer / UPS / Pole / Guying / Meter / Wiring)
→ kVA types: base = fixed_fee + (kva × fee_per_unit)   [range lookup]
→ fixed types: base = fixed_fee
→ inspection_fee = base × electrical_inspection_percentage (setting, default 10%)
→ amount = base (inspection_fee stored separately)
→ grand total = sum(amount) + sum(inspection_fee)
```

#### Mechanical Tab
```
Select mechanical equipment type (MECH_*) + unit count
→ Base fee: FeeSchedule lookup on MECH fee schedules
  · per_unit:   amount = quantity × fee_per_unit
  · fixed:      amount = quantity × fixed_fee
  · range_based: lookup range → flat fixed_fee or fee_per_unit × qty (with optional excess)
→ NBC Inspection fee: resolveInspectionFee() maps MECH_REFRIG → INSP_REFRIG
  · flat:    insp = range-band fixed_fee (+ excess if unit > threshold)
  · per_unit: insp = unit × fee_per_unit (+ excess if unit > threshold)
  · tiered:  insp = min(unit,threshold)×fee + max(0,unit-threshold)×excess_fee
→ amount = base fee only; inspection_fee stored separately
→ grand total = sum(amount) + sum(inspection_fee)
```

#### Other Tabs (Plumbing, Electronics, Accessories, etc.)
```
Select fee type + enter quantity
→ amount = quantity × unit_fee
```

### Grand Total Formula (all categories)
```
Assessment total = sum(assessment_items.amount)
                 + sum(assessment_items.inspection_fee)
                 + filing_fee
                 + processing_fee
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
/register → /login → /online/apply (status = submitted)
→ upload requirements → track status → download permit (status = released)
```

---

## Document Generation

### PDF Templates (`resources/views/pdf/`)

| Template | Trigger |
|----------|---------|
| application-form | ApplicationController::printForm |
| building-permit | PermitController::print (BP) |
| occupancy-permit | PermitController::print (OP) |
| assessment-summary | AssessmentController::print |
| billing-statement | BillingController::print |
| official-receipt | CollectionController::receipt |
| zoning-certification | PermitController::zoningCertification |
| locational-clearance | PermitController::locationalClearance |
| evaluation-report | PermitController::evaluationReport |

### Notifications

| Notification | Trigger |
|-------------|---------|
| ApplicationSubmittedNotification | Application submitted |
| AssessmentCompleteNotification | Assessment finalized → client |
| PaymentPostedNotification | Payment recorded → client |
| ApplicationApprovedNotification | Permit generated → client |
