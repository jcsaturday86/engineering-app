# BOPMS Database Schema Reference

> Database: `db_ebps` (MySQL) | Schema source: `database/backup/db_engineering.sql`

---

## Application Tables

### `application_building_permits`

| Column | Description |
|--------|-------------|
| `id` | PK |
| `project_title` | Project title |
| `year`, `month`, `counter` | Application number generation |
| `complexity_id` | FK → application_complexities (Simple/Complex) |
| `application_type_id` | FK → application_types (New/Renewal/Amendatory) |
| `applies_to` | LC=Locational Clearance, FS=Fire Safety |
| `full_name`, `tin_no`, `contact_no` | Applicant info (encrypted) |
| `owned_by_enterprise` | Enterprise name |
| `form_of_ownership_id` | FK → form_of_ownerships |
| `province_id`, `city_id`, `barangay_id` | Applicant address |
| `street_no`, `zip_code` | Applicant address detail |
| `lot_no`, `blk_no`, `tct_no`, `tax_dec_no` | Property identifiers |
| `street`, `bldg_barangay_id` | Building location |
| `land_classification_id` | FK → land_classifications |
| `scope_of_work_id` | FK → scope_of_works |
| `scope_of_work_details` | Additional details |
| `occupancy_classified` | Classification text |
| `no_of_units`, `no_of_storey` | Building specs |
| `total_floor_area`, `lot_area` | Area in sqm |
| `building_cost`, `electrical_cost`, `mechanical_cost` | Cost breakdown |
| `electronics_cost`, `plumbing_cost` | Cost breakdown |
| `equipment_one_cost` through `equipment_four_cost` | Equipment costs |
| `total_estimate_cost` | Sum of all costs |
| `proposed_date_construction`, `expected_date_completion` | Timeline |
| `issued_date` | Date issued |
| `include_electrical` | 0/1 flag |
| `total_connected_load`, `total_transformer_capacity`, `total_generator_capacity` | Electrical data |
| Engineer fields | `engineer_*` (name, PRC, PTR, TIN, address, date_signed) |
| Owner fields | `owner_*` (name, address, govt_id, date_signed) |
| PEE fields | `pee_*` (name, PRC, PTR, TIN, address, date_signed) |
| SEW fields | `sew_*` (name, PRC, PTR, TIN, address, date_signed) |
| `state` | 1=New, 2=AssessedZoning, 3=AssessedEAS, 4=AssessedBFP, 5=Paid, 0=Cancelled |
| `entered_by` | FK → users |
| `user_online_id` | FK → users_online |

### `application_occupancy_permits`

| Column | Description |
|--------|-------------|
| `id` | PK |
| `year`, `month`, `counter` | Application numbering |
| `application_type_id` | FK → application_types |
| `applies_for` | FS, LC |
| `bp_no`, `bp_issued_date` | Building Permit reference |
| `fsec_no`, `fsec_issued_date` | FSEC reference |
| `fsic_counter`, `fsic_date` | FSIC tracking |
| `full_name`, `tin_no`, `contact_no` | Applicant (encrypted) |
| `province_id`, `city_id`, `barangay_id` | Location |
| `no_of_storey`, `no_of_units`, `total_floor_area` | Building info |
| `completion_date` | Date of completion |
| `state` | 1=New, 3=Assessed, 5=Paid, 0=Cancelled |
| `entered_by`, `user_online_id` | Processing |

### `application_building_permits_groups` / `application_occupancy_permits_groups`

| Column | Description |
|--------|-------------|
| `id` | PK |
| `application_bp_id` / `application_op_id` | FK to parent application |
| `group_id` | FK → groups |
| `sub_group_id` | FK → sub_groups |
| `sub_group_others` | Free text for "Others" sub-groups |

---

## Assessment Tables (BP)

### `bp_assessment_fees` (Master)

| Column | Description |
|--------|-------------|
| `id` | PK |
| `application_bp_id` | FK → application_building_permits |
| `filing_fee`, `processing_fee` | Standard fees |
| `locational_zoning_fee` | Zoning assessment |
| `electrical_inspection_fee` | Electrical inspection |
| `mechanical_inspection_fee` | Mechanical inspection |

### BP Assessment Detail Tables

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `bp_assessment_construction_fees` | Construction fees by division | division_id, floor_area, fee |
| `bp_assessment_electrical_fees` | Electrical fee items | description, quantity, fee |
| `bp_assessment_mechanical_fees` | Mechanical fee items | description, quantity, fee |
| `bp_assessment_plumbing_fees` | Plumbing fee items | description, quantity, fee |
| `bp_assessment_electronics` | Electronics fee items | description, fee |
| `bp_assessment_accessory_building_fees` | Building accessory fees | type, quantity, fee |
| `bp_assessment_accessory_fees` | Add-on accessory fees | type, quantity, fee |
| `bp_assessment_zoning_fees` | Zoning fees | classification, fee |
| `bp_assessment_surcharge_fees` | Surcharge fees | base_amount, rate, fee |
| `bp_assessment_fsec` | FSEC fee items | code, description, fee |

---

## Assessment Tables (OP)

| Table | Purpose |
|-------|---------|
| `occ_assessment_fees` | Master occupancy assessment |
| `occ_assessment_fsics` | FSIC fee items |

---

## Fee Schedule Tables (~100+)

### Construction Fees
| Table | Records | Purpose |
|-------|---------|---------|
| `construction_fees` | 228 | Range-based by division (24 divisions) |

### Electrical Fees
| Table | Purpose |
|-------|---------|
| `elec_total_connected_load_fees` | kW range fees |
| `elec_transformer_ups_generator_fees` | Capacity fees |
| `elec_pole_attachment_location_fees` | Location fees |
| `elec_miscellaneous_fees` | Meter + wiring permit |

### Mechanical Fees
| Table | Purpose |
|-------|---------|
| `mech_refrigeration_aircon_vent_fees` | AC/ventilation |
| `mech_escalator_moving_walks` | Escalator fees |
| `mech_elevator_fees` | Elevator fees |
| `mech_boilers_fees` | Boiler capacity fees |
| `mech_diesel_gasoline_range_fees` | Engine fees |
| `mech_other_internal_combustion_fees` | Other IC engine fees |

### Plumbing Fees
| Table | Purpose |
|-------|---------|
| `plum_installation_fees` | Base installation |
| `plum_special_plumbing_fixtures_fees` | Special fixtures |
| `plum_every_fixtures_fees` | Per fixture |
| `plum_water_meter_range_fees` | Water meter |
| `plum_septic_tank_range_fees` | Septic tank |

### Electronics Fees
| Table | Purpose |
|-------|---------|
| `electronics_fees` | Base electronics |

### Accessory Building Fees
| Table | Purpose |
|-------|---------|
| `acc_all_parts_of_bldg_fees` | Open parts (50% rate) |
| `acc_bldgs_with_heights` | Height surcharge (>8m) |
| `acc_bank_records_vaults_range_fees` | Vault volume |
| `acc_swimming_pools_fees` | Pool fees by group |
| `acc_construction_erection_towers_fees` | Tower fees |
| `acc_storage_silos_range_fees` | Silo height |
| `acc_smoke_stacks_chimneys_range_fees` | Chimney height |
| `acc_reinforced_concrete_steel_tanks_range_fees` | Tank fees |
| `acc_tank_above_ground_range_fees` | Above ground tanks |
| `acc_pullouts_reinstallation_steel_tanks_fees` | Pull-out fees |
| `acc_booth_kiosks_platforms_stages_fees` | Booth/stage |
| `acc_firewalls_fees` | Firewall fees |
| `acc_water_waste_water_treatment_fees` | Treatment tank |

### Accessory Fees (Add-ons)
| Table | Purpose |
|-------|---------|
| `ass_line_grade_range_fees` | Line & grade |
| `ass_ground_prep_ex_fees` | Ground prep |
| `ass_fencing_fees` / `ass_fencing_range_fees` | Fencing by type |
| `ass_construction_of_pavement_range_fees` | Pavement |
| `ass_streets_and_sidewalks_range_fees` | Street use |
| `ass_erection_of_scaffoldings` | Scaffolding |
| `ass_sign_installation_fees` / `ass_sign_range_fees` | Signage |
| `ass_repairs_fees` | Repairs |
| `ass_demolition_fees` / `ass_demolition_structures_range_fees` | Demolition |

### Occupancy Division Fees
| Table | Purpose |
|-------|---------|
| `occ_division_a_buildings_range_fees` | Division A |
| `occ_division_b_buildings_range_fees` | Division B |
| `occ_division_c_d_buildings_range_fees` | Division C/D |
| `occ_division_j_i_buildings_range_fees` | Division J-I |
| `occ_division_j_ii_e_i_rate_fees` | Division J-II E-I (flat) |
| `occ_division_j_ii_e_ii_range_fees` | Division J-II E-II |
| `occ_division_j_ii_e_iii_range_fees` | Division J-II E-III |
| `occ_j_two_structures` | Special J-2 structures |
| `occ_change_in_use_occupancy_fees` | Change of use |

### Zoning Fees
| Table | Purpose |
|-------|---------|
| `land_use_and_zoning_fees` | By classification/zone |
| `certification_zoning_fees` | Certification fees |

### BFP Fees
| Table | Purpose |
|-------|---------|
| `bfp_order_of_payments` | BFP payment structure |
| `bfp_other_fees` | Miscellaneous BFP |
| `bfp_other_fees_elec_installation_range_fees` | Electrical install |
| `bfp_other_fees_welding_range_fees` | Welding fees |
| `bfp_fire_code_revenues` | BFP revenue tracking |
| `bfp_sources_of_incomes` | Downpayment percentage |

### Surcharges
| Table | Purpose |
|-------|---------|
| `surcharges` | Surcharge rates |

### Annual Inspection
`ann_inspection_a_fees` through `ann_inspection_fxx_fees` — comprehensive annual inspection fee matrices.

---

## Collection Tables

### `collections`

| Column | Description |
|--------|-------------|
| `id` | PK |
| `type` | 1=Building Permit, 2=Occupancy Permit |
| `application_bp_id` | FK → application_building_permits (nullable) |
| `application_op_id` | FK → application_occupancy_permits (nullable) |
| `or_number` | Official Receipt number (UNIQUE) |
| `paid_by` | Payer name |
| `collector_id` | FK → users |
| `amount_to_pay`, `amount_received`, `total_change` | Amounts |
| `mode_of_payment` | cash, check, online |
| `bank_name`, `check_number`, `check_date` | Check details |
| `bfp_payment_option` | full or down-payment |
| `bfp_balance` | Remaining balance |
| `state` | 1=Active, 0=Voided |

### `collection_details`

| Column | Description |
|--------|-------------|
| `id` | PK |
| `collection_id` | FK → collections |
| `detail` | Fee description |
| `amount` | Collected amount |
| `state` | Status tracking |

---

## Reference Tables

| Table | Key Fields | Purpose |
|-------|-----------|---------|
| `groups` | code (A-J), name | Building occupancy groups |
| `sub_groups` | group_id, name | Detailed classifications |
| `divisions` | group_id, code, assessment_mode | Fee calculation divisions |
| `scope_of_works` | name | Construction scope options |
| `form_of_ownerships` | name | Business ownership types |
| `occupancies` | name | 1=Residential, 2=Commercial, 3=Social |
| `application_types` | name | New, Renewal, Amendatory, Simple, Complex |
| `application_complexities` | name | Simple, Complex |
| `land_classifications` | name | Land use types |
| `building_parts` | name | Structural elements |
| `signatories` | role, name, license | Professional signatories |
| `provinces` | psgc_code, name | Philippine provinces |
| `cities` | province_id, name | Cities/municipalities |
| `barangays` | city_id, name | Barangays |
| `csf_barangays` | captain_name | City-specific barangay info |
| `users` | access_level (1-5) | System users |
| `access_levels` | name | Admin, EAS, BFP, CTO, PDO |

---

## Key Schema Differences from Engineering-App

| Aspect | BOPMS | Engineering-App |
|--------|-------|-----------------|
| BP Applications | `application_building_permits` | `applications` (shared) |
| OP Applications | `application_occupancy_permits` | `applications` (shared) |
| BP Groups | `application_building_permits_groups` | `application_occupancy_groups` |
| OP Groups | `application_occupancy_permits_groups` | `application_occupancy_groups` |
| Fee Tables | 100+ individual tables | 3 tables (categories/types/schedules) |
| Assessment | Per-discipline tables (10+) | 2 tables (assessments/assessment_items) |
| Collections | Separate BP/OP FKs | Single application_id FK |
| Permits | Separate BP/OP tables | Single permits table |
| Status | Integer (1-5) | String enum |
| Soft Deletes | No | Yes |
| Migrations | No (raw SQL) | Yes (Laravel migrations) |
