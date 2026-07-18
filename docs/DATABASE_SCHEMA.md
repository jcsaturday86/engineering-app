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

**Key settings:** `assessment.electrical_inspection_percentage` (default 10), `assessment.default_filing_fee`, `assessment.default_processing_fee`, `general.logo` (type `file` — city/LGU official seal, uploaded via Settings → General, GD-resized to max 400px before storage; printed on permits, application forms, assessment summaries, billing statements, official receipts, and used as the browser-tab favicon fallback), `general.favicon` (type `file` — dedicated browser-tab icon shown on every page; falls back to `general.logo`, then to the static `favicon.ico`), `general.dpwh_logo` (type `file` — DPWH logo for the Occupancy Permit PDF and the Building Permit PDF's right header cell; falls back to the static `public/images/dpwh-logo.png` asset when empty), `general.national_govt_logo` (type `file` — National Government logo shown on the right of the BP Unified Application Form letterhead and the left of the OP Application Form header), `general.city` / `general.province` (printed on PDF letterheads; seeded as "City of San Fernando" / "La Union"), `general.area_number` (Area No. digit box on the BP application form when the application has none of its own), `general.zip_code` (Building Permit PDF), `general.domain` (public domain used to build the QR verification link on printed permits; blank falls back to `config('app.url')`).

File-type settings are each stored at a fixed path derived from their key (`SettingsController::update()`, e.g. `logos/city-seal.png` for `general.logo`, `logos/favicon.png` for `general.favicon`, `logos/dpwh-logo.png` for `general.dpwh_logo`, `logos/national-govt-logo.png` for `general.national_govt_logo`) — previously all file uploads were hardcoded to the same path regardless of key, which would have made two file settings silently clobber each other.

The `Setting` model provides two static helpers used by every PDF-producing controller: `Setting::general()` (all `group = general` settings keyed by key) and `Setting::imageDataUri($settings, $key)` (base64 data-URI for a file setting, or null if unset/missing) — centralizing the seal/logo embedding pattern so uploaded branding propagates to all printed documents automatically.

### Spatie / Laravel System Tables
- `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` — includes `view-audit-logs`, granted only to `super-admin`
- `activity_log` — id, log_name, description, subject_type/id, causer_type/id, properties (json), event. Surfaced to users via `/reports/audit-logs` (super-admin only), filterable by search/causer/subject type/event/month
- `password_reset_tokens`, `sessions`, `cache`, `jobs`, `failed_jobs`, `notifications`

---

## Geographic Tables

### `provinces` / `cities` / `barangays`

Standard hierarchical geo tables with `psgc_code`, `name`, `is_active`. ~42K barangay records seeded from Philippine PSA data.

---

## Reference Tables

### `permit_types`
`code` (BP, OP, FP, EP, DP, SGP, SP, ELP, AI, PP, ECP), `name`, `is_active`, `sort_order`. Active: BP, OP, DP, SGP, FP, AI. `SP` ("Sign Permit") is a separate, still-unbuilt placeholder — not to be confused with SGP (Signage Permit). `AI` ("Annual Inspection") was originally built and seeded as `MP` ("Mechanical Permit") — renamed via migration to `AI` once the module was rebuilt around the official Annual Inspection Fees schedule instead of the original equipment-based tabs (see `docs/PROJECT_CONTEXT.md`).

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

> `DEMO_FEE` (Demolition Permit, `permit_type_id` scoped to DP) holds 6 real NBC demolition-fee types with seeded rates, each carrying a `unit_label`. `SGP_FEE` (Signage Permit, `permit_type_id` scoped to SGP) is a single empty category with no seeded `FeeType`/`FeeSchedule` rows — SGP assessment is manual-entry only via the generic fee-item fallback form. `FP_FEE` (name "Fencing Permit Fees", Fencing Permit, `permit_type_id` scoped to FP) is likewise empty of its own `FeeType`/`FeeSchedule` rows — it does not mint new fee types. Instead, `AssessmentItem` rows tagged `fee_category_id = FP_FEE` point their `fee_type_id` at existing `ACC_FEE`-scoped `FeeType` rows (`ASS_FENCE_MASONRY`, `ASS_FENCE_INDIG`, `ASS_LINE_GRADE`, `ASS_GP_INSPECT`, `ASS_GP_EXCAV`, `ASS_GP_ISSUANCE`, `ASS_GP_FOUND`, `ASS_GP_OTHER`, `ASS_GP_ENCROACH`) — reusing the ancillary/accessory fee schedule rather than duplicating it. `fee_category_id` and `fee_type_id` are independent, unenforced foreign keys, so this cross-category reference is intentional, not a data error.

> **Annual Inspection (AI)** uses exactly 4 `FeeCategory` rows scoped to the `AI` permit type: `AINSP_GEN` (General/Occupancy/Electrical), `AINSP_ELECTRONICS`, `AINSP_MECH`, `AINSP_ELEC`. The first three source their `FeeType`/`FeeSchedule` rows from the `ANN_INSP` category's `AINSP_*` codes (49 rows, rebuilt against the official rate schedule); `AINSP_ELEC` reuses the existing BP `ELEC_*` rows by code (same decoupled `fee_category_id`/`fee_type_id` cross-reference pattern as FP above). Five earlier categories from this module's original build as "Mechanical Permit" — `AI_AC`/`AI_MACH`/`AI_ESC`/`AI_ELEV`/`AI_GENSET`, which reused BP's `MECH_*`/`INSP_*` codes for 5 equipment tabs — were deleted entirely once the module was rebuilt around the official schedule; they no longer exist in the schema.

### `fee_types`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| fee_category_id | FK → fee_categories | |
| code | string(50) | e.g., CONST_A1, ELEC_TCL, MECH_REFRIG, INSP_REFRIG, DEMO_FLOOR_AREA |
| name | string | |
| computation_method | enum | fixed, per_unit, range_based, cumulative_range, percentage, formula |
| unit_label | string(40) | Yes | Physical unit the fee is measured in ("sq.m.", "lineal meter(s)", "unit(s)") — drives the assessment tab's dynamic quantity-field label. Added for DEMO_FEE; nullable, unused by other categories (they still hard-code unit labels as a per-view JS map) |
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

### `demolition_applications` (Demolition Permit only)

| Column Group | Columns |
|-------------|---------|
| **Identity** | id, application_type_id (FK), app_year, app_month, app_counter, application_number (unique, DP-YYYY-MM-NNNNN) |
| **Status** | status (default: 'draft'), source (walk_in/online) |
| **Applicant** | applicant_first/middle/last_name, applicant_tin, applicant_telephone |
| **Enterprise** | owned_by_enterprise, enterprise_name, form_of_ownership_id (FK) |
| **Applicant Address** | applicant_province/city/barangay_id (FK), applicant_street, applicant_zip_code, applicant_ctc_no, applicant_ctc_date_issued, applicant_ctc_place_issued |
| **Location of Demolition Works** | lot_no, block_no, tct_no, tax_dec_no, demolition_street, demolition_barangay_id (FK) — note: distinct from the applicant's own barangay; `DemolitionApplication::buildingBarangay()` is overridden to point here |
| **Scope of Work** | scope_of_work (enum: demolition/others), scope_of_work_detail |
| **Full-time Inspector/Supervisor** | inspector_name, inspector_address, inspector_telephone, inspector_prc_no, inspector_prc_validity, inspector_ptr_no, inspector_ptr_date_issued, inspector_ptr_issued_at, inspector_tin |
| **Lot Owner Consent** | owner_name, owner_ctc_no, owner_ctc_date_issued, owner_ctc_place_issued |
| **Processing** | entered_by, assessed_by, approved_by, client_user_id (all FK → users), submitted/assessed/approved/paid/released/cancelled_at, cancellation_reason, issued_date |
| **System** | remarks, deleted_at |

**Indexes:** [status], [app_year, app_month]

### `signage_applications` (Signage Permit only)

A much simpler table than `demolition_applications` — no enterprise, CTC, inspector, or lot-owner fields.

| Column Group | Columns |
|-------------|---------|
| **Identity** | id, application_type_id (FK), app_year, app_month, app_counter, application_number (unique, SGP-YYYY-MM-NNNNN) |
| **Status** | status (default: 'draft'), source (walk_in/online) |
| **Applicant** | applicant_first/middle/last_name |
| **Applicant Address** | applicant_province/city/barangay_id (FK), applicant_street, applicant_zip_code |
| **Scope of Work** | install (boolean), install_detail (text), attach (boolean), attach_detail (text), paint (boolean), paint_detail (text) — three independent checkboxes, each with its own detail textbox; at least one required |
| **Signage Details** | wordings (text), premises_of (string) |
| **Processing** | entered_by, assessed_by, approved_by, client_user_id (all FK → users), submitted/assessed/approved/paid/released/cancelled_at, cancellation_reason, issued_date |
| **System** | remarks, deleted_at |

**Indexes:** [status], [app_year, app_month]

> `SignageApplication::buildingBarangay()` is overridden to alias `applicantBarangay()` (not a separate `building_barangay_id` column — SGP has no site-location address distinct from the applicant's own).

### `fencing_applications` (Fencing Permit only)

Structurally closest to `demolition_applications` — has enterprise, design-professional, inspector, and lot-owner blocks — and uses `construction_*` naming (not `demolition_*`) for the site-location block. A later migration added an `applicant_ctc_*` triplet to the Applicant Address block (DP has no equivalent applicant-level CTC fields — only its lot-owner-consent block carries CTC data), matching the pattern already used for Design Professional, Full-Time Inspector, and Consent of Lot Owner.

| Column Group | Columns |
|-------------|---------|
| **Identity** | id, application_type_id (FK), app_year, app_month, app_counter, application_number (unique, FP-YYYY-MM-NNNNN) |
| **Status** | status (default: 'draft'), source (walk_in/online) |
| **Applicant** | applicant_first/middle/last_name, applicant_tin, applicant_telephone |
| **Enterprise** | owned_by_enterprise, enterprise_name, form_of_ownership_id (FK) |
| **Applicant Address** | applicant_province/city/barangay_id (FK), applicant_street, applicant_zip_code, applicant_ctc_no, applicant_ctc_date_issued, applicant_ctc_issued_at |
| **Location of Construction** | lot_no, block_no, tct_no, tax_dec_no, construction_street, construction_barangay_id (FK) — distinct from the applicant's own barangay, mirroring DP's `demolition_barangay_id` pattern |
| **Scope of Work** | scope_of_work (enum: new_construction/erection/addition/repair/others), scope_of_work_detail |
| **Design Professional, Plans and Specifications** | design_professional_name, design_professional_address, design_professional_prc_no, design_professional_prc_validity, design_professional_ptr_no, design_professional_ptr_date_issued, design_professional_ptr_issued_at, design_professional_tin |
| **Full-Time Inspector or Supervisor** | inspector_name, inspector_address, inspector_prc_no, inspector_prc_validity, inspector_ptr_no, inspector_ptr_date_issued, inspector_ptr_issued_at, inspector_tin — same field shape as the Design Professional block, appended as flat columns (see history note below) |
| **Consent of Lot Owner** | owner_name, owner_address, owner_ctc_no, owner_ctc_date_issued, owner_ctc_issued_at |
| **Processing** | entered_by, assessed_by, approved_by, client_user_id (all FK → users), submitted/assessed/approved/paid/released/cancelled_at, cancellation_reason, issued_date |
| **System** | remarks, deleted_at |

**Indexes:** [status], [app_year, app_month]

> **History note:** `fencing_applications` originally shipped alongside a child table `fencing_inspectors` (one-to-many, with `is_primary`/`sort_order`, to support a repeatable "Add Inspector" UI). This was simplified in the same session before release — `fencing_inspectors` was dropped and replaced with the 8 flat `inspector_*` columns listed above, directly on `fencing_applications`, mirroring `design_professional_*` exactly. The `fencing_inspectors` table does not exist in the current schema.

### `annual_inspection_applications` (Annual Inspection only)

| Column Group | Columns |
|-------------|---------|
| **Identity** | id, application_type_id (FK), app_year, app_month, app_counter, application_number (unique, AI-YYYY-MM-NNNNN) |
| **Status** | status (default: 'draft'), source (walk_in/online) |
| **Application Kind** | application_kind (enum: new/yearly, default 'new') — Yearly = annual re-inspection; fee lookups otherwise identical between the two |
| **Applicant** | owner_name (Name of Owner/Lessee — no separate first/middle/last split) |
| **Location** | location_street (nullable), location_barangay_id (FK barangays) |
| **Processing** | entered_by, assessed_by, approved_by, client_user_id (all FK → users), submitted/assessed/approved/paid/released/cancelled_at, cancellation_reason, issued_date |
| **System** | deleted_at |

**Indexes:** [status], [app_year, app_month]

> **History note:** this table (and its model/controller) originally shipped as `mechanical_applications`/`MechanicalApplication`/`MechanicalApplicationController`, backing a 5-equipment-tab "Mechanical Permit" (MP) module with multi-permit generation. A later rename migration renamed the table, backfilled `PermitType.code` (`MP`→`AI`), and every route/morph/model reference to `AnnualInspectionApplication`/`AI`, once the module's fee tabs were rebuilt around the official Annual Inspection Fees schedule. The form is deliberately minimal (owner name + location only) — all equipment/quantity data lives in the Assessment, not the application record.

### `annual_inspection_permit_units` (dormant)
`mechanical_application_id` (FK, cascade — column name retained from the pre-rename schema), `group_code`, `description`, `quantity`, `amount`, `permit_id` (FK permits, nullable), `generated_at`, `deleted_at`. Built to support the original multi-permit-per-application generation (one row per generated `Permit`, grouped by equipment type). Left in place, unused, after Permit Generation was switched to single-permit-per-application to match every other permit type — no code writes to this table anymore, kept dormant rather than dropped.

### `application_occupancy_groups`
Polymorphic (`applicationable_type` / `applicationable_id`), `occupancy_group_id`, `occupancy_sub_group_id`, `others_text`. BP and OP only — DP, SGP, and FP have no occupancy-group concept.

### `application_requirements`
Polymorphic, `requirement_name`, `file_path`, `original_filename`, `status` (pending/approved/rejected), `reviewer_remarks`, `reviewed_by`, `reviewed_at`.

---

## Assessment Tables

### `assessments`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| applicationable_type / applicationable_id | varchar(10) / bigint | Polymorphic: 'bp', 'op', 'dp', 'sgp', 'fp', or 'ai' |
| assessment_type | string(30) | building, occupancy, demolition, signage, fencing, zoning. AI's assessment_type is `'mechanical'` (retained from the pre-rename schema, not renamed) |
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
| quantity | decimal(15,2) | For AI's measured-value fee items (kW/ton/kVA/lineal meter/cu.m.) this is the physical measurement, not a count — see `computation_details.quantity_count` below |
| unit_fee | decimal(15,2) | Base rate shown in table |
| excess_fee | decimal(15,2) | Excess portion of base fee |
| inspection_fee | decimal(15,2) | NBC inspection fee. **BP's `ELEC`/`MECH` categories always store 0** here now (inspection fees removed from the BP assessment — see `docs/PROJECT_CONTEXT.md`); Annual Inspection's own fee computation still uses this column/mechanism via `resolveInspectionFee()` |
| amount | decimal(15,2) | Base permit fee only (does NOT include inspection_fee). For AI's Mechanical/Electrical tabs, `amount = baseFee × quantity_count` (see below) |
| computation_details | json | Yes | Inputs/outputs for audit. AI's Mechanical/Electrical items store `quantity_count` (int, default 1) here — the equipment-count multiplier applied on top of the measured-value base fee, entered via a separate "Quantity" form field distinct from the measurement `quantity` column above |
| is_active | boolean | Default: true |
| deleted_at | timestamp | Yes |

> **Grand total formula:** `sum(amount) + sum(inspection_fee) + filing_fee + processing_fee` — this is consistent across CONST, ELEC, and MECH categories (ELEC/MECH's `inspection_fee` term is always 0 on BP now, a no-op in the formula rather than a formula change).

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
| `dp` | `App\Models\DemolitionApplication` |
| `sgp` | `App\Models\SignageApplication` |
| `fp` | `App\Models\FencingApplication` |
| `ai` | `App\Models\AnnualInspectionApplication` (morph alias renamed from `mp` in the same migration that renamed the underlying table/model) |

The 7 downstream tables (assessments, billings, collections, permits, documents, application_occupancy_groups, application_requirements) use `applicationable_type` + `applicationable_id` to reference BP, OP, DP, SGP, FP, or AI (DP/SGP/FP/AI never populate `application_occupancy_groups`/`application_requirements` — no occupancy-group or document-upload concept on any of them). Legacy `application_id` column is kept nullable for backward compatibility.

Every controller/service that branches on permit type by `match ($application->getPermitTypeCode()) { 'OP' => 'op', 'DP' => 'dp', 'SGP' => 'sgp', 'FP' => 'fp', 'AI' => 'ai', default => 'bp' }` (or the reverse) must include all 6 arms — several of these `match()` blocks were found missing an `SGP` arm (silently falling through to `'bp'`/the BP route) during the SGP build, including one that had already been missing a `DP` arm since DP was first built (`BillingService::generateFor()`, `collections/index.blade.php`, `permits/index.blade.php`, `verify/permit.blade.php`); the same class of bug was proactively checked for and fixed when AI was added — `PermitController::doGenerate()`'s `$morphType` match had no `'AI'` arm at one point during the single-permit-generation switch, which would have silently created `Permit` rows pointing at the wrong parent table (`applicationable_type = 'bp'`) had it shipped.
