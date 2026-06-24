# BOPMS Workflow Reference

> Legacy system workflow documentation for reference during engineering-app development.

---

## Building Permit (BP) Workflow

### State Transitions

```
[1] New Application
 ↓ Zoning assessed
[2] Assessed (Zoning/Locational)
 ↓ Engineering assessed
[3] Assessed (Engineering/EAS)
 ↓ BFP assessed
[4] Assessed (BFP/Fire Safety)
 ↓ Payment received
[5] Paid
 ↓ Permit generated (separate action)

[0] Cancelled (from any state)
```

### Step-by-Step Process

**1. Application Entry** (`applicationSave`)
- User inputs: project title, applicant details, location, building specs, cost estimates
- Auto-generates number: `BP-YY-MM-NNNNN` (e.g., BP-23-5-00001)
- Creates initial `bp_assessment_fees` record with zeros
- State = 1

**2. Group/Subgroup Selection**
- User selects applicable building groups (A through J)
- Subgroups allow detailed classification
- Some subgroups require "other" text explanation
- Stored in `application_building_permits_groups`

**3. Construction Fee Assessment** (`constructionFeeSave`)
- Division determined by group/subgroup selection
- Total floor area matched to ranges in `construction_fees`
- Fee per sq.meter applied from matching range

**4. Electrical Assessment** (`electricalFeeSave`) — if `include_electrical=1`
- Total Connected Load fee (kW ranges)
- Transformer/UPS/Generator capacity fees
- Pole attachment location fees
- Miscellaneous fees (meter, wiring permit)

**5. Mechanical Assessment** (`mechanicalFeeSave`)
- Refrigeration/AC/Ventilation fees
- Escalator/Moving walk fees
- Elevator fees
- Boiler fees (by capacity)
- Diesel/Gasoline engine fees

**6. Plumbing Assessment** (`plumbingFeeSave`)
- Installation fees
- Special fixture fees
- Every fixture fees (per fixture count)
- Water meter fees (by installation count)
- Septic tank fees (by capacity)

**7. Electronics Assessment** (`electronicsFeeSave`)
- General electronics fees
- Inspection fees

**8. Accessories of Building** (`accessoriesBldgFeeSave`)
- Open parts (50% of principal), height surcharge (>8m)
- Swimming pools, towers, silos, smokestacks
- Tanks (above ground, underground), booths/kiosks
- Firewalls, water/wastewater treatment

**9. Accessory Fees** (`assessmentAccessoryFeeSave`)
- Line & grade, ground prep/excavation
- Fencing, pavement, streets/sidewalk use
- Scaffolding (monthly), signage
- Repairs, demolition/moving

**10. Zoning/Locational Clearance** (`assessmentZoning`)
- Based on land classification and zone type
- Residential R-1/R-2 rates differ from Commercial

**11. Surcharge Assessment** (`assessmentSurchargeFee`)
- Applied based on construction cost percentage

**12. BFP Assessment — FSEC** (`assessmentFsec`)
- Fire Safety Evaluation Clearance fees
- State transitions: 3 → 4

**13. Payment/Collection** (`paymentSave`)
- Sums all fee categories into grand total
- Accepts: cash, check (bank/check#/date), online
- Generates Official Receipt (unique OR number)
- Creates `collections` + `collection_details` records
- State transitions: 4 → 5

---

## Occupancy Permit (OP) Workflow

### State Transitions

```
[1] New Application
 ↓ Engineering assessed (skips zoning)
[3] Assessed
 ↓ Payment received
[5] Paid
 ↓ Permit generated

[0] Cancelled (from any state)
```

### Process

**1. Application Entry** (`occupancyApplicationSave`)
- References existing BP: `bp_no`, `bp_issued_date`
- FSEC reference: `fsec_no`, `fsec_issued_date`
- Applicant info, property location, building specs
- State = 1

**2. Occupancy Assessment** (`occupancyAssessment`)
- Base occupancy fee by division and floor area
- Division-specific fee tables: A, B, C/D, J-I, J-II variants
- Change in use fees (if applicable)

**3. FSIC Assessment** (`assessmentFsic`)
- Fire Safety Inspection Certificate fees
- State transitions: 1 → 3

**4. Payment** (`payment_occupancy`)
- Same collection flow as BP
- State transitions: 3 → 5

---

## Fee Computation Flow

### Range-Based Lookup (Most Common)

```
fee_table:
  range_from | range_to | fee        | in_excess | in_excess_fee
  1          | 50       | 3.40/sqm   | NULL      | NULL
  51         | 100      | 170.00     | 50        | 2.80
  101        | 150      | 310.00     | 100       | 2.40
```

**Logic:**
1. Find row where `range_from <= value <= range_to`
2. If exact match: use `fee` directly
3. If `in_excess` is set: `fee + ((value - in_excess) * in_excess_fee)`

### Cumulative vs Non-Cumulative

| Mode | Description | Divisions |
|------|-------------|-----------|
| Non-Cumulative | Each division calculated separately | A1, A2 |
| Cumulative | Fees build on each other across divisions | Most others |

### Division/Group-Based Fee Selection

```
Application → Groups (A-J) → SubGroups → Divisions → Fee Table
                                           ↓
                                    assessment_mode determines
                                    cumulative vs non-cumulative
```

### Occupancy Classification Multipliers

| Group | Occupancy ID | Type |
|-------|-------------|------|
| A (Residential) | 1 | Residential rates |
| B/E/F/G | 2 | Commercial/Industrial rates |
| C/D/H/I | 3 | Social/Institutional rates |

---

## Fee Computation Example

**Scenario:** 2-storey residential, 150 sqm, Manila

| Category | Calculation | Amount |
|----------|------------|--------|
| Construction | Division A1, 101-150 range, 6.00/sqm × 150 | 900.00 |
| Electrical | 25kW (21-50 range) + meter + wiring | 250.00 |
| Mechanical | 5-ton AC (1-10 range) | 200.00 |
| Plumbing | Installation + 10 fixtures + 1 meter | 60.00 |
| Accessories | Line & grade (100-150 sqm range) | 24.00 |
| Zoning | Residential R-1 + certification | 1,135.00 |
| Filing/Processing | Standard fees | 350.00 |
| BFP/FSEC | Filing + inspection | 1,700.00 |
| Surcharge | 0.1% of 500K construction cost | 500.00 |
| **Grand Total** | | **5,119.00** |

---

## Role-Based Access

| Access Level | Role | Permissions |
|-------------|------|-------------|
| 1 | Administrator | Full access to all modules |
| 2 | EAS User (Engineering) | BP/OP applications, assessments |
| 3 | BFP User (Fire Safety) | FSEC/FSIC assessment |
| 4 | CTO User (Treasury) | Collections, payments, receipts |
| 5 | PDO User (Planning) | Zoning/locational clearance |

---

## Payment Flow

```
Collection Screen
 ↓ Select application (state 3 or 4)
Payment Form
 ├── Displays all fee categories with amounts
 ├── Shows Grand Total
 └── Payment input:
      ├── Cash: amount received → change calculated
      ├── Check: bank name, check #, check date
      └── Online: reference number
 ↓ Submit
Creates:
 ├── collections record (OR number, amounts, payment mode)
 └── collection_details (fee line items)
Updates:
 └── application state → 5 (Paid)
```

---

## Permit Generation

After payment (state=5), permits are generated as PDFs:

| Permit Type | Route | Template |
|-------------|-------|----------|
| Building Permit | `/generateBuildingPermit` | BP PDF template |
| Occupancy Permit | `/generateOccupancyPermit` | OP PDF template |
| FSEC | `/generateFsec` | FSEC clearance |
| FSIC | `/generateFsic` | FSIC certificate |
| Locational Clearance | `/generateLocationalClearance` | LC PDF |
| Zoning Certification | `/generatePdoCertification` | PDO cert PDF |

---

## Key Differences from Engineering-App

| Aspect | BOPMS | Engineering-App |
|--------|-------|-----------------|
| BP/OP Tables | Separate tables | Single `applications` table (to be separated) |
| Status | Numeric (1-5) | String enum (draft, submitted, etc.) |
| Controllers | 1 monolithic (9000 lines) | 13 focused controllers |
| Services | None | 7 service classes |
| Fee Tables | 100+ individual tables | 3 consolidated tables |
| BFP Module | Included | Not included |
| Soft Deletes | No | Yes |
| Activity Log | No | Yes (Spatie) |
| Encryption | DB-level (LaravelDbEncrypter) | None |
| Permissions | Access level integers | Spatie Permission (RBAC) |
