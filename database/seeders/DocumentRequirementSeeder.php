<?php

namespace Database\Seeders;

use App\Models\DocumentRequirement;
use App\Models\PermitType;
use Illuminate\Database\Seeder;

/**
 * Official Engineering Office document requirements per service.
 *
 * Idempotent — keyed on permit_type_id + name, so re-running (including via the
 * self-healing boot path) updates rather than duplicates. Staff can freely edit,
 * add, reorder, deactivate or delete rows afterwards in Settings → Document
 * Requirements; anything they add is left untouched by a re-run.
 *
 * Levels: entries whose official text carries a parenthetical condition are
 * seeded 'conditional' with that parenthetical moved into condition_note;
 * everything else is 'mandatory'. These are starting points, adjustable in the UI.
 *
 * Annual Inspection (AI) intentionally has no rows — the office has not defined
 * any yet. It is NOT special-cased anywhere in code: it appears in the settings
 * UI like every other service and works the moment rows are added.
 */
class DocumentRequirementSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->requirements() as $permitTypeCode => $rows) {
            $permitType = PermitType::where('code', $permitTypeCode)->first();

            if (! $permitType) {
                $this->command?->warn("Permit type {$permitTypeCode} not found — skipping its requirements.");
                continue;
            }

            $this->seedRows($permitType->id, $rows, null, 0);
        }
    }

    /**
     * Recursively upsert a level of requirements, preserving declaration order.
     */
    private function seedRows(int $permitTypeId, array $rows, ?int $parentId, int $startSort): void
    {
        $sort = $startSort;

        foreach ($rows as $row) {
            $record = DocumentRequirement::updateOrCreate(
                [
                    'permit_type_id' => $permitTypeId,
                    'name' => $row['name'],
                ],
                [
                    'parent_id' => $parentId,
                    'condition_note' => $row['note'] ?? null,
                    'requirement_level' => $row['level'] ?? 'mandatory',
                    'is_uploadable' => $row['uploadable'] ?? true,
                    'sort_order' => $sort,
                ]
            );

            $sort++;

            if (! empty($row['children'])) {
                $this->seedRows($permitTypeId, $row['children'], $record->id, 0);
            }
        }
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function requirements(): array
    {
        // Shorthand for the recurring "professional documents" child sets.
        $conditional = fn (string $name, string $note) => [
            'name' => $name,
            'note' => $note,
            'level' => 'conditional',
        ];

        return [
            'BP' => [
                ['name' => 'Unified Application Form for Building Permit and FSEC (Signed and Sealed)'],
                ['name' => 'Certified True Copy of Original Certificate of Title (OCT) / Transfer Certificate of Title (TCT)'],
                ['name' => 'Certified True Copy of Latest Tax Declaration'],
                ['name' => 'Current Real Property Tax Receipt'],
                $conditional(
                    'Duly notarized copy of Affidavit of Consent from the lot owner',
                    'In case the applicant is not the registered owner of the lot.'
                ),
                $conditional(
                    'Duly notarized copy of the Deed of Absolute Sale',
                    'In case the Tax Declaration / Original Certificate of Title / Transfer Certificate of Title is not yet transferred to the applicant.'
                ),
                $conditional(
                    'Duly notarized copy of the Contract of Lease',
                    'In case the applicant is renting the space / building.'
                ),
                ['name' => 'Barangay Building Permit Clearance'],
                [
                    'name' => 'Survey plans, design plans, specifications and other documents prepared, signed and sealed by the duly licensed and registered professionals',
                    'note' => 'If plans are needed for loan purposes.',
                    'uploadable' => false,
                    'children' => [
                        ['name' => 'Architectural Documents'],
                        ['name' => 'Civil/Structural Documents'],
                        ['name' => 'Electrical Documents'],
                        ['name' => 'Mechanical Documents'],
                        ['name' => 'Sanitary Documents'],
                        ['name' => 'Plumbing Documents'],
                        ['name' => 'Electronics Documents'],
                        ['name' => 'Geodetic Documents'],
                    ],
                ],
                [
                    'name' => 'Fire Protection Plan',
                    'note' => 'If applicable.',
                    'level' => 'conditional',
                    'children' => [
                        ['name' => 'Automatic Fire Suppression System', 'level' => 'conditional'],
                        ['name' => 'Kitchen Hood Suppression', 'level' => 'conditional'],
                        ['name' => 'Fire Detection & Alarm System', 'level' => 'conditional'],
                        ['name' => 'Wet Stand Pipe', 'level' => 'conditional'],
                        ['name' => 'Dry Stand Pipe', 'level' => 'conditional'],
                    ],
                ],
                ['name' => 'Photocopy of Valid License (PRC I.D.) and Valid PTR of all involved professionals'],
                ['name' => 'Notarized Bill of Materials and Cost Estimates of the building/structure to be erected as declared by the owner, with sign and seal of design professional'],
                ['name' => 'Technical Specifications signed and sealed by design professional'],
                $conditional(
                    'Structural Design Computation and Analysis with sign and seal of design professional',
                    'For 2-storey and above.'
                ),
                $conditional(
                    'Soil Analysis / Boring Test',
                    'For 3-storey and above.'
                ),
                ['name' => 'Construction Safety and Health Program (CSHP)'],
                ['name' => 'Clearances from other agencies exercising and enforcing regulatory functions affecting buildings/structures'],
            ],

            'FP' => [
                ['name' => 'Filled-Up Unified Application Form for Building Permit (Signed and Sealed)'],
                ['name' => 'Certified True Copy of Original Certificate of Title (OCT) / Transfer Certificate of Title (TCT)'],
                ['name' => 'Certified True Copy of Latest Tax Declaration'],
                ['name' => 'Current Real Property Tax Receipt'],
                $conditional(
                    'Duly notarized copy of Affidavit of Adjoining Owners',
                    'In case the property has no OCT or TCT.'
                ),
                $conditional(
                    'Duly notarized copy of Affidavit of Consent from the lot owner',
                    'In case the applicant is not the registered owner of the lot.'
                ),
                $conditional(
                    'Duly notarized copy of the Deed of Absolute Sale',
                    'In case the Tax Declaration / Original Certificate of Title / Transfer Certificate of Title is not yet transferred to the applicant.'
                ),
                $conditional(
                    'Duly notarized copy of the Contract of Lease',
                    'In case the applicant is renting the property.'
                ),
                ['name' => 'Barangay Fencing Permit Clearance'],
                [
                    'name' => 'Survey plans, design plans, specifications and other documents prepared, signed and sealed by the duly licensed and registered professionals',
                    'note' => 'If plans are needed for loan purposes.',
                    'uploadable' => false,
                    'children' => [
                        ['name' => 'Architectural Documents'],
                        ['name' => 'Civil/Structural Documents'],
                        ['name' => 'Electrical Documents'],
                        ['name' => 'Sanitary/Plumbing Documents'],
                    ],
                ],
                ['name' => 'Photocopy of Valid License (PRC I.D.) and Valid PTR of all involved professionals'],
                ['name' => 'Notarized Bill of Materials and Cost Estimates of the building/structure to be erected as declared by the owner, with sign and seal of design professional'],
                ['name' => 'Technical Specifications signed and sealed by design professional'],
                ['name' => 'Clearances from other agencies exercising and enforcing regulatory functions affecting buildings/structures'],
            ],

            'OP' => [
                ['name' => 'Filled-up Unified Application Form for Certificate of Occupancy and FSIC'],
                ['name' => 'Duly notarized Certificate of Completion form, signed by the owner/applicant and signed and sealed by the duly licensed Professional in-charge of construction'],
                ['name' => 'Photocopy of the Issued Building Permit, Issued Unified Form, Architectural Permit Form, Civil/Structural Permit Form, Electrical Permit Form, Sanitary/Plumbing Permit Form, Mechanical Permit Form, Electronics Permit Form and FSEC with checklist'],
                $conditional(
                    'AS-BUILT Plan',
                    'If the building/structure is not the same as the original approved plan.'
                ),
                ['name' => 'Photocopy of Valid License (PRC I.D.) and Valid PTR of all involved professionals'],
                ['name' => 'Photograph of the completed structure showing front, sides, and rear areas and interior'],
            ],

            'DP' => [
                ['name' => 'Certified true copy of Building Tax Declaration or copy of Approved Building Permit'],
                ['name' => 'Current Real Property Tax Receipt'],
                ['name' => 'Barangay Demolition Permit'],
                ['name' => 'Pictures of building to demolish'],
                ['name' => 'Sketch Plan / Floor Plan (signed & sealed)'],
            ],

            'SGP' => [
                ['name' => 'Barangay Signage Permit'],
                ['name' => 'Sketch Plan / Floor Plan (signed & sealed)'],
                $conditional('DPWH Highway Clearance', 'If along a National Highway.'),
                $conditional('PEO Highway Clearance', 'If along a Provincial road.'),
                $conditional('LUECO Clearance Certificate', 'If near a distribution line.'),
            ],

            // No requirements defined by the office yet. Left deliberately empty
            // rather than omitted — AI is managed through the same settings UI.
            'AI' => [],
        ];
    }
}
