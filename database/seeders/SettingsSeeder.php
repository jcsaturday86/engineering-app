<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General settings
            [
                'group' => 'general',
                'key' => 'general.lgu_name',
                'value' => 'City Government',
                'type' => 'string',
                'description' => 'Short name of the local government unit',
            ],
            [
                'group' => 'general',
                'key' => 'general.lgu_full_name',
                'value' => 'City Government of Sample City',
                'type' => 'string',
                'description' => 'Full name of the local government unit',
            ],
            [
                'group' => 'general',
                'key' => 'general.province',
                'value' => 'La Union',
                'type' => 'string',
                'description' => 'Province name',
            ],
            [
                'group' => 'general',
                'key' => 'general.city',
                'value' => 'City of San Fernando',
                'type' => 'string',
                'description' => 'City or municipality name',
            ],
            [
                'group' => 'general',
                'key' => 'general.zip_code',
                'value' => '',
                'type' => 'string',
                'description' => 'ZIP code shown on printed Building Permit',
            ],
            [
                'group' => 'general',
                'key' => 'general.domain',
                'value' => '',
                'type' => 'string',
                'description' => 'Public domain used to build the QR code verification link on printed permits (e.g. https://permits.sanfernando.gov.ph). Leave blank to use the app URL.',
            ],
            [
                'group' => 'general',
                'key' => 'general.address',
                'value' => 'City Hall, Sample City',
                'type' => 'string',
                'description' => 'Office address',
            ],
            [
                'group' => 'general',
                'key' => 'general.contact',
                'value' => '(000) 000-0000',
                'type' => 'string',
                'description' => 'Contact number',
            ],
            [
                'group' => 'general',
                'key' => 'general.email',
                'value' => 'engineering@lgu.gov.ph',
                'type' => 'string',
                'description' => 'Office email address',
            ],
            [
                'group' => 'general',
                'key' => 'general.logo',
                'value' => '',
                'type' => 'file',
                'description' => 'City/LGU official seal or logo (PNG/JPG) — printed on permit PDFs',
            ],
            [
                'group' => 'general',
                'key' => 'general.favicon',
                'value' => '',
                'type' => 'file',
                'description' => 'Browser tab icon (PNG/JPG) — shown on every page. Falls back to the Official Logo if not set.',
            ],
            [
                'group' => 'general',
                'key' => 'general.dpwh_logo',
                'value' => '',
                'type' => 'file',
                'description' => 'DPWH logo (PNG/JPG) — printed on the Occupancy Permit PDF',
            ],
            [
                'group' => 'general',
                'key' => 'general.national_govt_logo',
                'value' => '',
                'type' => 'file',
                'description' => 'National Government logo (PNG/JPG) — printed on the left of the Occupancy Permit Application Form',
            ],
            [
                'group' => 'general',
                'key' => 'general.area_number',
                'value' => '3314-W',
                'type' => 'string',
                'description' => 'Area/district number code',
            ],
            [
                'group' => 'general',
                'key' => 'general.planning_office_name',
                'value' => 'CITY PLANNING & DEVELOPMENT OFFICE',
                'type' => 'string',
                'description' => 'Planning office name printed on the Zoning Certification PDF header',
            ],
            [
                'group' => 'general',
                'key' => 'general.planning_office_address',
                'value' => 'Second Floor, City Hall Annex Building',
                'type' => 'string',
                'description' => 'Planning office address printed on the Zoning Certification PDF header',
            ],
            [
                'group' => 'general',
                'key' => 'general.planning_office_telephone',
                'value' => '(072) 888-69-01 Local 120',
                'type' => 'string',
                'description' => 'Planning office telephone number printed on the Zoning Certification PDF header',
            ],

            // Permit prefix settings
            [
                'group' => 'permits',
                'key' => 'permits.bp_prefix',
                'value' => 'BP',
                'type' => 'string',
                'description' => 'Building permit number prefix',
            ],
            [
                'group' => 'permits',
                'key' => 'permits.op_prefix',
                'value' => 'OP',
                'type' => 'string',
                'description' => 'Occupancy permit number prefix',
            ],
            [
                'group' => 'permits',
                'key' => 'permits.or_prefix',
                'value' => 'OR',
                'type' => 'string',
                'description' => 'Official receipt number prefix',
            ],
            [
                'group' => 'permits',
                'key' => 'permits.billing_prefix',
                'value' => 'BL',
                'type' => 'string',
                'description' => 'Billing statement number prefix',
            ],

            // Assessment settings
            [
                'group' => 'assessment',
                'key' => 'assessment.default_filing_fee',
                'value' => '0',
                'type' => 'decimal',
                'description' => 'Default filing fee amount',
            ],
            [
                'group' => 'assessment',
                'key' => 'assessment.default_processing_fee',
                'value' => '0',
                'type' => 'decimal',
                'description' => 'Default processing fee amount',
            ],
            [
                'group' => 'assessment',
                'key' => 'assessment.electrical_inspection_percentage',
                'value' => '10',
                'type' => 'decimal',
                'description' => 'Electrical inspection fee percentage (e.g. 10 = 10% of fee amount)',
            ],
            [
                'group' => 'assessment',
                'key' => 'assessment.mechanical_inspection_percentage',
                'value' => '10',
                'type' => 'decimal',
                'description' => 'Mechanical inspection fee percentage (e.g. 10 = 10% of base amount)',
            ],
        ];

        foreach ($settings as $setting) {
            if ($setting['type'] === 'file') {
                // File settings are uploaded via the Settings UI — never overwrite an
                // already-uploaded value with the seeder's empty default on re-run.
                Setting::firstOrCreate(['key' => $setting['key']], $setting);
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Remove legacy mech_insp.* JSON settings — rates are now in fee_schedules table.
        Setting::where('group', 'mech_insp')->delete();
    }
}
