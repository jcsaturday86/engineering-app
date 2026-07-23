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
| Dynamic branding (seal/logos/favicon) | DONE | `Setting::general()`/`imageDataUri()` helpers; seal on all printed documents; `general.favicon` browser-tab icon (falls back to seal); `general.national_govt_logo` on both application forms |
| Browser autofill disabled | DONE | autocomplete="off" on all forms |
| Form validation UX | DONE | Error banner, section highlighting, scroll-to-error |
| Test data seeder | DONE | ApplicationSeeder: 5 BP + 5 OP |
| Revert / send-back actions | DONE | Password-confirmed backward transitions at every workflow step: submission, zoning, engineering assessment, OP-to-draft, permit generation |
| On-demand barangay lookup | DONE | `GeoController::barangaysForCity()` — AJAX fetch replacing full ~42K-row client-side dataset |
| Unknown-URL fallback | DONE | `Route::fallback()` — redirect to role-appropriate home or login instead of 404 |
| Session-expired (419) redirect | DONE | Redirects to login/staff.login with flash message instead of default Page Expired screen |
| Password visibility toggles | DONE | Client login, staff login, registration, staff account creation |
| Staff account password complexity | DONE | Admin-supplied password now enforced/used on Create User (was previously discarded, hardcoded to `password123`) |

---

## Building Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD (walk-in) | DONE | |
| Application CRUD (online) | DONE | Client portal |
| Application numbering | DONE | BP-YYYY-MM-NNNNN |
| Occupancy group selection | DONE | Groups A–J with sub-groups |
| All BP form fields | DONE | Applicant, enterprise, project, building, costs, engineers |
| Application form print | DONE | Now a real DomPDF stream (was browser-print HTML): background-image overlay of the official 2-page Unified Application Form (8.5×13in long bond), ~84 dynamic fields; overlaid letterhead (seal left, National Govt logo right, city/province from Settings); Area No. from `general.area_number`; p2 applicant signature line (Building Official block removed with the new scan); `dpi=200` set explicitly so the background scan isn't downsampled/blurred |
| Print Forms dropdown | DONE | BP Show page: single "Print Forms" dropdown (Alpine) replacing 7 separate buttons — Application Form + 6 discipline forms (Architectural/Structural/Electrical/Sanitary/Mechanical/Electronics) |
| Discipline print routes | DONE | `applications/{id}/print-discipline/{discipline}` — all 6 disciplines (Architectural, Structural, Electrical, Sanitary, Mechanical, Electronics) now render real permit-form PDFs |
| Architectural Permit PDF (NBC Form A-01) | DONE | Background-image overlay (own scans, GD pixel-calibrated); Boxes 1/4/5/6 auto-filled from the Application record + letterhead; Box 3 (Design Professional) left blank for hand-signing; page 2 "Permit Issued By" reads the Permit's building-official snapshot, or the active Signatory if no Permit yet |
| Structural Permit PDF (NBC Form A-07) | DONE | Same overlay technique/conventions as Architectural, own scans; Box 4 "Supervision/In-Charge" reuses the generic `engineer_*` fields (no dedicated structural-engineer columns exist); shares `resolveBuildingOfficial()` with Architectural |
| Electrical Permit PDF (Form No. 77-001-S) | DONE | Same overlay technique, own scans; Box 2 "Design Professional" filled from real `pee_*` (Professional Electrical Engineer) fields + `total_connected_load`/`total_transformer_capacity`/`total_generator_capacity` (KVA summary); Box 3 "Supervisor of Electrical Works" reuses generic `engineer_*` fields; only the "New Installation" scope checkbox maps to existing data (this form's other 7 scope options have no equivalent field) |
| Sanitary/Plumbing Permit PDF (Form No. 77-001-S) | DONE | Same overlay technique, own scans; Box 6 (Design Professional) left blank for hand-signing, Box 7 (In-Charge of Installation) and Box 8 (Applicant) filled from generic `engineer_*`/`applicant_*` fields; also fills `no_of_storeys`, `total_floor_area`, `plumbing_cost`, `proposed_construction_date`/`expected_completion_date` — fields with no printed home on any other discipline form; no "Permit Issued By" signatory block exists on this form |
| Mechanical Permit PDF (NBC Form A-04) | DONE | Same overlay technique; source is a clean digitally-generated reference image (not a scan), 8.5×14in legal-size (unlike the other forms' 8.5×13in); Scope of Work maps all 12 checkbox options against `scope_of_works`; Box 2 (Installation types) and Box 3/4 (PME/Supervisor of Mechanical Works) left blank — no backing columns exist; Box 5/6 (Building/Lot Owner) reuse generic `applicant_*`/`owner_*` fields; page 2 "Permit Issued By" shares `resolveBuildingOfficial()` |
| Electronics Permit PDF (NBC Form A-07) | DONE | Sixth and final discipline form — print-forms set now complete. Same overlay technique, standard 8.5×13in; Scope of Work maps New Installation/Others (Annual Inspection has no equivalent); Box 2 (Nature of Installation checklist) and Box 3/4 (Design Professional/Supervisor) left blank — no backing columns; Box 5/6 reuse generic `applicant_*`/`owner_*` fields with a real gutter margin between columns; page 2 "Permit Issued By" has no signature-line caption on this form |
| "Computer-generated document" footer on all forms | DONE | Extended from Building/Occupancy Permit to all 10 application/permit PDFs (BP/OP applications + all 6 discipline forms); `bottom:` positioning on `.print-page` forms auto-adapts to Mechanical's taller 8.5×14in page; no controller changes needed, `auth()->user()?->full_name` called directly from each view |
| Cancel hidden after permit generation | DONE | Show-page Cancel button excluded for `permit_generated` (in addition to paid/released/cancelled) |
| Status workflow | DONE | 8-state machine |
| Submission notification | DONE | Notifies engineering users |
| FSEC No. / Date Issued fields | DONE | Reference-only fields on the application form, shown on the printed Building Permit |
| Revert submission / return to zoning | DONE | `revertSubmission()`, `sendBackForEditing()`, `returnToZoning()` — password-confirmed |
| Year filter + Turn Around Time column | DONE | `/applications` index: `?year=` filter (default current year), submitted→permit-generated day count |

---

## Occupancy Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD | DONE | Separate controller/model/table |
| OP-specific fields | DONE | BP reference, FSEC, FSIC No., completion date |
| Applies For (Full/Partial) | DONE | Selected via Application Type; drives the FULL/PARTIAL checkbox on the printed certificate |
| Character of Occupancy | DONE | Shared occupancy group selection |
| Status workflow (skips zoning) | DONE | submitted → engineering_assessed |
| Polymorphic downstream | DONE | assessments, billings, collections, permits, documents |
| OP application form print (PDF) | DONE | Dedicated `occupancy-application-form.blade.php` (DomPDF, A4) — fixed crash from reusing the BP overlay view; official Certificate of Occupancy application layout with two-column signatory block |
| Revert submission / revert-to-draft | DONE | `revertSubmission()`; `AssessmentController::revertToDraftOp()` also purges occupancy fee entries |
| OP-appropriate status labels | DONE | `zoning_assessed` shown as "For Occupancy Assessment" (no zoning stage in OP) |
| Year filter + Turn Around Time column | DONE | `/occupancy-applications` index; Project Title column (replaced Applicant Address) |

---

## Demolition Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD (walk-in) | DONE | `demolition_applications` table + `DemolitionApplication` model/controller; morph map `dp` |
| Application numbering | DONE | DP-YYYY-MM-NNNNN |
| DP form fields | DONE | Applicant, enterprise, address, Location of Demolition Works, Scope of Work, Full-time Inspector/Supervisor, Lot Owner Consent |
| Status workflow (skips zoning) | DONE | submitted → engineering_assessed → billed → paid → permit_generated → released |
| City/Barangay edit-form selection bug | DONE (fixed) | Alpine `x-model` + `x-for`-rendered `<option>`s race condition — switched to `:value` + `@change` + `$nextTick`/`$watch` reapplication |
| Workflow Actions section removed from Show page | DONE | Redundant with header action buttons |
| DEMO_FEE fee category | DONE | 6 fee types (floor area, mech equip, hand demolition incl/excl floors, appendage, moving), own category scoped to the DP permit type — separate from BP's pre-existing `ASS_DEMO_*` under ACC_FEE |
| Fee-schedule-driven assessment tab | DONE | `addDemolitionItem()` auto-computes `amount = quantity × rate` server-side; replaced the earlier manual "Unit Fee" text-entry fallback |
| `fee_types.unit_label` + Demolition Fees settings page | DONE | `/settings/demolition-fees` — Settings-configurable physical unit ("sq.m.", "lineal meter(s)", etc.) per fee type, drives the assessment tab's dynamic Quantity label |
| Application form PDF (NBC Form B-08) | DONE | Background-image overlay of the official 2-page scan; letterhead + Building Official block on page 2 |
| Assessment summary PDF | DONE | `pdf/assessment-summary-dp.blade.php` |
| Final permit certificate PDF | DONE | `pdf/demolition-permit.blade.php`, bordered-frame landscape style, QR verification code |
| Sidebar entries | DONE | Main nav, Assessment flyout, Permits flyout |
| Excluded from online self-service + generic fee-schedule listing | DONE | Has its own dedicated Demolition Fees settings page |
| `/permits/demolition` Print button removed | DONE | Application-form printing for DP is a manual/physical process — underlying route/PDF untouched |
| OR Number autofocus on payment form | DONE | `collections/create.blade.php` (shared by BP/OP/DP/SGP) |

---

## Signage Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD (walk-in) | DONE | `signage_applications` table + `SignageApplication` model/controller; morph map `sgp`; permit code `SGP` (not `SP`, already reserved for a future unbuilt permit type) |
| Application numbering | DONE | SGP-YYYY-MM-NNNNN |
| SGP form fields | DONE | Applicant name, applicant address, Scope of Work (Install/Attach/Paint checkboxes + detail textboxes), Wordings, Premises Of |
| Status workflow (skips zoning) | DONE | Same 5-step shape as DP: submitted → engineering_assessed → billed → paid → permit_generated → released |
| Assessment fees | DONE (manual entry) | Empty `SGP_FEE` category seeded (tab renders); no `FeeType`/`FeeSchedule` rows yet — generic "Add Fee Item" fallback form used, same as every category originally worked before a dedicated fee-schedule form was built |
| Application form print | PENDING | No scanned official form supplied yet; deferred by explicit scope decision — every other print output is complete |
| Assessment summary PDF | DONE | `pdf/assessment-summary-sgp.blade.php` |
| Final permit certificate PDF | DONE | `pdf/signage-permit.blade.php`, bordered-frame landscape style, QR verification code |
| Sidebar entries | DONE | Main nav, Assessment flyout, Permits flyout — positioned below Demolition Permit |
| Excluded from online self-service | DONE | Not excluded from the generic `/settings/fees` listing (no dedicated settings page yet) |
| `/permits/signage` Print button | DONE | Shown (unlike DP) — final permit certificate print is complete, only the application-form print is deferred |
| Cross-cutting `match($permitCode)` bug sweep | DONE (fixed) | Found and fixed 4 places missing an `SGP` arm during end-to-end verification: `BillingService::generateFor()` (billing created with wrong morph type), `collections/index.blade.php` (pay-button route + type badge), `permits/index.blade.php` (permit-number link hardcoded to the BP show route — a pre-existing bug also affecting DP, now fixed for all 4 types), `verify/permit.blade.php` (type label) |

---

## Fencing Permit Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD (walk-in) | DONE | `fencing_applications` table + `FencingApplication` model/controller; permit code `FP` (pre-existing inactive PermitType flipped to active) |
| Application numbering | DONE | FP-YYYY-MM-NNNNN |
| FP form fields | DONE | Applicant/enterprise/address, Location of Construction, Scope of Work, Design Professional block, Inspector/Supervisor block, Consent of Lot Owner |
| Status workflow (skips zoning) | DONE | Same 5-step shape as DP/SGP: submitted → engineering_assessed → billed → paid → permit_generated → released |
| Inspector section design iteration | DONE | Originally a repeatable "Add Inspector" Alpine.js UI backed by a `fencing_inspectors` child table (`is_primary` flag) — first repeatable-child-record UI in this codebase. Simplified per user request to a second FIXED single block (8 flat `inspector_*` columns on `fencing_applications`, mirroring `design_professional_*`); migration drops `fencing_inspectors`, `FencingInspector` model deleted, controller/views/PDF read the flat columns directly |
| "Same as Design Professional" toggle | DONE | Pill-style toggle on the Inspector section copies all 8 Design Professional field values via client-side JS, reusing the existing "Same as PEE" pattern from the BP form |
| FP_FEE fee category | DONE | Reuses existing `ACC_FEE`-scoped fee schedule data (`ASS_FENCE_MASONRY`/`ASS_FENCE_INDIG`) under a new dedicated `FP_FEE` category rather than duplicating rate data |
| Line & Grade / Ground Preparation fee codes | DONE | 7 more codes added to the FP assessment fee dropdown (`ASS_LINE_GRADE`, `ASS_GP_INSPECT`, `ASS_GP_EXCAV`, `ASS_GP_ISSUANCE`, `ASS_GP_FOUND`, `ASS_GP_OTHER`, `ASS_GP_ENCROACH`), reusing existing `ACC_FEE` rate data; required adding a `case 'fixed':` branch to the fee-computation logic (3 of the 7 use fixed-fee computation, not needed by the original 2-code implementation). Note: these were first mistakenly wired into the Zoning assessment's fee dropdown, then fully reverted before being correctly added here — Zoning's dropdown is unchanged from before this session |
| Assessed Fees summing bug | DONE (fixed) | Certificate's Assessed Fees table only showed the first active fee item's amount instead of summing all active items — missed a second fee type when both Masonry and Indigenous fencing fees were assessed together |
| Final permit certificate PDF | DONE | `pdf/fencing-permit.blade.php`, 2-page plain-HTML/CSS reproduction of NBC Form B-03 |
| DomPDF 3-page pagination bug | DONE (fixed) | Certificate rendered 3 pages instead of 2 — root cause: insufficient CSS vertical-spacing headroom combined with a `display:table`-based two-column layout (`.box-half`) DomPDF mis-paginated; fixed by tightening spacing and switching to CSS `float`-based columns |
| Sidebar entries | DONE | Main nav, Assessment flyout, Permits flyout — positioned between Occupancy Permit and Demolition Permit |
| End-to-end verification | DONE | Full lifecycle verified in browser: create → submit → assess → finalize → pay → generate permit → print |
| Application-form print | DONE | `printForm()` (route `fencing-applications.print`) + `pdf/fencing-application-form.blade.php` — background-image-overlay over the NBC Form B-03 scans, added in a follow-up session (mirrors `DemolitionApplicationController`'s approach; DP/SGP's own application-form prints remain deferred) |
| Application-print field-position bugs | DONE (fixed) | Several Box 1/2/3/4 fields (Address, Location-of-Construction's Lot/Blk/TCT/Tax-Dec-No. row, Box 2/3 name lines, Box 4's C.T.C. row) had values overlapping their own printed labels; Tax Dec. No. needed a centered band instead of left-aligned. All re-calibrated via PHP-GD pixel scans of the source scans rather than eyeballed estimates |
| Building Official signature-line strikethrough | DONE (fixed) | Page 2's dynamic BO title/name/designation was positioned using an eyeballed screenshot estimate ~0.6in below the real line, causing the pre-printed signature line to strike through the text; re-measured via pixel scan and added a second designation line matching DP's two-line convention |
| Scope of Work checkbox sizing/position/glyph | DONE (fixed) | All 5 checkboxes were 0.04–0.25in off their printed squares and undersized; the checkmark glyph (`&#10004;`) was silently rendering as "?" because `.c` was missing `'DejaVu Sans'` in its font stack — a workaround already present in every other discipline form's PDF but missed here |
| Header letterhead (logos + Republic text) | DONE | Official seal + national government logo (0.76in — the largest size that fits without overlapping "OFFICE OF THE BUILDING OFFICIAL"; user chose this over a literal 1.5x that would've overlapped) + centered "Republic of the Philippines / [City] / Province of [Province]" text, matching DP's letterhead pattern |
| Application-print PDF performance fix | DONE (fixed) | `fencing-p1/p2.png` were Truecolor+Alpha PNGs hitting DomPDF's known-slow image-embedding path (~10s render); converted to flattened JPEGs (`fencing-p1/p2.jpg`, quality 90), matching the fix already applied to every other discipline form — render time dropped to ~2.3s. PNGs kept on disk unreferenced as calibration sources |
| Application-form required-field validation | DONE | Every create/edit field made `required` (client + server) except `owned_by_enterprise` (optional checkbox) and its two dependents `enterprise_name`/`form_of_ownership_id` (`required_if:owned_by_enterprise,1`, Alpine-bound `:required`), plus `scope_of_work_detail` (`required_if:scope_of_work,repair,others`) |

---

## Annual Inspection (AI) Module

| Feature | Status | Notes |
|---------|--------|-------|
| Application CRUD (walk-in) | DONE | `annual_inspection_applications` table + `AnnualInspectionApplication` model/controller; morph map `ai`; permit code `AI` (originally built + seeded as `MP`, renamed) |
| Application numbering | DONE | AI-YYYY-MM-NNNNN |
| AI form fields | DONE | Name of Owner/Lessee, Location (Street + Barangay), New/Yearly application_kind toggle, Character of Occupancy (single-select radio, added later — see below) |
| Status workflow (skips zoning) | DONE | Same 5-step shape as DP/SGP/FP: submitted → engineering_assessed → billed → paid → permit_generated → released |
| Original build: 5 equipment tabs + multi-permit generation | SUPERSEDED | First shipped as "Mechanical Permit" with AC/Machinery/Escalator/Elevator/Generator-Set tabs (reusing BP's `MECH_*`/`INSP_*` codes) and multi-permit generation (1 bundled AC cert + 1 permit per other equipment item, via `AnnualInspectionPermitUnit`) — fully replaced by the official-schedule rebuild below |
| Rebuilt around official Annual Inspection Fees schedule | DONE | Diffed the user's official rate document against 49 seeded `AINSP_*` rows; fixed a real bug (Floor Area's 2nd bracket + open-ended excess), rebuilt several rate tables as flat brackets or lump-sum-plus-excess, dropped 2 obsolete items, relabeled 1, added a new "Water, Sump and Sewage Pumps" code |
| `range_based` excess-formula bug fix | DONE (fixed) | `addAnnualInspectionFeeItem()` ignored `excess_every`, multiplying raw excess directly by `excess_fee` — broke Floor Area's "every 1,000 sq.m or portion thereof" rule; fixed with a `ceil()`-based formula, no-op for every other `excess_every=1` row |
| 4 official-schedule tabs (General/Electronics/Mechanical/Electrical) | DONE | `AINSP_GEN`/`AINSP_ELECTRONICS`/`AINSP_MECH` via `addAnnualInspectionFeeItem()`; `AINSP_ELEC` via `addAnnualInspectionElectricalItem()` (reuses BP's `ELEC_*` schedule) |
| 5 original equipment tabs removed | DONE (fixed) | `AI_AC`/`AI_MACH`/`AI_ESC`/`AI_ELEV`/`AI_GENSET` categories deleted from seeder + DB; `addAnnualInspectionUnitItem()` deleted; equipment-tab Blade branch removed |
| Permit Generation switched to single-permit (interim) | SUPERSEDED | Removing the equipment tabs broke the old multi-permit grouping logic; briefly thin-wrapped the shared `doGenerate`/`doRevertGenerate`/`doRestoreRevoke` like every other permit type — since replaced by the multi-certificate scheme below |
| Quantity (equipment count) field | DONE | 15 Mechanical + 3 Electrical measured-value fee codes (kW/ton/kVA/lineal meter/cu.m.) get a separate "Quantity" input; `amount = baseFee × quantity_count`, stored in `computation_details`; discrete-count codes (unit(s)/head(s)/etc.) unaffected |
| Unit/Qty table columns split | DONE | Assessment items table for the 4 AI tabs shows separate Unit (measurement + label, or label-only for discrete-count codes) and Qty (equipment count) columns instead of one ambiguous combined column; the same split was later extended to the shared Summary tab's per-category tables, which had been showing AI items with the old ambiguous single column |
| Assessment summary PDF | DONE (fixed) | `pdf/assessment-summary-ai.blade.php` — was still grouping sections by the module's original, since-deleted equipment categories (showed ₱0.00 everywhere); fixed to group by the 4 real `AINSP_*` categories |
| Multi-certificate permit generation | DONE | Reactivated from the original design: up to 6 certificates per application (General+Electrical, Electronics, Machinery, Aircon/Refrigeration — each 1 bundled cert — plus 1 cert per Elevator unit and 1 cert per Escalator/Funicular/Cable-Car unit), only generated for groups with assessed items. New `buildAiCertificateGroups()` derivation helper; `doGenerateAi()`/`revertGenerateAi()`/`restoreRevokeAi()` act on all-or-one-per-group rather than the shared single-permit methods |
| Final permit certificate PDF | DONE | `pdf/annual-inspection-permit.blade.php`, one certificate per print (parameterized by `AnnualInspectionPermitUnit`) — itemized table for bundle certs, single equipment line for per-unit certs, QR verification code |
| `AnnualInspectionPermitUnit` model/table | DONE (reactivated) | Was left dormant after the single-permit interim; now the active bridge table for the multi-certificate scheme, with a new `assessment_item_id` FK for per-unit certificates |
| Generated Permits panel restored | DONE | `annual-inspection-applications/show.blade.php` regained its multi-row Generated Permits panel; `permits/index.blade.php`'s `mechanical` type regained its multi-permit-aware UI ("N permit(s) generated", "View Permits") — both had been reverted to single-permit display during the interim single-permit period |
| "Equipment / Items to be Inspected" checklist | DONE | New optional section on the application form — a declared reference list (Elevators/Escalators/Aircon-Refrigeration/Other Machinery/Electronics Equipment, each row = fee code + Quantity + optional Specification) shown on the show page and as a read-only panel on the Assessment page; does not auto-generate assessment items. New `annual_inspection_equipment_items` table/`AnnualInspectionEquipmentItem` model; first live repeatable-row Alpine.js UI in the codebase (starts with zero rows so Equipment+Quantity can be `required` per row without blocking a no-equipment submission) |
| Sidebar entries | DONE | Main nav, Assessment flyout, Permits flyout — positioned last (after Fencing Permit) |
| Excluded from online self-service | DONE | Same as DP/SGP/FP |
| Mechanical assessment spec fields | DONE | AINSP_MECH add-item form on `/assessments/ai/{id}` captures category-specific specs, required per category: Elevator (Workload kg, No. of Passengers), Aircon/Refrigeration (Description, Tons or HP), Escalator/Funicular/Cable Car (8 fields: Rated Load, Capacity/Hr, Speed, Effective Width, Tread Width, Floors Served, Floor Height, Motor HP), Other Machinery (Description). Stored in `AssessmentItem.computation_details['specs']`; new code-set helpers on `AnnualInspectionEquipmentItem`; shown as an extra details row under each item in both the tab table and the Summary tab table. Not yet reflected on any printed PDF |
| Character of Occupancy (single-select) | DONE | Added to `/annual-inspection-applications/create`/edit, matching Building Permit's field but as a single-select radio group (not multi-select checkboxes) — reuses the existing polymorphic `application_occupancy_groups` table with no migration, writing exactly one row per application. `/annual-inspection-applications/{id}` shows Group and Subgroup as two separate labeled fields |
| Signatories: 15 Annual Inspection roles | DONE | Seeded 15 `ai_*` roles (Locational Zoning of Land Use, Line and Grade (Geodetic), Architectural, Civil/Structural, Electrical, Mechanical, Sanitary, Plumbing, Electronics, Interior Design, Accessibility, Fire Safety, and 3 Chief/City Engineer roles) into `/settings/signatories`, edit-only (a Create/Delete UI was built then explicitly reverted at the user's request — edit-only was deemed sufficient) |
| "General, Occupancy & Electrical" certificate rebuild | DONE | `pdf/annual-inspection-permit-ge.blade.php` — the GE certificate (only) replaced with a pixel-accurate one-page A4-landscape reproduction of the official NBC Form No. B-19, using the real form image as a DomPDF background (`public/images/forms/nbc-form-b19-hq.png`, a truecolor conversion fixing an original 256-color-palette banding issue) with an absolute-position text overlay auto-filled from the saved application/assessment/permit — Owner, Location, Character of Occupancy Group/Subgroup, the 12 discipline signatories, 2 Chief signature blocks, a centered Republic/Province/City letterhead with the official logo, and the Building Official signature block. Originally built as two A4-portrait pages, collapsed to one A4-landscape page (same `1:√2` aspect ratio, so a uniform `1/√2` scale + x-shift transform was used), with a scoped `dpi=200` override in `PermitController::print()` for this template only. Other AI certificate groups (ELN/MACH/ACREF/ELEV/ESC) still use the generic `pdf/annual-inspection-permit.blade.php` template, unaffected |
| GE certificate: performance fix | DONE | Background switched from the truecolor PNG (~0.88MB) to a flattened JPEG (quality 90) — the same DomPDF PNG-embedding slow path already fixed for every other background-overlay form in this app. Render time dropped 3.23s → 1.53s, no visible quality loss; `dpi=200` unchanged. Original PNG kept on disk as a calibration source |
| GE certificate: peso sign fix | DONE | `Fee Paid` showed a missing-glyph box instead of ₱ — DomPDF/Arial-substitute bug already diagnosed and fixed elsewhere in this app. Fixed by leading the font stack with `'DejaVu Sans'` |
| GE certificate: alignment/spacing pass | DONE | Several rounds of GD pixel-scan calibration: seal moved/shrunk to clear the page's pre-printed top border while staying above the letterhead; letterhead shifted down to restore a gap above "OFFICE OF THE BUILDING OFFICIAL"; Fee Paid/Official Receipt No./Date Paid/Date Issued/No./Name of Owner-Lessee/Character of Occupancy/Group/Located at-along each individually re-measured and nudged to sit cleanly above their blank lines. Found and fixed a real overlap bug along the way: the Group value's `left` position landed on top of the pre-printed "Group" label text |
| GE certificate: title + name signatory display | DONE | All 14 signatory blocks (12 discipline rows + 2 Chief blocks) previously showed name only, dropping each row's `title` (e.g. "Engr", "Arch", "SP"); now render `{title} {name}` via a new `$sigFull()` closure. Signature dates under the 2 Chief blocks and Building Official block removed at the user's request ("this is manually done") — left blank for hand-signing |
| GE certificate: signatories locked at generation time | DONE | All 14 discipline/Chief signatories were previously resolved via a live `Signatory` lookup on every print, so editing a signatory after generation silently changed already-generated certificates on reprint — unlike Building Official, already snapshotted. New `permits.signatories_snapshot` JSON column populated once in `doGenerateAi()`; `$sigFull()` reads it first, falling back to a live lookup only for permits generated before this column existed. Verified: editing a live signatory after generating a new permit left that permit's certificate unchanged, while an older (pre-lock) certificate correctly picked up the edit |
| GE certificate: verification QR code | DONE | Added a scannable QR code linking to the same public `/verify/permit/{token}` page every other permit type uses — no controller changes needed, `$qrImage` was already computed and passed to this template by `PermitController::print()`, just never rendered. Placed in a blank area (found via a GD blank-space scan) at the bottom-left of the right half, no caption, matching the Building Permit's QR styling; later moved right per follow-up to clear the page border |
| Annual Inspection: Occupancy No. / Issued Date fields | DONE | Two new optional fields, `occupancy_no` (string) and `occupancy_issued_date` (date), added to the AI create/edit form under Character of Occupancy, shown on the show page when set, and wired into the GE certificate's previously-permanently-blank "Certificate of Occupancy No. ___ issued on ___" line |
| Verify-permit page: permit type label bug fix | DONE | `/verify/permit/{token}` labeled every non-OP/DP/SGP permit "Building Permit" — its `match()` never listed FP or AI, both silently falling into the `default` case. Added `'FP' => 'Fencing Permit'` and `'AI' => 'Annual Inspection Permit'`; the FP gap was found and fixed incidentally while addressing the reported AI case |

---

## Building Permit Inspection Fee Removal

| Feature | Status | Notes |
|---------|--------|-------|
| Remove ELEC/MECH inspection-fee computation | DONE | `addElectricalItem()`/`addMechanicalItem()` now always store `inspection_fee = 0`; `resolveInspectionFee()` itself untouched (still used by AI) |
| Remove inspection-fee display | DONE | "Inspection" table columns dropped from both BP tabs; Summary tab's "Inspection Fees" row gated behind the AI-only flag; surcharge base-formula reference to `inspection_fee` simplified |
| Remove inspection-fee line items from BP Assessment Summary PDF | DONE | Electrical/Mechanical/Electronics inspection-fee rows removed from `pdf/assessment-summary.blade.php` |
| Retroactive backfill (`bp:remove-inspection-fees` Artisan command) | DONE | `--dry-run` mode + real run; zeroes existing `AssessmentItem.inspection_fee` for BP ELEC/MECH items and cascades the reduction through `Assessment.total_amount`, `Billing`/`BillingItem`, and (where safe) `Collection`/`CollectionDetail`, recomputing from source rather than delta-subtracting (incidentally fixing a pre-existing ELEC double-count bug) |

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
| Finalized lock | DONE | Add/remove/autocompute blocked after finalize; single amber banner |
| Zoning certification PDF | DONE | Template exists |
| Locational clearance PDF | DONE | Template exists |
| Revert zoning finalize / send back to editing | DONE | `revertZoning()`, `sendBackForEditing()` — password-confirmed |

---

## Fee Computation

| Feature | Status | Notes |
|---------|--------|-------|
| Fee schedule management | DONE | Categories/types/schedules CRUD |
| Zoning fee management | DONE | /settings/zoning-fees |
| All 6 computation methods | DONE | fixed, per_unit, range_based, cumulative_range, percentage (formula = PARTIAL) |
| Excess/min/max | DONE | |
| Construction fee data + tab | DONE | BOPMS-style: Part+Division+Area → auto lookup |
| Electrical fee data + tab | DONE | BOPMS-style: 7 types, range kVA |
| Electrical inspection fee (BP) | REMOVED | Was `assessment.electrical_inspection_percentage` setting (default 10%) — inspection fees no longer charged/displayed on the BP assessment; `inspection_fee` always stored as 0 for `ELEC` items now |
| Mechanical fee data + tab | DONE | BOPMS-style: equipment type+unit → auto base fee only |
| Mechanical NBC inspection fees (BP) | REMOVED | Was computed via `resolveInspectionFee()` against MECH_INSP's 29 INSP_* types / 55 schedules — no longer charged/displayed on the BP assessment; `inspection_fee` always stored as 0 for `MECH` items now. `resolveInspectionFee()` and the MECH_INSP schedule data themselves are untouched — still used by the Annual Inspection assessment |
| Mechanical inspection formulas (still used by AI) | DONE | flat (range-band), per_unit (rate×count), tiered (cumulative for elevators) — via `resolveInspectionFee()`, now exclusively invoked by the Annual Inspection module, not BP |
| BP assessment tab navigation | DONE | 8 tabs + Summary, badges, hidden MECH_INSP tab |
| Plumbing fee data | DONE | Seeded |
| Plumbing tab (BOPMS-style) | DONE | 22 PLUMB_* types, dynamic unit labels |
| Electronics fee data | DONE | Seeded |
| Electronics tab (BOPMS-style) | DONE | 11 ELECT_* types |
| Accessories tab (ACC_BLDG) | DONE | BOPMS-style |
| Accessory Fees tab (ACC_FEE) | DONE | BOPMS-style |
| Surcharge tab (SURCHARGE) | DONE | Percentage-based on violation stage |
| Occupancy fee data | DONE | Seeded |
| Occupancy fee tab — OP (BOPMS-style) | DONE | 8 OCC_* types, dynamic unit label (Costing/Area/Unit/Meters); range_based w/ excess_every, per_unit, percentage all verified |
| Assessment finalization lock | DONE | BP + zoning: add/remove/autocompute blocked after finalize (UI + server guards) |
| Finalize stays on Summary tab | DONE | Redirects to ?tab=SUMMARY |
| BP assessment PDF | DONE | Fire Code Fees removed; Code 128 barcode above BP number; Approved By from building_official signatory; city seal header, enlarged fonts |
| OP assessment PDF | DONE | Separate `assessment-summary-op` template titled "OCCUPANCY PERMIT ASSESSMENT"; only Occupancy Fees section (no Zoning/BP/Other Fees); city seal header, enlarged fonts |
| Print button on BP + OP assessment index | DONE | Shown when status = engineering_assessed or billed |
| Revert engineering finalize (BP + OP) | DONE | `revertEngineering()` / `revertEngineeringOp()` — password-confirmed un-finalize |
| Zoning fees missing from printed Summary of Computation | DONE (fixed) | Root cause: `fee_category_id` never set on zoning `AssessmentItem::create()` calls; fixed + backfilled |

---

## Payment / Treasury Module

| Feature | Status | Notes |
|---------|--------|-------|
| Payment collection (cash/check/online) | DONE | |
| Official receipt generation | DONE | PDF, unique OR number, city seal header |
| Void transaction | DONE | Password verify, void tracking; header button removed from /collections (route remains) |
| Collection history | DONE | "My Collections": scoped to logged-in collector, month filter (default current month) |
| Barcode scan / search on Collections | DONE | Exact app-number match → payment form; partial match filters list |
| Cash change display | DONE | Live Alpine calc; server rejects insufficient cash amount |
| No-scroll payment form redesign | DONE | POS-style 3-col amount strip, segmented payment mode, sticky action bar |
| Awaiting Payment already-paid exclusion | DONE | `whereDoesntHave('collections', active)` guard, in addition to `status = billed` |

---

## Billing

| Feature | Status | Notes |
|---------|--------|-------|
| Billing auto-generation | DONE | Auto on assessment finalize (BillingService::generateFor); BL-YYYY-MM-NNNNN; Billing menu/page removed |
| Billing number counter bug fix | DONE (fixed) | Counter was `count(billings this month) + 1` — collided with an existing (soft-deleted) number whenever the sequence had a gap, blocking finalize mid-transaction; now derived from the actual max existing number for the year/month prefix |
| Billing statement PDF | DONE | billing.print route kept; city seal + city/province from Settings |
| Billing status tracking | DONE | unpaid, partial, paid, void |

---

## Permit Generation

| Feature | Status | Notes |
|---------|--------|-------|
| Building permit PDF | DONE | NBC Form B-018 style — A4 landscape, city seal + DPWH logo header, thick bordered frame, QR verification code |
| Occupancy permit PDF | DONE | DPWH Certificate of Occupancy style — A4 landscape, DPWH logo + city seal, QR verification code |
| Permit numbering | DONE | CODE-YYYY-MM-NNNNN |
| QR code verification | DONE | `verification_token` (UUID) per permit; public `/verify/permit/{token}` page (no auth); `general.domain` setting controls the QR's domain |
| Generate Permit routing fix (OP) | DONE | Occupancy Permits list was posting to the BP-only generate route (404); now branches by `$type` |
| Revoke generated permit | DONE | `revertGenerate()` / `revertGenerateOp()` — tags `status = 'revoked'` + soft-delete, retains permit number, blocks regeneration; password-confirmed with required reason |
| Restore revoked permit | DONE | `restoreRevoke()` / `restoreRevokeOp()` — un-trashes the same Permit row, same number; password-confirmed only |
| Permits list filters + TTA + Permit No. column | DONE | `/permits/building`, `/permits/occupancy` — Search/Status(incl. Revoked)/Year filters; Permit No. as primary column; TTA beside Date |
| Building Official snapshot | DONE | Signatory captured on `Permit` at generation time; used by both PDFs + verification page; immune to later Signatory edits |
| Printed permit footer note | DONE | "Computer-generated permit. Printed on: {date} \| Printed by: {user}" on both BP/OP PDFs |
| Evaluation report PDF | DONE | City seal + Republic/city/province header |

---

## Reports

| Feature | Status | Notes |
|---------|--------|-------|
| Permit / Revenue / Collection reports | DONE | |
| Excel + PDF export | DONE | |
| Permit report status filter + Permit No./TTA columns | DONE | Filters to Permit Generated/Revoked only; combined app-date→permit-date range |
| Permit report peso sign fix | DONE | Switched PDF font to DejaVu Sans (bundled with DomPDF) — Helvetica/Arial lack the ₱ glyph |
| Audit Logs report | DONE | `/reports/audit-logs`, super-admin only (`view-audit-logs` permission); filters Spatie's `activity_log` by search/causer/subject type/event/month |

---

## Settings / Admin

| Feature | Status | Notes |
|---------|--------|-------|
| System settings | DONE | File settings: `general.logo`, `general.favicon`, `general.dpwh_logo`, `general.national_govt_logo` (GD-resized, per-key storage path); strings: `general.city`/`general.province` (real values seeded), `general.area_number`, `general.zip_code`, `general.domain` |
| User management | DONE | Create User: password now admin-set with complexity enforcement + strength UI (was hardcoded `password123`) |
| User management: role select / blank-field bug | PENDING | Create/Edit User form is currently unusable end-to-end — role `<select>` sends IDs but validation expects names; `User::create()` crashes if middle_name/phone/department/position are blank. Found during password-complexity work, tracked separately (not yet fixed) |
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
| Permit download | DONE | When status = released; now carries the same seal/DPWH logo/QR as the staff print path (previously rendered without them) |

---

## Dashboard

| Feature | Status | Notes |
|---------|--------|-------|
| KPI cards | DONE | Applications, pending, revenue — always reflect the live/current period; now aggregate all 6 permit types (BP/OP/DP/SGP/FP/AI), not just BP/OP |
| Monthly revenue chart | DONE | Chart.js; year-navigable via `?year=` (prev/next arrows, clamped to current year) |
| Monthly transactions chart | DONE | Grouped bar across all 6 permit types (was BP vs OP only), from `Collection.applicationable_type`; shares the same year navigator |
| Recent applications + daily count | DONE | Merges all 6 permit types' latest records into one combined, timestamp-sorted list with correct per-type links |

---

## Not Migrating from BOPMS

| Feature | Reason |
|---------|--------|
| BFP module (fire-safety assessment/inspection workflow) | Not included in this system. FSEC No./Date (BP, OP) and FSIC No. (OP) exist only as reference fields shown on printed permits — no BFP validation, workflow, or integration |
| DB-level encryption | Not required |
| BFP partial payment | BFP excluded |
