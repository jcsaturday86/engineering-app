<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get the OP permit type ID
        $opPermitTypeId = DB::table('permit_types')->where('code', 'OP')->value('id');

        if (! $opPermitTypeId) {
            return;
        }

        $opApplications = DB::table('applications')
            ->where('permit_type_id', $opPermitTypeId)
            ->whereNull('deleted_at')
            ->get();

        if ($opApplications->isEmpty()) {
            return;
        }

        $sharedColumns = [
            'application_type_id', 'app_year', 'app_month', 'app_counter',
            'application_number', 'area_number', 'status', 'source',
            'applicant_first_name', 'applicant_middle_name', 'applicant_last_name',
            'applicant_suffix', 'applicant_tin', 'applicant_contact_no',
            'applicant_email', 'applicant_govt_id', 'applicant_id_date_issued',
            'applicant_id_place_issued', 'applicant_date_signed',
            'enterprise_name', 'form_of_ownership_id',
            'applicant_province_id', 'applicant_city_id', 'applicant_barangay_id',
            'applicant_street', 'applicant_zip_code',
            'lot_no', 'block_no', 'tct_no', 'tax_dec_no',
            'land_classification_id', 'building_street', 'building_barangay_id',
            'no_of_storeys', 'no_of_units', 'occupancy_classified',
            'total_floor_area', 'lot_area',
            'owner_name', 'owner_address', 'owner_govt_id',
            'owner_id_date_issued', 'owner_id_place_issued', 'owner_date_signed',
            'bp_number', 'bp_issued_date', 'fsec_no', 'fsec_issued_date',
            'completion_date', 'applies_for',
            'remarks',
            'entered_by', 'assessed_by', 'approved_by',
            'submitted_at', 'assessed_at', 'approved_at', 'paid_at',
            'released_at', 'cancelled_at', 'cancellation_reason',
            'client_user_id', 'issued_date',
            'created_at', 'updated_at',
        ];

        $downstreamTables = [
            'assessments',
            'billings',
            'collections',
            'permits',
            'documents',
            'application_requirements',
            'application_occupancy_groups',
        ];

        foreach ($opApplications as $opApp) {
            // Build the insert data from shared columns
            $insertData = [];
            foreach ($sharedColumns as $col) {
                $insertData[$col] = $opApp->$col ?? null;
            }

            // Insert into occupancy_applications
            $newId = DB::table('occupancy_applications')->insertGetId($insertData);

            // Update downstream tables: point to new OP record
            foreach ($downstreamTables as $tableName) {
                DB::table($tableName)
                    ->where('application_id', $opApp->id)
                    ->update([
                        'applicationable_type' => 'op',
                        'applicationable_id' => $newId,
                    ]);
            }

            // Soft-delete the old OP row in applications
            DB::table('applications')
                ->where('id', $opApp->id)
                ->update(['deleted_at' => now()]);
        }
    }

    public function down(): void
    {
        // Restore: un-soft-delete OP rows in applications, remove from occupancy_applications
        $opPermitTypeId = DB::table('permit_types')->where('code', 'OP')->value('id');

        if (! $opPermitTypeId) {
            return;
        }

        // Restore soft-deleted OP rows
        DB::table('applications')
            ->where('permit_type_id', $opPermitTypeId)
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);

        // Revert downstream tables back to bp type with original application_id
        $downstreamTables = [
            'assessments', 'billings', 'collections', 'permits',
            'documents', 'application_requirements', 'application_occupancy_groups',
        ];

        foreach ($downstreamTables as $tableName) {
            $opRows = DB::table($tableName)->where('applicationable_type', 'op')->get();
            foreach ($opRows as $row) {
                $opApp = DB::table('occupancy_applications')->find($row->applicationable_id);
                if ($opApp) {
                    $originalApp = DB::table('applications')
                        ->where('application_number', $opApp->application_number)
                        ->first();
                    if ($originalApp) {
                        DB::table($tableName)->where('id', $row->id)->update([
                            'applicationable_type' => 'bp',
                            'applicationable_id' => $originalApp->id,
                            'application_id' => $originalApp->id,
                        ]);
                    }
                }
            }
        }

        DB::table('occupancy_applications')->truncate();
    }
};
