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
                'value' => 'Sample Province',
                'type' => 'string',
                'description' => 'Province name',
            ],
            [
                'group' => 'general',
                'key' => 'general.city',
                'value' => 'Sample City',
                'type' => 'string',
                'description' => 'City or municipality name',
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
                'value' => 'images/logo.png',
                'type' => 'string',
                'description' => 'Path to LGU logo',
            ],
            [
                'group' => 'general',
                'key' => 'general.area_number',
                'value' => '3314-W',
                'type' => 'string',
                'description' => 'Area/district number code',
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
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
