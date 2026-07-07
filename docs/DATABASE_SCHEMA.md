# Database Schema

> Database: `epms_db` (MariaDB 12.3) | All tables use timestamps unless noted.

---

## Auth & System Tables

### `users`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint PK | | |
| name | string | | Display name |
| email | string unique | | Login email |
| password | string | | Hashed |
| first_name / middle_name / last_name / suffix | string | Yes | |
| phone | string(20) | Yes | |
| department / position | string | Yes | |
| avatar | string | Yes | File path |
| is_active | boolean | | Default: true |
| must_change_password | boolean | | Default: false |
| last_login_at / last_login_ip | timestamp/string | Yes | |
| deleted_at | timestamp | Yes | Soft delete |

### `settings`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| group | string | Default: 'general' |
| key | string unique | Setting key |
| value | text | Setting value |
| type | string | string, decimal, boolean, json |
| description | string | Human description |

**Key settings:** `assessment.electrical_inspection_percentage` (default 10), `assessment.default_filing_fee`, `assessment.default_processing_fee`, `general.logo` (type `file` — city/LGU seal, uploaded via Settings → General, GD-resized to max 400px before storage, printed on both permit PDFs), `general.dpwh_logo` (type `file` — DPWH logo for the Occupancy Permit PDF; falls back to the static `public/images/dpwh-logo.png` asset when empty), `general.zip_code` (Building Permit PDF), `general.domain` (public domain used to build the QR verification link on printed permits; blank falls back to `config('app.url')`).

File-type settings are each stored at a fixed path derived from their key (`SettingsController::update()`, e.g. `logos/city-seal.png` for `general.logo`, `logos/dpwh-logo.png` for `general.dpwh_logo`) — previously all file uploads were hardcoded to the same path regardless of key, which would have made two file settings silently clobber each other.

### Spatie / Laravel System Tables
- `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`
- `activity_log` — id, log_name, description, subject_type/id, causer_type/id, properties (json), event
- `password_reset_tokens`, `sessions`, `cache`, `jobs`, `failed_jobs`, `notifications`

---

## Geographic Tables

### `provinces` / `cities` / `barangays`

Standard hierarchical geo tables with `psgc_code`, `name`, `is_active`. ~42K barangay records seeded from Philippine PSA data.

---

## Reference Tables

### `permit_types`
`code` (BP, OP, FP, EP, DP, SP, ELP, MP, PP, ECP), `name`, `is_active`, `sort_order`

### `application_types`
`permit_type_id`, `name` (New/Renewal/Amendatory for BP; Full/Partial for OP), `is_active`, `sort_order`

### `scope_of_works`
`name`, `category` (construction/other), `is_active`, `sort_order`

### `occupancy_groups` / `occupancy_sub_groups` / `occupancy_divisions`
- Groups: A–J (10 groups, 40+ sub-groups)
- Divisions: code (A1, B1, etc.), `assessment_mode` (cumulative/non_cumulative)

### Other Reference Tables
- `building_parts` — id, name, is_active
- `signatories` — id, role, name, title, designation, department, license_no, is_active. The `building_official` role is used as "Approved By" on the assessment summary PDF and permits.
- `land_classifications` — id, name, code, is_active
- `form_of_ownerships` — id, name, is_active

---

## Fee Schedule Tables

### `fee_categories`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| permit_type_id | FK → permit_types | |
| code | string(30) unique | CONST, ELEC, MECH, MECH_INSP, PLUMB, etc. |
| name | string | Category name |
| is_active | boolean | Default: true |
| sort_order | integer | |

> `MECH_INSP` is a hidden category (excluded from assessment tabs). It holds the NBC mechanical permit inspection fee rates (29 INSP_* fee types, 55 schedule rows) mirroring BOPMS `ann_inspection_f*` tables.

### `fee_types`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| fee_category_id | FK → fee_categories | |
| code | string(50) | e.g., CONST_A1, ELEC_TCL, MECH_REFRIG, INSP_REFRIG |
| name | string | |
| computation_method | enum | fixed, per_unit, range_based, cumulative_range, percentage, formula |
| has_excess / has_minimum / has_maximum | boolean | Default: false |
| is_active | boolean | Default: true |
| sort_order | integer | |

### `fee_schedules`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| fee_type_id | FK → fee_types | |
| occupancy_division_id | FK → occupancy_divisions | Yes |
| occupancy_sub_group_id | FK → occupancy_sub_groups | Yes |
| range_from / range_to | decimal(15,2) | Default: 0 |
| fixed_fee | decimal(15,2) | Flat fee or range-band amount |
| fee_per_unit | decimal(15,4) | Per-unit rate |
| percentage | decimal(8,4) | For percentage method |
| excess_threshold | decimal(15,2) | Unit count where excess kicks in |
| excess_fee | decimal(15,2) | Rate per unit above threshold |
| excess_every | decimal(15,2) | Excess groups: fee = `ceil(excess / excess_every) × excess_fee` ("per ₱1M or fraction thereof", e.g. OCC_DIV_A) |
| minimum_fee / maximum_fee | decimal(15,2) | |
| formula | text | Yes | For formula method |
| insp_fee | decimal(15,4) | Reserved — not used by current implementation |
| insp_method | enum(flat,per_unit,tiered) | Reserved — not used by current implementation |
| insp_excess_threshold | decimal(15,2) | Reserved — not used by current implementation |
| insp_excess_fee | decimal(15,4) | Reserved — not used by current implementation |
| insp_excess_every | decimal(10,2) | Reserved — not used by current implementation |
| is_active | boolean | Default: true |

**Index:** [fee_type_id, range_from, range_to]

> The `insp_*` columns are schema artifacts added during development. The active implementation uses separate INSP_* fee types in the MECH_INSP category instead.

---

## Zoning Fee Tables

### `land_use_and_zoning_fees`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| occupancy_sub_group_id | FK → occupancy_sub_groups | |
| range_from / range_to | decimal(15,2) | Cost range |
| amount | decimal(15,2) | Base fee for this range |
| excess_of | decimal(15,2) | Threshold for excess |
| percentage | decimal(10,6) | Per-peso excess rate |
| is_active | boolean | Default: true |

**162 rows** across 52 sub-groups, 6 fee patterns. **Index:** [occupancy_sub_group_id, range_from, range_to]

### `certification_zoning_fees`
`occupancy_sub_group_id` (nullable = applies to all), `amount` (P500 flat), `is_active`

### `land_use_and_zoning_other_fees`
`name`, `code` (VARIANCE, NON_CONFORMING), `amount`, `is_active`

---

## Application Tables

### `applications` (Building Permit only)

| Column Group | Columns |
|-------------|---------|
| **Identity** | id, permit_type_id (FK), application_type_id (FK), app_year, app_month, app_counter, application_number (unique), area_number |
| **Status** | status (default: 'draft'), source (walk_in/online) |
| **Header** | complexity, applies_to |
| **Applicant** | applicant_first/middle/last_name, suffix, tin, contact_no, email, govt_id, id_date_issued, id_place_issued, date_signed |
| **Enterprise** | enterprise_name, form_of_ownership_id (FK) |
| **Applicant Address** | province_id, city_id, barangay_id, street, zip_code (all FK where applicable) |
| **Project** | project_title, scope_of_work_id (FK), scope_of_work_details |
| **Building Location** | lot_no, block_no, tct_no, tax_dec_no, land_classification_id (FK), building_street, building_barangay_id (FK) |
| **Building Specs** | no_of_storeys, no_of_units, occupancy_classified, total_floor_area, lot_area |
| **Costs** | building_cost, electrical_cost, mechanical_cost, electronics_cost, plumbing_cost, other_equipment_cost, equipment_cost_1–4, total_estimated_cost |
| **Timeline** | proposed_construction_date, expected_completion_date |
| **Fire Safety (reference only)** | fsec_no, fsec_issued_date — shown on the printed Building Permit; no BFP workflow attached |
| **Engineer** | engineer_name, prc_no, prc_validity, ptr_no, ptr_date_issued, ptr_issued_at, tin, address, date_signed |
| **PEE** | pee_name, prc_no, prc_validity, date_signed, ptr_no, ptr_date_issued, ptr_issued_at, address, tin |
| **SEW** | sew_profession, name, prc_no, prc_validity, date_signed, ptr_no, ptr_date_issued, ptr_issued_at, address, tin |
| **Owner** | owner_name, address, govt_id, id_date_issued, id_place_issued, date_signed |
| **Electrical** | include_electrical, total_connected_load, total_transformer_capacity, total_generator_capacity |
| **Processing** | entered_by, assessed_by, approved_by, client_user_id (all FK → users), submitted/assessed/approved/paid/released/cancelled_at, cancellation_reason, issued_date |
| **System** | remarks, deleted_at |

**Indexes:** [permit_type_id, status], [app_year, app_month], [status]

### `occupancy_applications` (Occupancy Permit only)

Same structure as `applications` minus BP-specific columns (no cost fields, no engineer/PEE/SEW, no electrical, no scope_of_work, no complexity). Adds OP-specific: `bp_number`, `bp_issued_date`, `fsec_no`, `fsec_issued_date`, `fsic_no` (reference only — no BFP workflow), `applies_for` (full/partial select on the OP form — currently informational only; the printed Certificate of Occupancy's FULL/PARTIAL checkbox is actually driven by `applicationType->name`, since Full/Partial is also modeled as the OP `application_types` options), `completion_date`, `project_title`.

### `application_occupancy_groups`
Polymorphic (`applicationable_type` / `applicationable_id`), `occupancy_group_id`, `occupancy_sub_group_id`, `others_text`.

### `application_requirements`
Polymorphic, `requirement_name`, `file_path`, `original_filename`, `status` (pending/approved/rejected), `reviewer_remarks`, `reviewed_by`, `reviewed_at`.

---

## Assessment Tables

### `assessments`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| applicationable_type / applicationable_id | varchar(10) / bigint | Polymorphic: 'bp' or 'op' |
| assessment_type | string(30) | building, occupancy, zoning |
| filing_fee / processing_fee / total_amount | decimal(15,2) | Default: 0 |
| status | enum | draft, finalized |
| assessed_by | FK → users | Yes |
| finalized_at / deleted_at | timestamp | Yes |

> Once `status = finalized`, assessment items can no longer be added or removed — enforced server-side in `AssessmentController` (redirect w/ error) and `ZoningController` (403).

### `assessment_items`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| assessment_id | FK → assessments | |
| fee_category_id / fee_type_id | FK | Yes |
| fee_code | string(50) | |
| description | string | |
| quantity | decimal(15,2) | |
| unit_fee | decimal(15,2) | Base rate shown in table |
| excess_fee | decimal(15,2) | Excess portion of base fee |
| inspection_fee | decimal(15,2) | NBC inspection fee (ELEC: % of base; MECH: from INSP_* schedules) |
| amount | decimal(15,2) | Base permit fee only (does NOT include inspection_fee) |
| computation_details | json | Yes | Inputs/outputs for audit |
| is_active | boolean | Default: true |
| deleted_at | timestamp | Yes |

> **Grand total formula:** `sum(amount) + sum(inspection_fee) + filing_fee + processing_fee` — this is consistent across CONST, ELEC, and MECH categories.

**Index:** [assessment_id, fee_code]

### `zoning_assessments`
1:1 with `applications` (BP only). Stores zoning compliance fields, boundaries, classification, decision info.

---

## Billing & Collection Tables

### `billings`
Polymorphic, `billing_number` (BL-YYYY-MM-NNNNN), `total_amount`, `status` (unpaid/partial/paid/void), `generated_by`.

> Rows are created automatically by `BillingService::generateFor()` when an assessment is finalized — there is no manual billing-generation UI.

### `billing_items`
`billing_id`, `category`, `description`, `amount`, `sort_order`.

### `collections`
Polymorphic, `billing_id`, `or_number`, `or_date`, `paid_by`, `amount_due/received/change`, `payment_mode` (cash/check/online), bank/check/online fields, `collected_by`, `status` (active/void).

### `collection_details`
`collection_id`, `fee_category`, `description`, `amount`.

### `void_transactions`
`collection_id`, `or_number`, `reason`, `voided_by`, `voided_at`.

---

## Permit & Document Tables

### `permits`
Polymorphic, `permit_type_id`, `permit_year/month/counter`, `permit_number` (CODE-YYYY-MM-NNNNN), `verification_token` (string, unique, UUID — set on generation, used to build the public QR-code verification link `/verify/permit/{token}`), `issued_date`, `processed_by`, `approved_by`, `status` (generated/signed/released/**revoked**), `revoke_reason` (text, nullable — required reason captured when revoking), `building_official_name`/`_title`/`_designation`/`_license_no` (nullable strings — a one-time snapshot of the active `building_official` Signatory taken at generation time; never updated afterward, so a permit's printed/verified issuer never changes even if the Signatory record is later edited). SoftDeletes — a revoked permit is soft-deleted (not removed) and kept forever at `status = 'revoked'` with its original `permit_number` intact, so it can be restored (un-trashed, `status` back to `generated`) rather than replaced by a newly-numbered permit; `permits()` (via `HasPermitApplicationBehavior`) reliably returns only the current active permit per application. The Turn Around Time column on the BP/OP application/permits indexes reads this table's `created_at` (the actual generation timestamp — `issued_date` has no time component) against the application's `submitted_at`/`created_at`; no new columns were added for that.

### `documents`
Polymorphic, `document_type` (e.g., pdf.building-permit), `title`, `file_path`, `counter`, `document_date`, `generated_by`.

---

## Polymorphic Morph Map

Registered in `AppServiceProvider`:

| Morph Alias | Model |
|-------------|-------|
| `bp` | `App\Models\Application` |
| `op` | `App\Models\OccupancyApplication` |

The 7 downstream tables (assessments, billings, collections, permits, documents, application_occupancy_groups, application_requirements) use `applicationable_type` + `applicationable_id` to reference either BP or OP. Legacy `application_id` column is kept nullable for backward compatibility.
