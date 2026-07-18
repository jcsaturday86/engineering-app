<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('mechanical_applications', 'annual_inspection_applications');
        Schema::rename('mechanical_permit_units', 'annual_inspection_permit_units');

        DB::statement('ALTER TABLE annual_inspection_permit_units RENAME COLUMN mechanical_application_id TO annual_inspection_application_id');

        DB::table('permit_types')->where('code', 'MP')->update([
            'code' => 'AI',
            'name' => 'Annual Inspection',
        ]);

        foreach ([
            'MP_AC' => 'AI_AC',
            'MP_MACH' => 'AI_MACH',
            'MP_ESC' => 'AI_ESC',
            'MP_ELEV' => 'AI_ELEV',
            'MP_GENSET' => 'AI_GENSET',
        ] as $old => $new) {
            DB::table('fee_categories')->where('code', $old)->update(['code' => $new]);
        }

        foreach (['assessments', 'permits', 'collections', 'billings'] as $table) {
            DB::table($table)->where('applicationable_type', 'mp')->update(['applicationable_type' => 'ai']);
        }

        DB::table('annual_inspection_applications')->where('application_number', 'like', 'MP-%')
            ->update(['application_number' => DB::raw("CONCAT('AI-', SUBSTRING(application_number, 4))")]);

        DB::table('permits')->where('permit_number', 'like', 'MP-%')
            ->update(['permit_number' => DB::raw("CONCAT('AI-', SUBSTRING(permit_number, 4))")]);
    }

    public function down(): void
    {
        DB::table('permits')->where('permit_number', 'like', 'AI-%')
            ->update(['permit_number' => DB::raw("CONCAT('MP-', SUBSTRING(permit_number, 4))")]);

        DB::table('annual_inspection_applications')->where('application_number', 'like', 'AI-%')
            ->update(['application_number' => DB::raw("CONCAT('MP-', SUBSTRING(application_number, 4))")]);

        foreach (['assessments', 'permits', 'collections', 'billings'] as $table) {
            DB::table($table)->where('applicationable_type', 'ai')->update(['applicationable_type' => 'mp']);
        }

        foreach ([
            'AI_AC' => 'MP_AC',
            'AI_MACH' => 'MP_MACH',
            'AI_ESC' => 'MP_ESC',
            'AI_ELEV' => 'MP_ELEV',
            'AI_GENSET' => 'MP_GENSET',
        ] as $old => $new) {
            DB::table('fee_categories')->where('code', $old)->update(['code' => $new]);
        }

        DB::table('permit_types')->where('code', 'AI')->update([
            'code' => 'MP',
            'name' => 'Mechanical Permit',
        ]);

        DB::statement('ALTER TABLE annual_inspection_permit_units RENAME COLUMN annual_inspection_application_id TO mechanical_application_id');

        Schema::rename('annual_inspection_permit_units', 'mechanical_permit_units');
        Schema::rename('annual_inspection_applications', 'mechanical_applications');
    }
};
