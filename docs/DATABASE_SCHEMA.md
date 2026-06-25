# Database Schema

> Database: `epms_db` (MariaDB 12.3) | All tables use timestamps unless noted.

---

## Auth & System Tables

### `users`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint PK | | Auto-increment |
| name | string | | Display name |
| email | string (unique) | | Login email |
| password | string | | Hashed password |
| email_verified_at | timestamp | Yes | Verification date |
| remember_token | string(100) | Yes | Session token |
| first_name | string | Yes | First name |
| middle_name | string | Yes | Middle name |
| last_name | string | Yes | Last name |
| suffix | string(20) | Yes | Name suffix |
| phone | string(20) | Yes | Phone number |
| department | string | Yes | Department |
| position | string | Yes | Job position |
| avatar | string | Yes | Avatar file path |
| is_active | boolean | | Default: true |
| must_change_password | boolean | | Default: false |
| last_login_at | timestamp | Yes | Last login time |
| last_login_ip | string(45) | Yes | Last login IP |
| deleted_at | timestamp | Yes | Soft delete |

### `settings`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| group | string | Default: 'general' |
| key | string (unique) | Setting key |
| value | text | Setting value |
| type | string | Default: 'string' |
| description | string | Human description |

### Spatie Permission Tables
- `permissions` — id, name, guard_name
- `roles` — id, team_foreign_key, name, guard_name
- `model_has_permissions` — permission_id, model_type, model_id
- `model_has_roles` — role_id, model_type, model_id
- `role_has_permissions` — permission_id, role_id

### Spatie Activity Log
- `activity_log` — id, log_name, description, subject_type/id, causer_type/id, properties (json), event, batch_uuid

### Laravel System Tables
- `password_reset_tokens` — email (PK), token, created_at
- `sessions` — id (PK), user_id (FK), ip_address, user_agent, payload, last_activity
- `cache` / `cache_locks` — key, value, expiration
- `jobs` / `job_batches` / `failed_jobs` — standard Laravel queue tables
- `notifications` — id, type, notifiable_type/id, data, read_at

---

## Geographic Tables

### `provinces`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| psgc_code | string | Philippine Standard Geographic Code |
| name | string | Province name |
| region | string | Yes | Region name |
| is_active | boolean | Default: true |

### `cities`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| province_id | FK → provinces (cascade) | |
| psgc_code | string | PSGC code |
| name | string | City/municipality name |
| zip_code | string(10) | Yes | Postal code |
| is_active | boolean | Default: true |

### `barangays`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| city_id | FK → cities (cascade) | |
| psgc_code | string | PSGC code |
| name | string | Barangay name |
| is_active | boolean | Default: true |

---

## Reference Tables

### `permit_types`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| code | string(20) unique | BP, OP, FP, EP, DP, SP, ELP, MP, PP, ECP |
| name | string | Full name |
| description | text | Yes |
| is_active | boolean | Default: true |
| sort_order | integer | Display order |

### `application_types`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| permit_type_id | FK → permit_types | Yes | Links type to permit |
| name | string | New, Renewal, Amendatory (BP); Full, Partial (OP) |
| description | text | Yes |
| is_active | boolean | Default: true |
| sort_order | integer | Display order |

### `scope_of_works`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| name | string | e.g., New Construction, Addition, Renovation |
| category | string | Yes | construction, other |
| is_active | boolean | Default: true |
| sort_order | integer | Display order |

### `form_of_ownerships`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| name | string | e.g., Sole Proprietorship, Partnership, Corporation |
| is_active | boolean | Default: true |

### `occupancy_groups`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| code | string(10) | A through J |
| name | string | Group name |
| description | text | Yes |
| is_active | boolean | Default: true |
| sort_order | integer | Display order |

### `occupancy_sub_groups`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| occupancy_group_id | FK → occupancy_groups (cascade) | |
| code | string(20) | Yes | Sub-group code |
| name | string | Sub-group name |
| description | text | Yes |
| is_active | boolean | Default: true |
| sort_order | integer | Display order |

### `occupancy_divisions`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| occupancy_group_id | FK → occupancy_groups (cascade) | |
| code | string(20) | Division code (A1, A2, B1, etc.) |
| name | string | Division name |
| assessment_mode | enum | 'cumulative' or 'non_cumulative' |
| is_active | boolean | Default: true |

### Other Reference Tables
- `building_parts` — id, name, is_active
- `signatories` — id, role, name, title, designation, department, license_no, is_active
- `land_classifications` — id, name, code, is_active

---

## Fee Schedule Tables

### `fee_categories`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| permit_type_id | FK → permit_types (cascade) | |
| code | string(30) unique | e.g., CON, ELEC, MECH, PLUMB, OCC |
| name | string | Category name |
| description | text | Yes |
| is_active | boolean | Default: true |
| sort_order | integer | Display order |

### `fee_types`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| fee_category_id | FK → fee_categories (cascade) | |
| code | string(50) | Unique within category |
| name | string | Fee type name |
| description | text | Yes |
| computation_method | enum | fixed, per_unit, range_based, cumulative_range, percentage, formula |
| has_excess | boolean | Default: false |
| has_minimum | boolean | Default: false |
| has_maximum | boolean | Default: false |
| is_active | boolean | Default: true |
| sort_order | integer | Display order |

### `fee_schedules`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| fee_type_id | FK → fee_types (cascade) | |
| occupancy_division_id | FK → occupancy_divisions | Yes |
| occupancy_sub_group_id | FK → occupancy_sub_groups | Yes |
| range_from | decimal(15,2) | Default: 0 |
| range_to | decimal(15,2) | Default: 0 |
| fixed_fee | decimal(15,2) | Default: 0 |
| fee_per_unit | decimal(15,2) | Default: 0 |
| percentage | decimal(8,4) | Default: 0 |
| excess_threshold | decimal(15,2) | Default: 0 |
| excess_fee | decimal(15,2) | Default: 0 |
| excess_every | decimal(15,2) | Default: 0 |
| minimum_fee | decimal(15,2) | Default: 0 |
| maximum_fee | decimal(15,2) | Default: 0 |
| formula | text | Yes |
| is_active | boolean | Default: true |

**Index:** [fee_type_id, range_from, range_to]

---

## Zoning Fee Tables

### `land_use_and_zoning_fees`

> Dedicated table for locational clearance fees, matching BOPMS `land_use_and_zoning_fees`. Organized by occupancy sub-group with range-based + excess computation.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| occupancy_sub_group_id | FK → occupancy_sub_groups | Which sub-group this rate applies to |
| range_from | decimal(15,2) | Cost range start |
| range_to | decimal(15,2) | Cost range end |
| amount | decimal(15,2) | Base/fixed fee for this range |
| excess_of | decimal(15,2) | Threshold above which excess applies |
| percentage | decimal(10,6) | Per-peso rate for excess (e.g., 0.001) |
| is_active | boolean | Default: true |

**Index:** [occupancy_sub_group_id, range_from, range_to]

**162 rows** across 52 sub-groups, organized in 6 fee patterns (Residential, Commercial, Mid-Tier, Heavy, Flat, Top-Tier).

### `certification_zoning_fees`

> Flat certification fee, matching BOPMS `certification_zoning_fees`.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| occupancy_sub_group_id | FK → occupancy_sub_groups | Yes | NULL = applies to all |
| amount | decimal(15,2) | Fixed certification fee (P500) |
| is_active | boolean | Default: true |

---

## Application Tables

### `applications` (Building Permit only)

> OP-specific columns (bp_number, bp_issued_date, fsec_no, fsec_issued_date, completion_date, applies_for) have been removed. OP applications are now in the separate `occupancy_applications` table. The `permit_type_id` column is retained for now.

| Column Group | Columns |
|-------------|---------|
| **Identity** | id, permit_type_id (FK), application_type_id (FK), app_year, app_month, app_counter, application_number (unique), area_number |
| **Status** | status (default: 'draft'), source (walk_in/online) |
| **Header** | complexity, applies_to |
| **Applicant** | applicant_first_name, applicant_middle_name, applicant_last_name, applicant_suffix, applicant_tin, applicant_contact_no, applicant_email, applicant_govt_id, applicant_id_date_issued, applicant_id_place_issued, applicant_date_signed |
| **Enterprise** | enterprise_name, form_of_ownership_id (FK) |
| **Applicant Address** | applicant_province_id (FK), applicant_city_id (FK), applicant_barangay_id (FK), applicant_street, applicant_zip_code |
| **Project** | project_title, scope_of_work_id (FK), scope_of_work_details |
| **Building Location** | lot_no, block_no, tct_no, tax_dec_no, land_classification_id (FK), building_street, building_barangay_id (FK) |
| **Building Specs** | no_of_storeys, no_of_units, occupancy_classified, total_floor_area, lot_area |
| **Costs** | building_cost, electrical_cost, mechanical_cost, electronics_cost, plumbing_cost, other_equipment_cost, equipment_cost_1-4, total_estimated_cost |
| **Timeline** | proposed_construction_date, expected_completion_date |
| **Engineer** | engineer_name, engineer_prc_no, engineer_prc_validity, engineer_ptr_no, engineer_ptr_date_issued, engineer_ptr_issued_at, engineer_tin, engineer_address, engineer_date_signed |
| **PEE** | pee_name, pee_prc_no, pee_prc_validity, pee_date_signed, pee_ptr_no, pee_ptr_date_issued, pee_ptr_issued_at, pee_address, pee_tin |
| **SEW** | sew_profession, sew_name, sew_prc_no, sew_prc_validity, sew_date_signed, sew_ptr_no, sew_ptr_date_issued, sew_ptr_issued_at, sew_address, sew_tin |
| **Owner** | owner_name, owner_address, owner_govt_id, owner_id_date_issued, owner_id_place_issued, owner_date_signed |
| **Electrical** | include_electrical, total_connected_load, total_transformer_capacity, total_generator_capacity |
| **Processing** | entered_by (FK), assessed_by (FK), approved_by (FK), client_user_id (FK), submitted_at, assessed_at, approved_at, paid_at, released_at, cancelled_at, cancellation_reason, issued_date |
| **System** | remarks, deleted_at, created_at, updated_at |

**Indexes:** [permit_type_id, status], [app_year, app_month], [status]

### `occupancy_applications` (Occupancy Permit only)

> Separate table for OP applications. Shares common fields with `applications` but has OP-specific fields and omits BP-specific fields (no cost fields, no engineer/PEE/SEW, no electrical, no scope_of_work, no complexity).

| Column Group | Columns |
|-------------|---------|
| **Identity** | id, application_type_id (FK), app_year, app_month, app_counter, application_number (unique), area_number |
| **Status** | status (default: 'draft'), source (walk_in/online) |
| **Applicant** | applicant_first_name, applicant_middle_name, applicant_last_name, applicant_suffix, applicant_tin, applicant_contact_no, applicant_email, applicant_govt_id, applicant_id_date_issued, applicant_id_place_issued, applicant_date_signed |
| **Enterprise** | enterprise_name, form_of_ownership_id (FK) |
| **Applicant Address** | applicant_province_id (FK), applicant_city_id (FK), applicant_barangay_id (FK), applicant_street, applicant_zip_code |
| **Project** | project_title |
| **Building Location** | lot_no, block_no, tct_no, tax_dec_no, land_classification_id (FK), building_street, building_barangay_id (FK) |
| **Building Specs** | no_of_storeys, no_of_units, occupancy_classified, total_floor_area, lot_area |
| **OP-Specific** | bp_number, bp_issued_date, fsec_no, fsec_issued_date, completion_date |
| **Owner** | owner_name, owner_address, owner_govt_id, owner_id_date_issued, owner_id_place_issued, owner_date_signed |
| **Processing** | entered_by (FK), assessed_by (FK), approved_by (FK), client_user_id (FK), submitted_at, assessed_at, approved_at, paid_at, released_at, cancelled_at, cancellation_reason, issued_date |
| **System** | remarks, deleted_at, created_at, updated_at |

**Indexes:** [status], [app_year, app_month]

### `application_occupancy_groups`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| applicationable_type | varchar(10) | Morph type: 'bp' or 'op' |
| applicationable_id | bigint unsigned | FK to applications or occupancy_applications |
| application_id | bigint unsigned | Yes | Legacy column, nullable. Use applicationable_type/id instead |
| occupancy_group_id | FK → occupancy_groups (cascade) | |
| occupancy_sub_group_id | FK → occupancy_sub_groups | Yes |
| others_text | string | Yes | Free text for "Others" |

**Unique:** [applicationable_type, applicationable_id, occupancy_sub_group_id]

### `application_requirements`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| applicationable_type | varchar(10) | Morph type: 'bp' or 'op' |
| applicationable_id | bigint unsigned | FK to applications or occupancy_applications |
| application_id | bigint unsigned | Yes | Legacy column, nullable. Use applicationable_type/id instead |
| requirement_name | string | |
| file_path | string | |
| original_filename | string | |
| status | enum | pending, approved, rejected |
| reviewer_remarks | text | Yes |
| reviewed_by | FK → users | Yes |
| reviewed_at | timestamp | Yes |

---

## Assessment Tables

### `assessments`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| applicationable_type | varchar(10) | Morph type: 'bp' or 'op' |
| applicationable_id | bigint unsigned | FK to applications or occupancy_applications |
| application_id | bigint unsigned | Yes | Legacy column, nullable. Use applicationable_type/id instead |
| assessment_type | string(30) | building, occupancy, zoning |
| filing_fee | decimal(15,2) | Default: 0 |
| processing_fee | decimal(15,2) | Default: 0 |
| total_amount | decimal(15,2) | Default: 0 |
| status | enum | draft, finalized |
| assessed_by | FK → users | Yes |
| finalized_at | timestamp | Yes |
| deleted_at | timestamp | Yes |

**Index:** [application_id, assessment_type]

### `assessment_items`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| assessment_id | FK → assessments (cascade) | |
| fee_category_id | FK → fee_categories | Yes |
| fee_type_id | FK → fee_types | Yes |
| fee_code | string(50) | |
| description | string | |
| quantity | decimal(15,2) | Default: 0 |
| unit_fee | decimal(15,2) | Default: 0 |
| excess_fee | decimal(15,2) | Default: 0 |
| inspection_fee | decimal(15,2) | Default: 0 |
| amount | decimal(15,2) | Default: 0 |
| computation_details | json | Yes |
| is_active | boolean | Default: true |
| deleted_at | timestamp | Yes |

**Index:** [assessment_id, fee_code]

### `zoning_assessments`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| application_id | FK → applications (cascade, unique) | 1:1 |
| project_lifespan | string | Yes |
| project_significance | string | Yes |
| project_classification | string | Yes |
| site_zoning_classification | string | Yes |
| right_over_lands | string | Yes |
| radius_covered | string | Yes |
| land_use_radius | string | Yes |
| findings_evaluation | text | Yes |
| decision_recommended | text | Yes |
| date_evaluation | date | Yes |
| certificate_date | date | Yes |
| project_status | string | Yes |
| boundary_north/south/east/west | string | Yes |
| building_coverage | string | Yes |
| secure_ecc | boolean | Default: false |
| off_street_parking | boolean | Default: false |
| decision_no | unsignedInt | Yes |
| assessed_by | FK → users | Yes |
| deleted_at | timestamp | Yes |

---

## Billing & Collection Tables

### `billings`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| applicationable_type | varchar(10) | Morph type: 'bp' or 'op' |
| applicationable_id | bigint unsigned | FK to applications or occupancy_applications |
| application_id | bigint unsigned | Yes | Legacy column, nullable. Use applicationable_type/id instead |
| billing_number | string(30) unique | BL-YYYY-MM-NNNNN |
| total_amount | decimal(15,2) | Default: 0 |
| status | enum | unpaid, partial, paid, void |
| generated_by | FK → users | Yes |
| deleted_at | timestamp | Yes |

### `billing_items`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| billing_id | FK → billings (cascade) | |
| category | string | Fee category |
| description | string | Fee description |
| amount | decimal(15,2) | Default: 0 |
| sort_order | integer | Display order |

### `collections`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| applicationable_type | varchar(10) | Morph type: 'bp' or 'op' |
| applicationable_id | bigint unsigned | FK to applications or occupancy_applications |
| application_id | bigint unsigned | Yes | Legacy column, nullable. Use applicationable_type/id instead |
| billing_id | FK → billings | Yes |
| or_number | string(30) unique | Official Receipt number |
| or_date | date | Receipt date |
| paid_by | string | Payer name |
| amount_due | decimal(15,2) | |
| amount_received | decimal(15,2) | |
| change_amount | decimal(15,2) | |
| payment_mode | enum | cash, check, online |
| bank_name | string | Yes | For check payments |
| check_number | string | Yes | For check payments |
| check_date | date | Yes | For check payments |
| online_reference | string | Yes | For online payments |
| collected_by | FK → users | Yes |
| status | enum | active, void |
| deleted_at | timestamp | Yes |

### `collection_details`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| collection_id | FK → collections (cascade) | |
| fee_category | string | Category name |
| description | string | Fee description |
| amount | decimal(15,2) | Default: 0 |
| is_active | boolean | Default: true |

### `void_transactions`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| collection_id | FK → collections (cascade) | |
| or_number | string(30) | Voided OR number |
| reason | text | Void reason |
| voided_by | FK → users | |
| voided_at | timestamp | |

---

## Permit & Document Tables

### `permits`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| applicationable_type | varchar(10) | Morph type: 'bp' or 'op' |
| applicationable_id | bigint unsigned | FK to applications or occupancy_applications |
| application_id | bigint unsigned | Yes | Legacy column, nullable. Use applicationable_type/id instead |
| permit_type_id | FK → permit_types (cascade) | |
| permit_year | year | |
| permit_month | tinyint | |
| permit_counter | unsignedInt | |
| permit_number | string(30) unique | CODE-YYYY-MM-NNNNN |
| issued_date | date | |
| processed_by | FK → users | Yes |
| approved_by | FK → users | Yes |
| status | enum | generated, signed, released |
| deleted_at | timestamp | Yes |

**Index:** [permit_type_id, permit_year]

### `documents`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| applicationable_type | varchar(10) | Morph type: 'bp' or 'op' |
| applicationable_id | bigint unsigned | FK to applications or occupancy_applications |
| application_id | bigint unsigned | Yes | Legacy column, nullable. Use applicationable_type/id instead |
| document_type | string(50) | e.g., pdf.building-permit |
| title | string | |
| file_path | string | Yes |
| counter | unsignedInt | Yes |
| document_date | date | |
| generated_by | FK → users | Yes |

**Index:** [applicationable_type, applicationable_id, document_type]

---

## Polymorphic Morph Map

Registered in `AppServiceProvider`:

| Morph Alias | Model |
|-------------|-------|
| `bp` | `App\Models\Application` |
| `op` | `App\Models\OccupancyApplication` |

The 7 downstream tables (assessments, billings, collections, permits, documents, application_occupancy_groups, application_requirements) use `applicationable_type` (varchar 10) + `applicationable_id` (bigint unsigned) to reference either BP or OP applications. The old `application_id` column is kept on each table for backward compatibility during transition.
