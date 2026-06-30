# Feature Completeness Status

> Legend: DONE | PARTIAL | PENDING | N/A

---

## Core Infrastructure

| Feature | Status | Notes |
|---------|--------|-------|
| Laravel 12 project setup | DONE | PHP 8.2, MariaDB 12.3 |
| Self-healing boot | DONE | SelfHealingServiceProvider |
| Staff authentication | DONE | /staff/login with role-based redirect |
| Client authentication | DONE | /login, /register |
| RBAC | DONE | 9 roles, 30+ permissions via Spatie |
| Activity logging | DONE | Application, Assessment, Collection, Permit |
| Soft deletes | DONE | All transaction tables |
| Settings management | DONE | Key-value with admin UI |
| Browser autofill disabled | DONE | autocomplete="off" on all forms |
| Form validation UX | DONE | Error banner, section highlighting, scroll-to-error |
| Test data seeder | DONE | ApplicationSeeder: 5 BP + 5 OP |

---

## Building Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD (walk-in) | DONE | |
| Application CRUD (online) | DONE | Client portal |
| Application numbering | DONE | BP-YYYY-MM-NNNNN |
| Occupancy group selection | DONE | Groups A–J with sub-groups |
| All BP form fields | DONE | Applicant, enterprise, project, building, costs, engineers |
| Application form print | DONE | Browser print, legal layout |
| Status workflow | DONE | 8-state machine |
| Submission notification | DONE | Notifies engineering users |

---

## Occupancy Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD | DONE | Separate controller/model/table |
| OP-specific fields | DONE | BP reference, FSEC, completion date |
| Character of Occupancy | DONE | Shared occupancy group selection |
| Status workflow (skips zoning) | DONE | submitted → engineering_assessed |
| Polymorphic downstream | DONE | assessments, billings, collections, permits, documents |

---

## Zoning / Planning Module

| Feature | Status | Notes |
|---------|--------|-------|
| Zoning assessment form | DONE | BOPMS-style card layout |
| Fee auto-compute | DONE | land_use_and_zoning_fees + certification_zoning_fees |
| Fee items table + delete | DONE | Per-row delete, auto-compute button |
| Assessment finalization | DONE | for_zoning_assessment → zoning_assessed |
| Skip locational clearance | DONE | Bypass planning, → submitted |
| Dedicated zoning fee tables | DONE | 162 LC rows, P500 cert fee |
| Zoning fee settings page | DONE | /settings/zoning-fees accordion UI |
| Variance/Non-Conforming fees | DONE | land_use_and_zoning_other_fees table |
| Fee type selector (4 types) | DONE | LC, LC Manual, Certification, Others |
| Checkbox bulk delete | DONE | fetch API bulk delete |
| Finalize password confirm | DONE | Hash::check() modal |
| Zoning certification PDF | DONE | Template exists |
| Locational clearance PDF | DONE | Template exists |

---

## Fee Computation

| Feature | Status | Notes |
|---------|--------|-------|
| Fee schedule management | DONE | Categories/types/schedules CRUD |
| Zoning fee management | DONE | /settings/zoning-fees |
| All 6 computation methods | DONE | fixed, per_unit, range_based, cumulative_range, percentage (formula = PARTIAL) |
| Excess/min/max | DONE | |
| Construction fee data + tab | DONE | BOPMS-style: Part+Division+Area → auto lookup |
| Electrical fee data + tab | DONE | BOPMS-style: 7 types, range kVA, auto inspection % |
| Electrical inspection fee | DONE | `assessment.electrical_inspection_percentage` setting (default 10%) |
| Mechanical fee data + tab | DONE | BOPMS-style: equipment type+unit → auto base + NBC inspection fee |
| Mechanical NBC inspection fees | DONE | MECH_INSP category: 29 INSP_* types / 55 schedules from BOPMS ann_inspection_f* tables |
| Mechanical inspection formulas | DONE | flat (range-band), per_unit (rate×count), tiered (cumulative for elevators) |
| BP assessment tab navigation | DONE | 8 tabs + Summary, badges, hidden MECH_INSP tab |
| Plumbing fee data | DONE | Seeded |
| Plumbing tab (BOPMS-style) | PENDING | Next implementation |
| Electronics fee data | DONE | Seeded |
| Occupancy fee data | DONE | Seeded |
| Accessory fee data | PARTIAL | May need more seed data |

---

## Payment / Treasury Module

| Feature | Status | Notes |
|---------|--------|-------|
| Payment collection (cash/check/online) | DONE | |
| Official receipt generation | DONE | PDF, unique OR number |
| Void transaction | DONE | Password verify, void tracking |
| Collection history | DONE | |

---

## Billing

| Feature | Status | Notes |
|---------|--------|-------|
| Billing generation | DONE | BL-YYYY-MM-NNNNN |
| Billing statement PDF | DONE | |
| Billing status tracking | DONE | unpaid, partial, paid, void |

---

## Permit Generation

| Feature | Status | Notes |
|---------|--------|-------|
| Building permit PDF | DONE | With signatories |
| Occupancy permit PDF | DONE | With signatories |
| Permit numbering | DONE | CODE-YYYY-MM-NNNNN |
| Evaluation report PDF | DONE | |

---

## Reports

| Feature | Status | Notes |
|---------|--------|-------|
| Permit / Revenue / Collection reports | DONE | |
| Excel + PDF export | DONE | |

---

## Settings / Admin

| Feature | Status | Notes |
|---------|--------|-------|
| System settings | DONE | |
| User management | DONE | |
| Role/permission matrix | DONE | |
| Fee schedule management | DONE | |
| Signatory management | DONE | |

---

## Online Client Portal

| Feature | Status | Notes |
|---------|--------|-------|
| Registration + login | DONE | Separate portal |
| Online application submission | DONE | Auto-submits |
| Status tracking | DONE | Timeline view |
| Document requirement upload | PARTIAL | Model/route exists, UI needs work |
| Permit download | DONE | When status = released |

---

## Dashboard

| Feature | Status | Notes |
|---------|--------|-------|
| KPI cards | DONE | Applications, pending, revenue |
| Monthly revenue chart | DONE | Chart.js |
| Recent applications + daily count | DONE | |

---

## Not Migrating from BOPMS

| Feature | Reason |
|---------|--------|
| BFP module (FSEC/FSIC) | BFP not included in this system |
| DB-level encryption | Not required |
| Annual inspection (non-mechanical) | Future scope |
| BFP partial payment | BFP excluded |
