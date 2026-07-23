# Codebase Reference

---

## Directory Structure

```
engineering-app/
├── app/
│   ├── Actions/           (4 action classes)
│   ├── Concerns/          (1 trait: HasPermitApplicationBehavior)
│   ├── Contracts/         (1 interface: PermitApplicationContract)
│   ├── DTOs/              (4 data transfer objects)
│   ├── Enums/             (5 enums)
│   ├── Exports/           (3 Excel export classes)
│   ├── Http/Controllers/  (18 + Auth controllers)
│   ├── Models/            (34 Eloquent models)
│   ├── Notifications/     (4 notification classes)
│   ├── Providers/         (AppServiceProvider, SelfHealingServiceProvider)
│   └── Services/          (8 service classes)
├── database/
│   ├── migrations/        (24+ migration files)
│   ├── seeders/           (9 seeders)
│   └── factories/
├── resources/views/       (40+ Blade templates)
├── routes/web.php         (140+ routes)
├── docs/                  (this documentation)
└── CLAUDE.md
```

---

## Models (34)

### Core Transaction Models

| Model | Table | Notes |
|-------|-------|-------|
| Application | applications | BP only. Implements PermitApplicationContract + HasPermitApplicationBehavior. getPermitTypeCode() = 'BP' |
| OccupancyApplication | occupancy_applications | OP only. Same contract/trait. getPermitTypeCode() = 'OP' |
| DemolitionApplication | demolition_applications | DP only. Same contract/trait. getPermitTypeCode() = 'DP'. Overrides `buildingBarangay()` → `demolition_barangay_id` (aliased as `demolitionBarangay()`) |
| SignageApplication | signage_applications | SGP only. Same contract/trait. getPermitTypeCode() = 'SGP'. Overrides `buildingBarangay()` → aliases `applicantBarangay()` (no separate site-location column) |
| FencingApplication | fencing_applications | FP only. Same contract/trait. getPermitTypeCode() = 'FP'. Overrides `buildingBarangay()` → `construction_barangay_id` (aliased as `constructionBarangay()`) — the generic trait default targets `building_barangay_id`, a column this table doesn't have. Inspector data lives as 8 flat `inspector_*` columns on this table (a repeatable `FencingInspector` child-table/model was built then deleted this session in favor of this fixed-block shape, matching Design Professional) |
| AnnualInspectionApplication | annual_inspection_applications | AI only. Same contract/trait. getPermitTypeCode() = 'AI'. Renamed from `MechanicalApplication` (table/model/every reference) once the module was rebuilt around the official Annual Inspection Fees schedule. Field set: `owner_name`, `location_street`/`location_barangay_id`, `application_kind` (new/yearly), optional `occupancy_no`/`occupancy_issued_date` (feed the GE certificate's Certificate-of-Occupancy line). Character of Occupancy uses the trait's generic `applicationOccupancyGroups(): MorphMany` (single-select — the app never writes more than one row for AI). `annualInspectionPermitUnits(): HasMany` (reactivated — see below) and `equipmentItems(): HasMany` (the declared equipment checklist, ordered by `sort_order`) |
| AnnualInspectionPermitUnit | annual_inspection_permit_units | `belongsTo(AnnualInspectionApplication)`, `belongsTo(Permit)`, `belongsTo(AssessmentItem)` (via `assessment_item_id`, nullable). Built for the module's original multi-permit-generation design, went dormant during a single-permit interim, then **reactivated**: one row per generated certificate (`group_code` = `GE`/`ELN`/`MACH`/`ACREF`/`ELEV`/`ESC`), linking each `Permit` back to its slice of assessment data |
| AnnualInspectionEquipmentItem | annual_inspection_equipment_items | `belongsTo(AnnualInspectionApplication)`. The declared "Equipment / Items to be Inspected" checklist captured on the application form — `fee_code`/`quantity`/`specification`/`sort_order`. Carries a public `CATEGORIES` const (5 groups of equipment-count fee codes) and `labelFor()`/`allCodes()` static helpers, reused by the form dropdown, show page, and assessment reference panel |
| ApplicationOccupancyGroup | application_occupancy_groups | morphTo: applicationable |
| ApplicationRequirement | application_requirements | morphTo: applicationable |
| Assessment | assessments | morphTo: applicationable. hasMany: assessmentItems. SoftDeletes, LogsActivity |
| AssessmentItem | assessment_items | belongsTo: assessment, feeCategory, feeType. SoftDeletes |
| ZoningAssessment | zoning_assessments | belongsTo: application (1:1, BP only). SoftDeletes |
| Billing | billings | morphTo: applicationable. hasMany: billingItems. SoftDeletes |
| BillingItem | billing_items | belongsTo: billing |
| Collection | collections | morphTo: applicationable. hasMany: collectionDetails. hasOne: voidTransaction. SoftDeletes, LogsActivity |
| CollectionDetail | collection_details | belongsTo: collection |
| VoidTransaction | void_transactions | belongsTo: collection |
| Permit | permits | morphTo: applicationable. SoftDeletes, LogsActivity. `verification_token` (UUID) set on generation, used for the public QR-code verification link. `status` includes `revoked`; `revoke_reason` and `building_official_*` snapshot columns added for revocation/audit. `signatories_snapshot` (JSON, cast `array`) — AI-only, populated in `doGenerateAi()`: a one-time `{role: {title, name}}` snapshot of the 14 discipline/Chief Signatory rows, same locking principle as `building_official_*` |
| Document | documents | morphTo: applicationable |

### Reference/Lookup Models

| Model | Table |
|-------|-------|
| PermitType | permit_types |
| ApplicationType | application_types |
| ScopeOfWork | scope_of_works |
| FormOfOwnership | form_of_ownerships |
| OccupancyGroup | occupancy_groups |
| OccupancySubGroup | occupancy_sub_groups |
| OccupancyDivision | occupancy_divisions |
| BuildingPart | building_parts |
| Signatory | signatories | 3 original roles (`building_official`, `planning_officer`, `treasury_officer`) plus 15 `ai_*` roles seeded for the Annual Inspection "General, Occupancy & Electrical" certificate's discipline/Chief signature blocks. Settings > Signatories UI is edit-only — no create/delete route (a Create/Delete pair was built then reverted at the user's request in the same session it was added) |
| LandClassification | land_classifications |

### Fee Schedule Models

| Model | Table | Notes |
|-------|-------|-------|
| FeeCategory | fee_categories | hasMany: feeTypes |
| FeeType | fee_types | hasMany: feeSchedules |
| FeeSchedule | fee_schedules | standard rate rows |
| LandUseAndZoningFee | land_use_and_zoning_fees | 162 rows, locational clearance rates |
| CertificationZoningFee | certification_zoning_fees | P500 flat cert fee |
| LandUseAndZoningOtherFee | land_use_and_zoning_other_fees | Variance/Non-Conforming |

### Auth/System Models
User (HasRoles, LogsActivity, SoftDeletes), Province, City, Barangay.

---

## Controllers

### ApplicationController (BP only)
index, create, store, show, edit, update, submit, cancel, revertSubmission, printForm, printDiscipline

`index()` accepts `search`, `status`, `year` (defaults to current year) query params and eager-loads `permits` for the Turn Around Time column.

`printForm()` streams a real DomPDF (`defaultMediaType=print`, `dpi=200`) instead of returning browser-print HTML. `printDiscipline(Application, string $discipline)` (`GET /applications/{id}/print-discipline/{discipline}`) validates against the `DISCIPLINE_FORMS` const (architectural/structural/electrical/sanitary/mechanical/electronics); `architectural`, `structural`, `electrical`, and `sanitary` dispatch to `printArchitecturalForm()` / `printStructuralForm()` / `printElectricalForm()` / `printSanitaryForm()` (real NBC/permit-form PDFs, background-image overlay, own scans), the rest render a shared blank placeholder (`pdf/discipline-form.blade.php`). The first three dedicated print methods share a private `resolveBuildingOfficial(Application): array` helper — returns `[title, name, designation]` from the first generated `Permit`'s snapshot, or the active `building_official` Signatory if no Permit exists yet (`printSanitaryForm()` doesn't call it — that form has no "Permit Issued By" signatory section).

### OccupancyApplicationController (OP only)
index, create, store, show, edit, update, submit, cancel, revertSubmission, printForm

Same `search`/`status`/`year` filtering and `permits` eager-load as `ApplicationController::index()`.

### DemolitionApplicationController (DP only)
index, create, store, show, edit, update, submit, cancel, revertSubmission, printForm

Same shape as `OccupancyApplicationController`, with DP-specific fields (enterprise, address CTC, Location of Demolition Works, Scope of Work, Full-time Inspector/Supervisor, Lot Owner Consent). `printForm()` streams the background-image-overlay NBC Form B-08 PDF, resolving the Building Official via a private `resolveBuildingOfficial(DemolitionApplication): array` helper (same shape as `ApplicationController`'s).

### SignageApplicationController (SGP only)
index, create, store, show, edit, update, submit, cancel, revertSubmission

Same shape again, trimmed further (no enterprise/CTC/inspector/lot-owner sections, no occupancy-group selection). **No `printForm()`** — the application-form print is deferred pending a scanned official form.

### FencingApplicationController (FP only)
index, report, create, store, show, edit, update, submit, revertSubmission, cancel, printForm

Same shape as `DemolitionApplicationController`, with FP-specific fields (enterprise, address plus an `applicant_ctc_*` triplet, Location of Construction, Scope of Work, Design Professional, Full-Time Inspector or Supervisor, Consent of Lot Owner). `validateApplication()` marks every field `required` except `owned_by_enterprise` (optional checkbox) and its two dependents `enterprise_name`/`form_of_ownership_id` (`required_if:owned_by_enterprise,1`), plus `scope_of_work_detail` (`required_if:scope_of_work,repair,others`). Adds a dedicated `report()` method (`GET /fencing-applications/report`) streaming a landscape DomPDF via the shared `pdf/report.blade.php` template — no equivalent exists on DP/SGP's controllers. `printForm()` (route `fencing-applications.print`) mirrors `DemolitionApplicationController::printForm()`'s background-image-overlay approach, rendering `pdf/fencing-application-form.blade.php` over `public/images/forms/fencing-p1.jpg`/`fencing-p2.jpg` (JPEG, not PNG — see the PDF Print Performance note in `docs/TASK.md`); this was added after FP's initial build, so unlike DP/SGP the application-form print is not deferred. The final permit certificate print (`printFp()` on `PermitController`) is a separate, plain-HTML/CSS template.

### AnnualInspectionApplicationController (AI only)
index, create, store, show, edit, update, submit, revertSubmission, cancel

Same shape as `FencingApplicationController` (minus its `report()`/`printForm()` extras) — trimmed to AI's minimal field set (owner name, location, `application_kind` new/yearly toggle, editable only while `draft`). No occupancy-group selection, no enterprise/CTC/inspector/lot-owner sections. Renamed from `MechanicalApplicationController` in the same rename pass as the model/table.

### GeoController
`barangaysForCity(City $city)` — `GET /geo/barangays/{city}`, returns active barangays for a city as JSON (`id`, `name`). Used by the BP/OP application form's cascading address dropdowns instead of shipping the full ~42K-row barangay dataset to the page.

### ZoningController
index, assess, store, autoCompute, addItem, removeItems (bulk), removeItem, finalize, revertZoning, sendBackForEditing, skip

**Private helpers:** `zoningAssessmentIsFinalized()` / `abortIfZoningFinalized()` — store, autoCompute, addItem, and remove methods abort 403 once the zoning assessment is finalized.

### ZoningFeeController (Settings)
index, update, store, updateCert, updateOther, destroy

### AssessmentController

| Method | Route | Purpose |
|--------|-------|---------|
| index | GET /assessments | BP assessment list (submitted/zoning_assessed/engineering_assessed/billed) |
| occupancyIndex | GET /assessments/occupancy | OP list (same statuses) |
| demolitionIndex | GET /assessments/demolition | DP list (submitted/engineering_assessed/billed) |
| signageIndex | GET /assessments/signage | SGP list (submitted/engineering_assessed/billed) |
| fencingIndex | GET /assessments/fencing | FP list (submitted/engineering_assessed/billed) |
| assess | GET /assessments/{id} | Tabbed fee entry (Construction/Electrical/Mechanical/…) |
| addConstructionItem | POST /assessments/{id}/construction-item | BOPMS-style: Part+Division+Area → auto fee lookup |
| addElectricalItem | POST /assessments/{id}/electrical-item | BOPMS-style: 7 types, auto inspection % |
| addMechanicalItem | POST /assessments/{id}/mechanical-item | BOPMS-style: equipment type+unit → base fee + NBC inspection fee |
| addPlumbingItem | POST /assessments/{id}/plumbing-item | BOPMS-style: 22 PLUMB_* types, per_unit + range_based |
| addElectronicsItem | POST /assessments/{id}/electronics-item | BOPMS-style: 11 ELECT_* types |
| addAccessoryItem | POST /assessments/{id}/accessory-item | ACC_BLDG tab |
| addAccFeeItem | POST /assessments/{id}/acc-fee-item | ACC_FEE tab |
| addSurchargeItem | POST /assessments/{id}/surcharge-item | SURCHARGE tab |
| addItem | POST /assessments/{id}/item | Generic item for other tabs |
| removeItem | DELETE /assessments/item/{id} | Remove item (guarded when finalized) |
| finalize | POST /assessments/{id}/finalize | BP → engineering_assessed → billed (auto); redirects to ?tab=SUMMARY |
| revertEngineering | POST /assessments/{id}/revert-finalize | Un-finalize a BP engineering assessment |
| returnToZoning | POST /assessments/{id}/return-to-zoning | Delete BP engineering assessment items, send application back to Planning |
| summary | GET /assessments/{id}/summary | BP summary view |
| print | GET /assessments/{id}/print | BP PDF summary (barcode + building_official signatory) |
| assessOp | GET /assessments/op/{op} | OP fee entry |
| addItemOp | POST /assessments/op/{op}/item | OP generic item |
| addOccupancyFeeItem | POST /assessments/op/{op}/occupancy-fee | BOPMS-style: 8 OCC_* types; range_based (excess_every), per_unit, percentage |
| finalizeOp | POST /assessments/op/{op}/finalize | OP → engineering_assessed → billed (auto); redirects to ?tab=SUMMARY |
| revertEngineeringOp | POST /assessments/op/{op}/revert-finalize | Un-finalize an OP engineering assessment |
| revertToDraftOp | POST /assessments/op/{op}/revert-to-draft | OP only; while `status = zoning_assessed`, deletes all occupancy fee entries + the Assessment, reverts application to `draft` |
| summaryOp | GET /assessments/op/{op}/summary | OP summary |
| printOp | GET /assessments/op/{op}/print | OP PDF (separate `assessment-summary-op` template) |
| assessDp | GET /assessments/dp/{dp} | DP fee entry (DEMO_FEE tab) |
| addItemDp | POST /assessments/dp/{dp}/item | DP generic item (legacy fallback, superseded by addDemolitionItem) |
| addDemolitionItem | POST /assessments/dp/{dp}/demolition-item | Fee-schedule-driven: DEMO_FEE type + quantity → server-computed `amount = quantity × rate` |
| finalizeDp | POST /assessments/dp/{dp}/finalize | DP → engineering_assessed → billed (auto) |
| revertEngineeringDp | POST /assessments/dp/{dp}/revert-finalize | Un-finalize a DP engineering assessment |
| revertToDraftDp | POST /assessments/dp/{dp}/revert-to-draft | DP only; while `status = submitted`, deletes fee entries + Assessment, reverts to `draft` |
| summaryDp | GET /assessments/dp/{dp}/summary | DP summary |
| printDp | GET /assessments/dp/{dp}/print | DP PDF (`assessment-summary-dp` template) |
| assessSgp | GET /assessments/sgp/{sgp} | SGP fee entry (SGP_FEE tab, generic manual-entry form) |
| addItemSgp | POST /assessments/sgp/{sgp}/item | SGP generic item (manual quantity + unit fee — no dedicated fee-schedule method exists yet) |
| finalizeSgp | POST /assessments/sgp/{sgp}/finalize | SGP → engineering_assessed → billed (auto) |
| revertEngineeringSgp | POST /assessments/sgp/{sgp}/revert-finalize | Un-finalize an SGP engineering assessment |
| revertToDraftSgp | POST /assessments/sgp/{sgp}/revert-to-draft | SGP only; while `status = submitted`, deletes fee entries + Assessment, reverts to `draft` |
| summarySgp | GET /assessments/sgp/{sgp}/summary | SGP summary |
| printSgp | GET /assessments/sgp/{sgp}/print | SGP PDF (`assessment-summary-sgp` template) |
| assessFp | GET /assessments/fp/{fp} | FP fee entry (FP_FEE tab, `addFenceItem()`) |
| finalizeFp / revertEngineeringFp / revertToDraftFp | POST /assessments/fp/{fp}/... | FP finalize/revert lifecycle |
| summaryFp / printFp | GET /assessments/fp/{fp}/summary\|print | FP summary/PDF (`assessment-summary-fp` template) |
| assessAi | GET /assessments/ai/{ai} | AI fee entry — 4 tabs (AINSP_GEN/ELECTRONICS/MECH via `addAnnualInspectionFeeItem()`, AINSP_ELEC via `addAnnualInspectionElectricalItem()`) |
| addInspItem | POST /assessments/ai/{ai}/insp-item | `addAnnualInspectionFeeItem()` — General/Electronics/Mechanical tabs, plus the Quantity (equipment-count) multiplier for 15 measured-value Mechanical codes |
| addElecItem | POST /assessments/ai/{ai}/elec-item | `addAnnualInspectionElectricalItem()` — Electrical tab, reuses BP `ELEC_*` schedules, plus the Quantity multiplier for TCL/Trans/UPS |
| finalizeAi / revertEngineeringAi / revertToDraftAi | POST /assessments/ai/{ai}/... | AI finalize/revert lifecycle |
| summaryAi / printAi | GET /assessments/ai/{ai}/summary\|print | AI summary/PDF (`assessment-summary-ai` template, sections grouped by the 4 real `AINSP_*` categories) |

**Private helpers:**
- `resolveInspectionFee(string $code, float $unit): array` — maps MECH_* code → INSP_* fee type (MECH_INSP category), does range or first-row lookup, returns {fee, excess_threshold, excess_fee, every, method}. Three methods: flat (range-band fixed), per_unit (rate × unit), tiered (cumulative for elevators). **No longer called by `addElectricalItem()`/`addMechanicalItem()`** (BP inspection fees removed — both now hardcode `inspection_fee = 0`); still used by the Annual Inspection assessment's own fee computation.
- `calculateTotals(Assessment $assessment): array` — returns subtotal, inspection, filing, processing, total.
- `redirectIfFinalized(Assessment, PermitApplicationContract): ?RedirectResponse` — called by every add/remove method; when assessment status = finalized, redirects to the assess page `?tab=SUMMARY` with an error flash.
- `doPrint(PermitApplicationContract)` — dispatches by `getPermitTypeCode()`: BP renders `pdf.assessment-summary` (building + zoning sections); OP/DP/SGP each delegate to their own `doPrintOp()`/`doPrintDp()`/`doPrintSgp()`, rendering `pdf.assessment-summary-op`/`-dp`/`-sgp` with only that permit type's single fee-category section (no Zoning/Building/Other Fees/Filing/Processing). All four generate a Code 128 barcode (picqer BarcodeGeneratorPNG, base64) and load the `building_official` signatory.

### BillingController
print only. Billing is auto-generated on assessment finalize via `BillingService::generateFor(PermitApplicationContract)` (guards: status must be `engineering_assessed`, no existing unpaid billing). The Billing menu/index page and manual generate routes were removed.

### CollectionController
- `index(Request $request)` — accepts `search`: exact match on a billed BP/OP/DP/SGP `application_number` redirects straight to that payment form (barcode-scan UX); partial match filters the Awaiting Payment list by application number or applicant name. Also accepts `month` (`YYYY-MM`, defaults to current month) — the Payment History list is scoped to the logged-in collector's own transactions (`collected_by = Auth::id()`) within that month
- `create`/`store` (BP), `createOp`/`storeOp` (OP), `createDp`/`storeDp` (DP), `createSgp`/`storeSgp` (SGP) — `doStore()` rejects an insufficient cash payment (`amount_received < billing->total_amount` when `payment_mode = cash`) before recording. Its internal `$morphType = match($application->getPermitTypeCode()) {...}` must carry all 4 arms (`OP`/`DP`/`SGP` + `default => 'bp'`) or a payment silently records against the wrong polymorphic type
- `receipt` (renders the Official Receipt PDF with the dynamic city seal), `voidForm`, `processVoid`

### PermitController
buildingIndex, occupancyIndex, demolitionIndex, signageIndex, fencingIndex, annualInspectionIndex, generate (BP), revertGenerate (BP), restoreRevoke (BP), generateOp (OP), revertGenerateOp (OP), restoreRevokeOp (OP), generateDp (DP), revertGenerateDp (DP), restoreRevokeDp (DP), generateSgp (SGP), revertGenerateSgp (SGP), restoreRevokeSgp (SGP), generateFp (FP), revertGenerateFp (FP), restoreRevokeFp (FP), generateAi (AI), revertGenerateAi (AI), restoreRevokeAi (AI), print, zoningCertification, locationalClearance, evaluationReport

`generateAi`/`revertGenerateAi`/`restoreRevokeAi` are AI-specific multi-certificate methods (not thin wrappers around the shared single-permit methods — that was an interim state after the original 5-equipment-category multi-permit builder was deleted, see `docs/PROJECT_CONTEXT.md`). A private `buildAiCertificateGroups(AnnualInspectionApplication): array` derives up to 6 certificate groups from the application's finalized assessment items (General+Electrical bundle, Electronics bundle, Machinery bundle, Aircon/Refrigeration bundle, one entry per Elevator item, one entry per Escalator/Funicular/Cable-Car item — skipping any group with zero items). `doGenerateAi()` loops this list once per generation, creating one `Permit` + one `AnnualInspectionPermitUnit` row per group (incrementing a single shared permit-number counter so every certificate in one action gets a unique number). `revertGenerateAi()`/`restoreRevokeAi()` act on **all** of an application's permits at once (loop revoke/soft-delete, or loop restore), unlike the shared single-permit `doRevertGenerate()`/`doRestoreRevoke()` which only ever handle one.

`buildingIndex`/`occupancyIndex`/`demolitionIndex`/`signageIndex` accept `search`, `status` (including a `revoked` pseudo-status matched via `whereHas('permits', fn ($q) => $q->withTrashed()->where('status', 'revoked'))`), and `year` (defaults to current year) query params; all four share the same `permits/index.blade.php` view keyed by `$type`.

`revertGenerate`/`revertGenerateOp` (`revert-permits` permission) tag the `Permit` `status = 'revoked'` (with a required `revoke_reason`) and soft-delete it, rolling the application status back to `paid`. `doGenerate()` refuses to create a new permit while a revoked permit exists for the application (`onlyTrashed()->where('status', 'revoked')->exists()`). `restoreRevoke`/`restoreRevokeOp` (same permission, password-confirm only) reverse this: `$permit->restore()`, `status` back to `generated`, application back to `permit_generated`.

`generate`/`generateOp`/`generateDp`/`generateSgp` (via `doGenerate()`) set a `verification_token` (UUID) on the new `Permit` row, and snapshot the currently-active `building_official` Signatory onto `building_official_name`/`_title`/`_designation`/`_license_no` — a one-time capture that survives Signatory edits, revoke, and restore. `print()` additionally builds a QR code (`endroid/qr-code`) encoding the public verification URL (`{general.domain setting|app.url}/verify/permit/{token}`) and passes it (plus `sealImage`, `dpwhLogo` — both sourced from `Setting`, each falling back to a static default) to the template selected by `match ($permit->permitType->code) { 'OP' => 'pdf.occupancy-permit', 'DP' => 'pdf.demolition-permit', 'SGP' => 'pdf.signage-permit', 'AI' => 'pdf.annual-inspection-permit', default => 'pdf.building-permit' }`, which reads the Building Official line from the permit's own snapshot columns, not the live Signatory. For AI specifically, `print()` looks up the `AnnualInspectionPermitUnit` tied to the `$permit->id` and passes it (plus, for bundle-type certificates, a re-derived itemized item list scoped to that unit's `group_code`) instead of the generic BP/OP/DP/SGP variable set; if `$aiUnit->group_code === 'GE'`, the template match is overridden to `pdf.annual-inspection-permit-ge` instead (the NBC Form B-19 background-overlay certificate — see `docs/PROJECT_CONTEXT.md`), and `$pdf->setOption('dpi', 200)` is set only for that branch (every other template in this method sets no DPI option at all, so this was scoped narrowly rather than changed globally). `doGenerateAi()` additionally snapshots the 14 `ai_*` discipline/Chief Signatory rows into `signatories_snapshot` on each generated `Permit`, alongside the existing `building_official_*` snapshot fields — same locking principle, scoped to `doGenerateAi()` only since no other permit type's template reads these roles.

### DemolitionFeeController (Settings, DP only)
index, updateSchedule, storeSchedule, destroySchedule, updateUnitLabel — `/settings/demolition-fees`, the only dedicated fee-schedule settings page among the 4 permit types (BP has ~10 per-category settings pages, SGP has none yet — its `SGP_FEE` category is editable only via the generic `/settings/fees`). `updateUnitLabel()` is the newest method, editing a `FeeType`'s `unit_label` inline.

### VerifyController (public, no auth)
`show(string $token)` — `GET /verify/permit/{token}`, throttled. Looks up `Permit::where('verification_token', $token)`; renders `verify/permit.blade.php` with the permit/applicant details if found, or a "could not be verified" state if not. The view's `match($permit->permitType->code)` for the displayed "Permit Type" label only explicitly listed OP/DP/SGP — FP and AI both silently fell into the `default => 'Building Permit'` case; fixed by adding `'FP' => 'Fencing Permit'` and `'AI' => 'Annual Inspection Permit'`.

### Other Controllers
DashboardController, OnlineApplicationController, ReportController, SettingsController, FeeScheduleController, ProfileController

`DashboardController::index()` aggregates KPI stats, the Monthly Transactions chart, and Recent Applications across **all 6 permit types** (BP/OP/DP/SGP/FP/AI) — originally only knew about BP and OP. The chart gained 4 more grouped-bar datasets (one per DP/FP/SGP/AI, sourced from `Collection.applicationable_type` the same way BP/OP already were); Recent Applications merges all 6 types' latest records into one timestamp-sorted list, resolving AI's display name from `owner_name` (it has no applicant first/middle/last split) and routing each entry to its correct per-type `show` page.

`ReportController::generate()` (permits report) and `App\Exports\PermitReportExport` filter to `permit_generated`/revoked applications only, and add Permit No./TTA columns.

`ReportController::auditLogs()` — `GET /reports/audit-logs`, gated by `can:view-audit-logs` (super-admin only; the permission is granted to no other role). Queries `Spatie\Activitylog\Models\Activity::with(['causer', 'subject'])` filtered by `search` (description), `causer_id`, `subject_type`, `event`, and a month range (`?month=YYYY-MM`, defaults to current month), paginated 20/page.

`SettingsController::storeUser()` validates and applies an admin-supplied password (`Password::min(8)->mixedCase()->numbers()->symbols()`, `confirmed`) instead of hardcoding every new staff account to `password123`. `update()` (Settings → General file uploads) derives each file setting's storage path from its key via a `match` expression, rather than a single hardcoded path shared by all file settings.

---

## Services (8)

| Service | Purpose |
|---------|---------|
| ApplicationService | BP CRUD, numbering, status transitions |
| OccupancyApplicationService | OP CRUD, numbering, status transitions |
| AssessmentService | finalize() — recalculate totals, mark finalized |
| FeeComputationService | computeFee() — 6 methods with excess/min/max |
| BillingService | generateFor() — auto-create billing on assessment finalize (BP/OP/DP/SGP), set status to billed. Internal `$morphType` match must carry all 4 permit-code arms — was found missing the `SGP` arm during the SGP build (silently created billings with `applicationable_type = 'bp'`) |
| CollectionService | recordPayment() — create collection, update billing |
| PermitService | generatePermit() — create permit with auto-numbering |
| SettingService | get(), set() — system settings |

## Actions (4)
CreateApplicationAction, FinalizeAssessmentAction, GeneratePermitAction, ProcessPaymentAction

## DTOs (4)
ApplicationDTO, OccupancyApplicationDTO, AssessmentItemDTO, CollectionDTO

## Enums (5)

| Enum | Values |
|------|--------|
| ApplicationStatus | draft, submitted, for_zoning_assessment, zoning_assessed, engineering_assessed, billed, paid, permit_generated, released, cancelled |
| AssessmentType | building, occupancy, zoning |
| ComputationMethod | fixed, per_unit, range_based, cumulative_range, percentage, formula |
| PaymentMode | cash, check, online |
| PermitTypeCode | BP, OP, FP, EP, DP, SGP, SP, ELP, AI, PP, ECP (renamed from MP) |

---

## Views

### Layouts / Partials
`layouts/app.blade.php`, `layouts/guest.blade.php`, `partials/sidebar-nav.blade.php`

### Application Views
BP: `applications/index`, `form`, `show`
OP: `occupancy-applications/index`, `form`, `show`
DP: `demolition-applications/index`, `form`, `show`
SGP: `signage-applications/index`, `form`, `show`
FP: `fencing-applications/index`, `form`, `show`
AI: `annual-inspection-applications/index`, `form`, `show` (directory renamed from `mechanical-applications/`) — `form`/`show` both carry the "Equipment / Items to be Inspected" checklist section (Alpine repeatable-row UI on the form; a simple listed table on show)

### Assessment Views
`assessments/assess.blade.php` — tabbed: Construction, Electrical, Mechanical, Plumbing, Electronics, Accessories, Accessory, Surcharges, DEMO_FEE (DP), SGP_FEE (SGP, generic fallback form), FP_FEE (FP), AINSP_GEN/AINSP_ELECTRONICS/AINSP_MECH/AINSP_ELEC (AI — the module's original 5 equipment-tab branches, `AI_AC`/`AI_MACH`/`AI_ESC`/`AI_ELEV`/`AI_GENSET`, were removed once these 4 official-schedule tabs replaced them), Summary. Excluded from tabs: ZONING_LC, ZONING_CERT, ANN_INSP, VIOLATION, MECH_INSP. `$isOp`/`$isDp`/`$isSgp`/`$isFp`/`$isMp` flags thread through every route/visibility ternary (the AI flag variable name `$isMp` was kept from the pre-rename build). The AINSP_MECH/AINSP_ELEC branches additionally carry a per-fee-code `quantityEligible` list (Alpine, Mechanical tab) / reuse of the existing `showKva` computed property (Electrical tab) to conditionally show a second "Quantity" input, and a parallel PHP-side `$aiQuantityEligibleCodes`/`$aiUnitLabels` pair (declared fresh each `@foreach($tabCategories as $cat)` iteration, to avoid stale values leaking into an unrelated tab) driving the assessment-items table's split Unit/Qty columns — this same pair/split logic was later extended into the **Summary tab's** per-category tables too (previously a generic single Qty/Unit-Fee column pair that didn't know about the AI split, showing a raw ambiguous quantity for AI items). When `$isAi`, a read-only amber "Declared Equipment (Basis of Assessment)" panel renders above the tab bar (on every AI tab), listing `$application->equipmentItems` — purely informational, sourced from the AI application form's equipment checklist, no interaction with the add-item forms.
`assessments/demolition-index.blade.php`, `assessments/signage-index.blade.php` — the DP/SGP equivalents of `occupancy-index.blade.php`. FP and AI reuse the same generic index pattern.

### Other Views
`zoning/`, `collections/`, `permits/`, `online/`, `dashboard/`, `settings/`, `reports/`, `auth/`. (`billing/` views removed — billing is print-only now, served via `pdf/billing-statement`.)

`collections/create.blade.php` — POS-style single-screen payment form: Application No./Applicant + OR Number/Paid By rows, a 3-column Amount Due/Amount Received/Change strip (Alpine-live), a Cash/Check/Online segmented control, and a sticky bottom action bar so the collector doesn't scroll mid-transaction.

### PDF Templates (`resources/views/pdf/`)
application-form (BP Unified Application Form — background-image overlay, see below), occupancy-application-form (OP Unified Application Form for Certificate of Occupancy — DomPDF, see below), demolition-application-form (DP application form, NBC Form No. B-08 — background-image overlay, see below), fencing-application-form (FP application form, NBC Form No. B-03 — background-image overlay), architectural-form (NBC Form A-01 Architectural Permit — background-image overlay, see below), structural-form (NBC Form A-07 Civil/Structural Permit — background-image overlay, see below), electrical-form (Form No. 77-001-S Electrical Permit — background-image overlay, see below), sanitary-form (Form No. 77-001-S Sanitary/Plumbing Permit — background-image overlay, see below), mechanical-form (NBC Form No. A-04 Mechanical Permit — background-image overlay, see below), electronics-form (NBC Form No. A-07 Electronics Permit — background-image overlay, see below), discipline-form (unused generic fallback), building-permit (NBC Form B-018 style, city seal + DPWH logo + QR code), occupancy-permit (DPWH Certificate of Occupancy style, DPWH logo + city seal + QR code), demolition-permit (bordered-frame landscape certificate style + QR code), signage-permit (same bordered-frame style, cloned from demolition-permit + QR code), fencing-permit (2-page plain-CSS NBC Form B-03 reproduction + QR code), annual-inspection-permit (one certificate per print, parameterized by `AnnualInspectionPermitUnit` — itemized table for bundle-type certificates, single equipment line for per-unit Elevator/Escalator certificates + QR code), assessment-summary (BP), assessment-summary-op (OP), assessment-summary-dp (DP), assessment-summary-sgp (SGP), assessment-summary-fp (FP), assessment-summary-ai (AI), billing-statement, official-receipt, zoning-certification, locational-clearance, evaluation-report, report

**Note on `mechanical-form.blade.php`**: this is NBC Form No. A-04, one of the 6 Building Permit **discipline** print forms (`ApplicationController::printDiscipline()`), unrelated to the separate Annual Inspection (AI, formerly "Mechanical Permit") application module — the naming overlap ("mechanical") is coincidental, the two features share no code.

**SGP has no `signage-application-form.blade.php`** — unlike every other permit type, the application-form print is deliberately deferred (no scanned official form supplied yet). `SignageApplicationController` has no `printForm()` method or `print` route.

Every template above that carries an Official Seal / logo sources it dynamically from `Setting` via the `Setting::general()` + `Setting::imageDataUri()` static helpers (base64 data-URI embedding) — including official-receipt, billing-statement, both assessment summaries, and evaluation-report, which previously had no branding at all. `OnlineApplicationController::doDownloadPermit()` (client-portal permit download) passes the same full variable set (`settings`/`sealImage`/`dpwhLogo`/`qrImage`) as `PermitController::print()` — it previously passed none of them, so downloaded permits silently rendered without seal/logo/QR.

**`application-form.blade.php` (browser-print HTML, not DomPDF)** — rendered directly (no PDF conversion) via `ApplicationController::printForm()` (BP only), opened in-browser with a print toolbar (`window.print()`), not routed through DomPDF like the other templates above. Rebuilt 2026-07-09 as a **background-image overlay**: `public/images/forms/unified-bp-form-p1.png` / `-p2.png` (scans of the official 2-page government form) are set as full-page CSS backgrounds (`print-color-adjust: exact` so browsers actually print them), and ~84 dynamic fields (owner/applicant data, scope-of-work and occupancy-group checkmarks, costs, signatories) are absolutely positioned on top in inch units, calibrated against the source image via PHP GD pixel scans rather than visual guesswork. Both scans were later replaced: **p1** (1700×2800, Legal source cropped to 8.5×13in long bond) has no pre-printed header — the letterhead is overlaid (seal from `general.logo` left, `general.national_govt_logo` right, Republic/`general.city`/`general.province` centered); the Area No. box falls back to the `general.area_number` setting; the Enterprise Name overlay was removed (the pre-printed label fills its whole cell — measured, no writable space). **p2** (1700×2600 = exactly 8.5×13in, own `background-size: 8.5in 13in` override) ends in a "SIGNATURE OVER PRINTED NAME OF OWNER/APPLICANT" line — the overlay prints the applicant's name centered above it; the former Building Official name/designation overlay is gone.

**`occupancy-application-form.blade.php` (DomPDF)** — rendered by `OccupancyApplicationController::printForm()` (which previously reused the BP view and crashed on `$application->permitType->code`; `OccupancyApplication` has no such relation). A4 portrait, 0.75in margin + `.content` padding (locational-clearance pattern). Two-logo table header (seal left / national govt logo right / address text centered), FULL/PARTIAL checkboxes from `applicationType->name`, FSIC checkbox from `fsic_no`, BP/FSEC references, applicant/project fields, static 5-item requirements checklist, and a two-column signatory block: left "Inspected by:" (blank line, then the `building_official` Signatory name + designation), right "Submitted by:" (applicant name over its line) + CTC fields + "Attested by:" block with ARCHITECT OR CIVIL ENGINEER line, Date line, and a blank PRC/PTR/TIN/CTC table.

**`architectural-form.blade.php` (DomPDF)** — rendered by `ApplicationController::printDiscipline()` when `$discipline === 'architectural'` (via the private `printArchitecturalForm()`). Reproduces NBC Form No. A-01 (Architectural Permit) as a 2-page background-image overlay, same technique as `application-form.blade.php`: `public/images/forms/architectural-p1.png` / `-p2.png` (1700×2600 @ 200dpi, rasterized from the user's own source PDFs via WinRT, field positions GD-pixel-calibrated). Page 1 fields: dynamic letterhead (seal + national govt logo + Republic/City/Province), Box 1 (Owner/Applicant name/M.I./TIN, enterprise name, form of ownership, occupancy, address — all positioned on the blank line *below* their printed label rather than beside it, at a larger font size for readability), Location of Construction, Scope of Work checkboxes, Box 4 (Supervision engineer — from `engineer_*` fields), Box 5 (Building Owner = applicant), Box 6 (Lot Owner consent — CTC No./Date Issued/Place Issued also placed below-label with a full 4-digit year). Box 3 (Design Professional) is deliberately left blank for hand-signing, since the plans may be sealed by an architect other than the engineer on record. Page 2 renders only a "PERMIT ISSUED BY:" block (Title/Name/Designation) sourced from the generated `Permit`'s `building_official_*` snapshot columns, shown only if a Permit exists; the rest of page 2 (Boxes 7–9, internal office processing) is pure background with no overlay data.

**`structural-form.blade.php` (DomPDF)** — rendered by `ApplicationController::printDiscipline()` when `$discipline === 'structural'` (via the private `printStructuralForm()`). Reproduces NBC Form No. A-07 (Civil/Structural Permit) as a 2-page background-image overlay, same technique and field-mapping conventions as `architectural-form.blade.php`: `public/images/forms/structural-p1.png` / `-p2.png` (rasterized from the user's source PDFs — an unusual 11.33×17.33in native page size, same aspect ratio as the standard 8.5×13in scaled 4/3×, rasterized directly to the standard 1700×2600px canvas). Box 4 "Supervision/In-Charge of Civil/Structural Works" reuses the same generic `engineer_*` fields Architectural's Box 4 uses (no dedicated structural-engineer columns exist on `Application`); Box 3 "Design Professional" is left blank for the same hand-signing rationale. Page 2's "PERMIT ISSUED BY:" block uses the same `resolveBuildingOfficial()`-sourced fallback as Architectural.

**`electrical-form.blade.php` (DomPDF)** — rendered by `ApplicationController::printDiscipline()` when `$discipline === 'electrical'` (via the private `printElectricalForm()`). Reproduces Form No. 77-001-S (Electrical Permit) as a 2-page background-image overlay, own scans (`public/images/forms/electrical-p1.png` / `-p2.png`, same 11.33×17.33in native source-size quirk as Structural). Unlike Architectural/Structural, this form has dedicated data columns rather than reusing the generic engineer block: Box 2 "Design Professional" is filled from the `pee_*` (Professional Electrical Engineer) fields, and a "Summary of Electrical Loads/Capacities" section is filled from `total_connected_load`/`total_transformer_capacity`/`total_generator_capacity`. Box 3 "Supervisor of Electrical Works" still reuses the generic `engineer_*` fields (no separate supervisor data exists). Only the "New Installation" Scope of Work checkbox maps to an existing `scope_of_work_id`; this form's other 7 scope options (Reconnection/Relocation/Annual Inspection/Separation/Temporary/Upgrading/Others) have no equivalent stored field. No ZIP CODE column exists in this form's Address row. Page 2's "PERMIT ISSUED BY:" block uses the same `resolveBuildingOfficial()`-sourced fallback as the other two forms.

**`sanitary-form.blade.php` (DomPDF)** — rendered by `ApplicationController::printDiscipline()` when `$discipline === 'sanitary'` (via the private `printSanitaryForm()`). Reproduces Form No. 77-001-S (Sanitary/Plumbing Permit) as a 2-page background-image overlay, own scans (`public/images/forms/sanitary-p1.png` / `-p2.png`, same 11.33×17.33in native-source quirk as Structural/Electrical). Denser layout than the other three discipline forms: separate ADDRESS and LOCATION OF INSTALLATION rows each with their own city/municipality field; no lot/block/TCT/tax-dec fields; a large FIXTURES TO BE INSTALLED / WATER SUPPLY checklist with no backing `Application` data, left entirely blank. Fills `no_of_storeys`, `total_floor_area`, `plumbing_cost` ("Total Cost of Installation"), and `proposed_construction_date`/`expected_completion_date` — none of which appear on the other discipline forms. Box 6 (Design Professional) left blank for hand-signing; Box 7 (In-Charge of Installation) and Box 8 (Applicant) filled from the generic `engineer_*`/`applicant_*` fields. No "Permit Issued By" section exists on this form.

**`mechanical-form.blade.php` (DomPDF)** — rendered by `ApplicationController::printDiscipline()` when `$discipline === 'mechanical'` (via the private `printMechanicalForm()`). Reproduces NBC Form No. A-04 (Mechanical Permit) as a 2-page background-image overlay; source is a pair of clean digitally-generated reference images (not a scan), saved as `public/images/forms/mechanical-p1.png` / `-p2.png` at a native 1700×2800px (8.5×14in @ 200dpi — legal-size, unlike the other discipline forms' 8.5×13in), paper size set accordingly (`[0, 0, 612, 1008]`). Scope of Work checkboxes map all 12 of this form's options (Erection/Addition/Alteration/Renovation/Conversion/Repair/Raising/Moving/Demolition/Accessory Structure/Others) against the seeded `scope_of_works` table — the richest scope mapping of any discipline form so far. Box 2 (Installation and Operation of... — boiler/pressure vessel/aircon/elevator/etc.) and Box 3/4 (Professional Mechanical Engineer / Supervisor-In-Charge of Mechanical Works) are left entirely blank: no backing columns exist for per-installation-type checkboxes or a PME/SIM data group (unlike Electrical's `pee_*`/`sew_*`). Box 5 (Building Owner) / Box 6 (Lot Owner) reuse the generic `applicant_*`/`owner_*` fields, same convention as the other forms. Page 2's Box 9 "Permit Issued By" uses the same `resolveBuildingOfficial()`-sourced fallback; Boxes 7/8 (internal office receipt/progress-flow) are pure background.

**`electronics-form.blade.php` (DomPDF)** — rendered by `ApplicationController::printDiscipline()` when `$discipline === 'electronics'` (via the private `printElectronicsForm()`). Reproduces NBC Form No. A-07 (Electronics Permit) as a 2-page background-image overlay, own clean digitally-generated reference images at the standard 1700×2600px (8.5×13in @ 200dpi). The sixth and final discipline form, completing the print-forms set. Scope of Work maps "New Installation" and "Others" against `scope_of_works` (ids 1 and 13); "Annual Inspection" has no equivalent scope row and is left unmapped. Box 2 (Nature of Installation Works/Equipment System — telecommunication/broadcasting/TV/security/fire alarm/etc.) and Box 3/4 (Design Professional / Supervisor-In-Charge of Electronics Works) are left entirely blank, same "no backing columns, don't guess" convention as Mechanical's Box 2/3/4. Box 5/6 (Building Owner/Lot Owner) reuse the generic `applicant_*`/`owner_*` fields with a real gutter margin between the two columns (same convention discovered on Mechanical). Page 2's "PERMIT ISSUED BY:" has no signature-line caption on this particular form — the Building Official name/designation are placed directly beneath the label with nothing else on the page below it.

**`discipline-form.blade.php` (DomPDF)** — no longer used by any discipline; all six (Architectural/Structural/Electrical/Sanitary/Mechanical/Electronics) now render dedicated background-image-overlay views. Retained only as the generic fallback for any future/unrecognized discipline key.

**"Computer-generated document" footer** — `application-form.blade.php`, `occupancy-application-form.blade.php`, and all 6 discipline forms print "This is a computer-generated document. Printed on: {{ now()->format('m/d/Y') }} | Printed by: {{ auth()->user()?->full_name }}" on every page, matching the pattern already present on `building-permit.blade.php`/`occupancy-permit.blade.php` (wording normalized from "...generated permit..." to "...generated document..." for consistency). Every one of these views is only reachable through authenticated routes, so `auth()->user()` is called directly in the Blade template with no controller changes. On the 7 `.print-page`-based forms it's positioned with `bottom:0.12in` (not `top:`) so it anchors to each page's true bottom edge regardless of page height — needed since Mechanical is 8.5×14in while the other 6 are 8.5×13in.

### Public Views (no auth)
`verify/permit.blade.php` — standalone permit verification page rendered by `VerifyController::show()`, styled independently of `layouts/app.blade.php` (no sidebar/auth chrome), similar in spirit to `layouts/guest.blade.php`.

---

## Providers

| Provider | Purpose |
|----------|---------|
| AppServiceProvider | Morph map: bp → Application, op → OccupancyApplication, dp → DemolitionApplication, sgp → SignageApplication, fp → FencingApplication, ai → AnnualInspectionApplication (renamed from mp → MechanicalApplication) |
| SelfHealingServiceProvider | Auto DB + migrations + seeds on boot |

`bootstrap/app.php` — `withExceptions()` renders any 419 `HttpException` (CSRF/session expiry) as a redirect to `login`/`staff.login` with a flash message. `routes/web.php` ends with `Route::fallback()`, redirecting any unmatched URL to the role-appropriate home or `login`.

## Seeders (9)

| Seeder | Data |
|--------|------|
| RolePermissionSeeder | 9 roles, 30+ permissions |
| ReferenceDataSeeder | Permit types (incl. DP, SGP), app types, scopes, ownerships, building parts, land classifications, signatories, fee categories (incl. MECH_INSP, DEMO_FEE, SGP_FEE) |
| OccupancyGroupSeeder | 10 groups A–J, 40+ sub-groups |
| FeeScheduleSeeder | Complete fee structure: CONST, ELEC, MECH, MECH_INSP (29 INSP_* types/55 schedules from BOPMS ann_inspection_f* tables), PLUMB, ELEC_INSP, OCC, SURCHARGE, DEMO_FEE (6 types w/ unit_label), ZONING fee tables. SGP_FEE is seeded empty (category only, no rates) |
| GeoDataSeeder | ~42K barangays (Philippine PSA data, 2.5MB) |
| SettingsSeeder | System settings (electrical_inspection_percentage, filing/processing defaults, general.logo, general.favicon, general.dpwh_logo, general.national_govt_logo, general.city, general.province, general.area_number, general.zip_code, general.domain) — file-type settings use `firstOrCreate` so re-seeding never clobbers an uploaded logo |
| AdminUserSeeder | Default admin user |
| ApplicationSeeder | 5 BP + 5 OP test records |
| AssessmentTestSeeder | Assessment test data |

## Third-Party Packages

| Package | Purpose |
|---------|---------|
| spatie/laravel-permission | RBAC |
| spatie/laravel-activitylog | Audit trail |
| barryvdh/laravel-dompdf | PDF generation |
| maatwebsite/excel | Excel exports |
| picqer/php-barcode-generator | Code 128 barcode on assessment PDF |
| endroid/qr-code | QR verification code on Building/Occupancy Permit PDFs |
