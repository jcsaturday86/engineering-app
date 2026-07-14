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
│   ├── Http/Controllers/  (15 + Auth controllers)
│   ├── Models/            (32 Eloquent models)
│   ├── Notifications/     (4 notification classes)
│   ├── Providers/         (AppServiceProvider, SelfHealingServiceProvider)
│   └── Services/          (8 service classes)
├── database/
│   ├── migrations/        (22+ migration files)
│   ├── seeders/           (9 seeders)
│   └── factories/
├── resources/views/       (40+ Blade templates)
├── routes/web.php         (100+ routes)
├── docs/                  (this documentation)
└── CLAUDE.md
```

---

## Models (32)

### Core Transaction Models

| Model | Table | Notes |
|-------|-------|-------|
| Application | applications | BP only. Implements PermitApplicationContract + HasPermitApplicationBehavior. getPermitTypeCode() = 'BP' |
| OccupancyApplication | occupancy_applications | OP only. Same contract/trait. getPermitTypeCode() = 'OP' |
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
| Permit | permits | morphTo: applicationable. SoftDeletes, LogsActivity. `verification_token` (UUID) set on generation, used for the public QR-code verification link. `status` includes `revoked`; `revoke_reason` and `building_official_*` snapshot columns added for revocation/audit |
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
| Signatory | signatories |
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

**Private helpers:**
- `resolveInspectionFee(string $code, float $unit): array` — maps MECH_* code → INSP_* fee type (MECH_INSP category), does range or first-row lookup, returns {fee, excess_threshold, excess_fee, every, method}. Three methods: flat (range-band fixed), per_unit (rate × unit), tiered (cumulative for elevators).
- `calculateTotals(Assessment $assessment): array` — returns subtotal, inspection, filing, processing, total.
- `redirectIfFinalized(Assessment, PermitApplicationContract): ?RedirectResponse` — called by every add/remove method; when assessment status = finalized, redirects to the assess page `?tab=SUMMARY` with an error flash.
- `doPrint(PermitApplicationContract)` — dispatches by `getPermitTypeCode()`: BP renders `pdf.assessment-summary` (building + zoning sections); OP delegates to `doPrintOp()`, which renders `pdf.assessment-summary-op` with only the Occupancy Fees section (no Zoning/Building/Other Fees/Filing/Processing). Both generate a Code 128 barcode (picqer BarcodeGeneratorPNG, base64) and load the `building_official` signatory.

### BillingController
print only. Billing is auto-generated on assessment finalize via `BillingService::generateFor(PermitApplicationContract)` (guards: status must be `engineering_assessed`, no existing unpaid billing). The Billing menu/index page and manual generate routes were removed.

### CollectionController
- `index(Request $request)` — accepts `search`: exact match on a billed BP/OP `application_number` redirects straight to that payment form (barcode-scan UX); partial match filters the Awaiting Payment list by application number or applicant name. Also accepts `month` (`YYYY-MM`, defaults to current month) — the Payment History list is scoped to the logged-in collector's own transactions (`collected_by = Auth::id()`) within that month
- `create`/`store` (BP), `createOp`/`storeOp` (OP) — `doStore()` rejects an insufficient cash payment (`amount_received < billing->total_amount` when `payment_mode = cash`) before recording
- `receipt` (renders the Official Receipt PDF with the dynamic city seal), `voidForm`, `processVoid`

### PermitController
buildingIndex, occupancyIndex, generate (BP), revertGenerate (BP), restoreRevoke (BP), generateOp (OP), revertGenerateOp (OP), restoreRevokeOp (OP), print, zoningCertification, locationalClearance, evaluationReport

`buildingIndex`/`occupancyIndex` accept `search`, `status` (including a `revoked` pseudo-status matched via `whereHas('permits', fn ($q) => $q->withTrashed()->where('status', 'revoked'))`), and `year` (defaults to current year) query params.

`revertGenerate`/`revertGenerateOp` (`revert-permits` permission) tag the `Permit` `status = 'revoked'` (with a required `revoke_reason`) and soft-delete it, rolling the application status back to `paid`. `doGenerate()` refuses to create a new permit while a revoked permit exists for the application (`onlyTrashed()->where('status', 'revoked')->exists()`). `restoreRevoke`/`restoreRevokeOp` (same permission, password-confirm only) reverse this: `$permit->restore()`, `status` back to `generated`, application back to `permit_generated`.

`generate`/`generateOp` (via `doGenerate()`) set a `verification_token` (UUID) on the new `Permit` row, and snapshot the currently-active `building_official` Signatory onto `building_official_name`/`_title`/`_designation`/`_license_no` — a one-time capture that survives Signatory edits, revoke, and restore. `print()` additionally builds a QR code (`endroid/qr-code`) encoding the public verification URL (`{general.domain setting|app.url}/verify/permit/{token}`) and passes it (plus `sealImage`, `dpwhLogo` — both sourced from `Setting`, each falling back to a static default) to the `pdf.building-permit` / `pdf.occupancy-permit` templates, which read the Building Official line from the permit's own snapshot columns, not the live Signatory.

### VerifyController (public, no auth)
`show(string $token)` — `GET /verify/permit/{token}`, throttled. Looks up `Permit::where('verification_token', $token)`; renders `verify/permit.blade.php` with the permit/applicant details if found, or a "could not be verified" state if not.

### Other Controllers
DashboardController, OnlineApplicationController, ReportController, SettingsController, FeeScheduleController, ProfileController

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
| BillingService | generateFor() — auto-create billing on assessment finalize (BP + OP), set status to billed |
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
| PermitTypeCode | BP, OP, FP, EP, DP, SP, ELP, MP, PP, ECP |

---

## Views

### Layouts / Partials
`layouts/app.blade.php`, `layouts/guest.blade.php`, `partials/sidebar-nav.blade.php`

### Application Views
BP: `applications/index`, `form`, `show`
OP: `occupancy-applications/index`, `form`, `show`

### Assessment Views
`assessments/assess.blade.php` — tabbed: Construction, Electrical, Mechanical, Plumbing, Electronics, Accessories, Accessory, Surcharges, Summary. Excluded from tabs: ZONING_LC, ZONING_CERT, ANN_INSP, VIOLATION, MECH_INSP.

### Other Views
`zoning/`, `collections/`, `permits/`, `online/`, `dashboard/`, `settings/`, `reports/`, `auth/`. (`billing/` views removed — billing is print-only now, served via `pdf/billing-statement`.)

`collections/create.blade.php` — POS-style single-screen payment form: Application No./Applicant + OR Number/Paid By rows, a 3-column Amount Due/Amount Received/Change strip (Alpine-live), a Cash/Check/Online segmented control, and a sticky bottom action bar so the collector doesn't scroll mid-transaction.

### PDF Templates (`resources/views/pdf/`)
application-form (BP Unified Application Form — background-image overlay, see below), occupancy-application-form (OP Unified Application Form for Certificate of Occupancy — DomPDF, see below), architectural-form (NBC Form A-01 Architectural Permit — background-image overlay, see below), structural-form (NBC Form A-07 Civil/Structural Permit — background-image overlay, see below), electrical-form (Form No. 77-001-S Electrical Permit — background-image overlay, see below), sanitary-form (Form No. 77-001-S Sanitary/Plumbing Permit — background-image overlay, see below), mechanical-form (NBC Form No. A-04 Mechanical Permit — background-image overlay, see below), electronics-form (NBC Form No. A-07 Electronics Permit — background-image overlay, see below), discipline-form (unused generic fallback), building-permit (NBC Form B-018 style, city seal + DPWH logo + QR code), occupancy-permit (DPWH Certificate of Occupancy style, DPWH logo + city seal + QR code), assessment-summary (BP), assessment-summary-op (OP), billing-statement, official-receipt, zoning-certification, locational-clearance, evaluation-report, report

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

### Public Views (no auth)
`verify/permit.blade.php` — standalone permit verification page rendered by `VerifyController::show()`, styled independently of `layouts/app.blade.php` (no sidebar/auth chrome), similar in spirit to `layouts/guest.blade.php`.

---

## Providers

| Provider | Purpose |
|----------|---------|
| AppServiceProvider | Morph map: bp → Application, op → OccupancyApplication |
| SelfHealingServiceProvider | Auto DB + migrations + seeds on boot |

`bootstrap/app.php` — `withExceptions()` renders any 419 `HttpException` (CSRF/session expiry) as a redirect to `login`/`staff.login` with a flash message. `routes/web.php` ends with `Route::fallback()`, redirecting any unmatched URL to the role-appropriate home or `login`.

## Seeders (9)

| Seeder | Data |
|--------|------|
| RolePermissionSeeder | 9 roles, 30+ permissions |
| ReferenceDataSeeder | Permit types, app types, scopes, ownerships, building parts, land classifications, signatories, fee categories (incl. MECH_INSP) |
| OccupancyGroupSeeder | 10 groups A–J, 40+ sub-groups |
| FeeScheduleSeeder | Complete fee structure: CONST, ELEC, MECH, MECH_INSP (29 INSP_* types/55 schedules from BOPMS ann_inspection_f* tables), PLUMB, ELEC_INSP, OCC, SURCHARGE, ZONING fee tables |
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
