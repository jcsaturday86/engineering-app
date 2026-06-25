# Feature Completeness Status

> Legend: DONE | PARTIAL | PENDING | N/A

---

## Core Infrastructure

| Feature | Status | Notes |
|---------|--------|-------|
| Laravel 12 project setup | DONE | PHP 8.2, MariaDB 12.3 |
| Self-healing boot (auto DB/migrations/seeds) | DONE | SelfHealingServiceProvider |
| Authentication (staff portal) | DONE | /staff/login with role-based redirect |
| Authentication (client portal) | DONE | /login, /register for online applicants |
| Role-based access control (RBAC) | DONE | 9 roles, 30+ permissions via Spatie |
| Activity logging | DONE | Application, Assessment, Collection, Permit |
| Soft deletes | DONE | All transaction tables |
| Settings management | DONE | Key-value settings table with admin UI |
| Browser autofill disabled | DONE | autocomplete="off" on all 41 forms |
| Form validation UX | DONE | Error summary banner, section highlighting, scroll-to-error |
| Backend validation aligned | DONE | Required fields match HTML required attributes |
| Test data seeder | DONE | ApplicationSeeder: 5 BP + 5 OP with all fields populated |

---

## Building Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD (walk-in) | DONE | Full create/edit/view/list |
| Application CRUD (online) | DONE | Client portal with submit on create |
| Application numbering (BP-YYYY-MM-NNNNN) | DONE | Auto-generated, unique |
| Occupancy group selection (multi-select) | DONE | Groups A-J with sub-groups |
| Application type (New/Renewal/Amendatory) | DONE | Linked to permit_type via application_types table |
| Complexity (Simple/Complex) | DONE | |
| Skip Locational Clearance | DONE | applies_to = "SKIP_LC" |
| Applicant information | DONE | Name, TIN, contact, address, ID |
| Enterprise/ownership | DONE | |
| Building location | DONE | Lot/block/TCT/tax dec, barangay |
| Scope of work | DONE | 12 scope options with details |
| Building specifications | DONE | Storeys, units, floor area, lot area |
| Cost estimates (9 cost fields) | DONE | Auto-totals via Alpine.js |
| Engineer/Architect details | DONE | PRC, PTR, TIN, address |
| PEE/SEW details | DONE | Professional electrical engineer fields |
| Owner details | DONE | Name, address, govt ID |
| Electrical permit data | DONE | Connected load, transformer, generator capacity |
| Application form print (HTML) | DONE | Browser print with legal paper layout |
| Status workflow | DONE | 8-state machine with validation |
| Application submission + notification | DONE | Notifies engineering users |

---

## Occupancy Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD (walk-in) | DONE | Separate form/controller, own `occupancy_applications` table (OccupancyApplicationController) |
| Application type (Full/Partial) | DONE | Separate types linked to OP permit type |
| BP reference (number, date issued) | DONE | |
| FSEC reference (number, date issued) | DONE | |
| Project details (name, completion, location) | DONE | OP-specific section |
| Character of Occupancy | DONE | Shared occupancy group selection |
| Application form print | DONE | OP-specific template |
| Status workflow (skips zoning) | DONE | submitted → engineering_assessed directly |
| Separate database table | DONE | Own `occupancy_applications` table, polymorphic downstream (assessments, billings, collections, permits, documents) |
| Separate model/service/DTO | DONE | OccupancyApplication model, OccupancyApplicationService, OccupancyApplicationDTO |
| Separate controller/routes | DONE | OccupancyApplicationController, /occupancy-applications/* routes |
| Separate views | DONE | occupancy-applications/index, form, show |

---

## Zoning / Planning Module

| Feature | Status | Notes |
|---------|--------|-------|
| Zoning assessment form | DONE | Card-based design matching BP/OP. Project classification (BOPMS dropdowns), zoning details, boundaries, compliance |
| Zoning fee auto-compute | DONE | Queries land_use_and_zoning_fees by occupancy sub-group + cost range. Matches BOPMS zoningAutoCompute() logic |
| Fee items table with delete | DONE | Assessment items table with per-row delete, auto-compute button |
| Assessment finalization | DONE | Sums items, finalizes assessment, transitions for_zoning_assessment → zoning_assessed |
| For zoning assessment status | DONE | New status for BP apps routed to planning office |
| Skip locational clearance | DONE | Bypass planning office, goes to submitted status directly |
| Dedicated zoning fee tables | DONE | land_use_and_zoning_fees (162 rows), certification_zoning_fees (P500) |
| Zoning fee settings page | DONE | /settings/zoning-fees — manage fees by occupancy group/sub-group with accordion UI |
| Zoning certification PDF | DONE | Template exists |
| Locational clearance PDF | DONE | Template exists |

---

## Fee Computation

| Feature | Status | Notes |
|---------|--------|-------|
| Fee schedule management (admin UI) | DONE | Categories, types, schedules CRUD |
| Zoning fee management (admin UI) | DONE | Dedicated /settings/zoning-fees page |
| 3-table consolidated design | DONE | Replaces BOPMS's 100+ tables |
| Fixed fee computation | DONE | |
| Per-unit fee computation | DONE | |
| Range-based fee computation | DONE | |
| Cumulative range computation | DONE | |
| Percentage computation | DONE | |
| Formula computation | PARTIAL | Formula stored as text, evaluation may be incomplete |
| Excess fee calculation | DONE | Threshold, per-unit excess, every-N grouping |
| Min/max constraints | DONE | |
| Construction fee data | DONE | Seeded via FeeScheduleSeeder |
| Electrical fee data | DONE | |
| Mechanical fee data | DONE | |
| Plumbing fee data | DONE | |
| Electronics fee data | DONE | |
| Occupancy fee data | DONE | |
| Zoning fee data | DONE | |
| Accessory fee data | PARTIAL | Some accessory categories may need more seed data |

---

## Payment / Treasury Module

| Feature | Status | Notes |
|---------|--------|-------|
| Payment collection (cash) | DONE | |
| Payment collection (check) | DONE | Bank, check number, check date |
| Payment collection (online) | DONE | Reference number |
| Official receipt generation | DONE | PDF with unique OR number |
| Void transaction | DONE | Admin password verification, void tracking |
| Collection history | DONE | |

---

## Billing

| Feature | Status | Notes |
|---------|--------|-------|
| Billing generation from assessments | DONE | Auto-number BL-YYYY-MM-NNNNN |
| Billing statement PDF | DONE | |
| Billing status tracking | DONE | unpaid, partial, paid, void |

---

## Permit Generation

| Feature | Status | Notes |
|---------|--------|-------|
| Building permit PDF | DONE | Template with signatories |
| Occupancy permit PDF | DONE | Template with signatories |
| Permit numbering | DONE | CODE-YYYY-MM-NNNNN |
| Permit status tracking | DONE | generated, signed, released |
| Evaluation report PDF | DONE | Template exists |

---

## Reports

| Feature | Status | Notes |
|---------|--------|-------|
| Permit reports (filter by date/type) | DONE | |
| Revenue reports | DONE | |
| Collection reports | DONE | |
| Excel export | DONE | Maatwebsite Excel |
| PDF export | DONE | DomPDF |

---

## Settings / Admin

| Feature | Status | Notes |
|---------|--------|-------|
| System settings | DONE | |
| User management (CRUD) | DONE | |
| Role/permission matrix | DONE | |
| Fee schedule management | DONE | |
| Signatory management | DONE | |

---

## Online Client Portal

| Feature | Status | Notes |
|---------|--------|-------|
| Client registration | DONE | |
| Client login (separate portal) | DONE | |
| Online application submission | DONE | Auto-submits (skips draft) |
| Application status tracking | DONE | Timeline view |
| Document requirement upload | PARTIAL | Model/route exists, UI may need work |
| Permit download | DONE | When status = released |

---

## Dashboard

| Feature | Status | Notes |
|---------|--------|-------|
| KPI cards (applications, pending, revenue) | DONE | |
| Monthly revenue chart | DONE | Chart.js |
| Recent applications list | DONE | |
| Daily transaction count | DONE | |

---

## Not Migrating from BOPMS

| Feature | Reason |
|---------|--------|
| BFP module (FSEC/FSIC) | BFP is not included in this system |
| DB-level encryption | Not required for this deployment |
| Annual inspection fees | Future scope, not in current requirements |
| BFP partial payment (downpayment) | BFP excluded |
