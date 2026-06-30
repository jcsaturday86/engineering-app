# Tasks

---

## Completed Tasks

### Separate BP and OP into Different Database Tables — COMPLETED

- `applications` = BP only; new `occupancy_applications` = OP only
- 7 downstream tables use polymorphic `applicationable_type`/`applicationable_id` (morph map: bp/op)
- New `OccupancyApplication` model + `OccupancyApplicationService` + `OccupancyApplicationDTO`
- New `OccupancyApplicationController` + `/occupancy-applications/*` routes + views
- Parallel `*Op()` methods in Assessment/Billing/Collection/Permit controllers
- 4 notification classes accept `Model` instead of `Application`
- `ApplicationStatus::allowedTransitionsFor(string $permitTypeCode)` for OP flow

### Zoning Assessment Fee Auto-Compute & Settings — COMPLETED

- New `land_use_and_zoning_fees` table (162 rows, 52 sub-groups, 6 fee patterns) + `certification_zoning_fees` (P500)
- `ZoningController::autoCompute()` matches BOPMS `zoningAutoCompute()` logic
- New `ZoningFeeController` + `/settings/zoning-fees` accordion settings page
- `land_use_and_zoning_other_fees` table (Variance/Non-Conforming) + settings UI
- New `for_zoning_assessment` status; `submitted` = skip-LC path
- Browser autofill disabled on all forms; `ApplicationSeeder` with 5 BP + 5 OP test records

### Zoning Assessment UX Improvements — COMPLETED

- 4 fee type selector (LC, LC Manual, Certification, Others) matching BOPMS
- Checkbox select-all / bulk delete via fetch API
- Password confirmation modal on finalize (Hash::check())

### BP Assessment Tabbed Navigation & BOPMS-Style Forms — COMPLETED

- 8 fee category tabs + Summary tab with item count badges
- **Construction tab:** Part of Building + Division + Area → server-side fee lookup. `amount = area × fee_per_unit`
- **Electrical tab:** 7 fee types, conditional fields, range kVA: `base = fixed_fee + (kva × fee_per_unit)`. Inspection fee = `base × electrical_inspection_percentage` (setting, 10%). `amount` = base; `inspection_fee` stored separately
- Split `ELEC_TUG` → `ELEC_TRANS` + `ELEC_UPS` matching BOPMS
- New routes: `POST /assessments/{id}/construction-item`, `POST /assessments/{id}/electrical-item`

### Mechanical Fee Assessment with NBC Inspection Fees — COMPLETED

- **MECH_INSP fee category:** 29 `INSP_*` fee types with 55 schedule rows; NBC rates sourced from BOPMS `ann_inspection_f*` SQL tables (I through XIX). Category hidden from assessment tab bar.
- **Mechanical tab (BOPMS-style):** `addMechanicalItem()` computes base permit fee (MECH schedules) + NBC inspection fee (`resolveInspectionFee()` maps `MECH_REFRIG` → `INSP_REFRIG`). `amount` = base only; `inspection_fee` stored separately. Consistent grand total: `sum(amount) + sum(inspection_fee)`
- Three inspection fee formulas: `flat` (range-band fixed_fee ± excess), `per_unit` (rate × unit ± excess), `tiered` (cumulative for elevators: first N floors × rate + excess × rate2)
- Route: `POST /assessments/{id}/mechanical-item`
- `MECH_INSP` added to `$excludedTabs` so it never appears as a manual-entry tab

---

## Upcoming Tasks

| Task | Priority | Notes |
|------|----------|-------|
| Plumbing tab (BOPMS-style) | High | Next: BOPMS-style form for PLUMB fee types, auto-computed inspection fees from PLUMB_INSP or plumbing-specific NBC rates |
| Electronics / Accessories tabs | Medium | Currently generic form; may need BOPMS-style upgrades |
| Additional permit types (FP, EP, DP, etc.) | Medium | Currently only BP and OP are active |
| Document requirement upload UI | Low | Model/route exists, UI needs improvement |
| Email notification configuration | Low | SMTP settings, notification templates |
| Annual inspection module (non-mech) | Future | Not in current requirements |
