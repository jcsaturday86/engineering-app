<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Complexity
            $table->string('complexity', 20)->nullable()->after('application_type_id');
            // Applies To (comma-separated: NA, LC, FS)
            $table->string('applies_to')->nullable()->after('complexity');

            // Occupancy Classified
            $table->string('occupancy_classified')->nullable()->after('no_of_units');

            // Equipment costs (replace single other_equipment_cost with 4 separate)
            $table->decimal('equipment_cost_1', 15, 2)->default(0)->after('other_equipment_cost');
            $table->decimal('equipment_cost_2', 15, 2)->default(0)->after('equipment_cost_1');
            $table->decimal('equipment_cost_3', 15, 2)->default(0)->after('equipment_cost_2');
            $table->decimal('equipment_cost_4', 15, 2)->default(0)->after('equipment_cost_3');

            // Applicant signing
            $table->date('applicant_date_signed')->nullable()->after('applicant_id_place_issued');

            // Owner place issued
            $table->string('owner_id_place_issued')->nullable()->after('owner_id_date_issued');

            // PEE (Professional Electrical Engineer)
            $table->string('pee_name')->nullable()->after('total_generator_capacity');
            $table->string('pee_prc_no')->nullable()->after('pee_name');
            $table->date('pee_prc_validity')->nullable()->after('pee_prc_no');
            $table->date('pee_date_signed')->nullable()->after('pee_prc_validity');
            $table->string('pee_ptr_no')->nullable()->after('pee_date_signed');
            $table->date('pee_ptr_date_issued')->nullable()->after('pee_ptr_no');
            $table->string('pee_ptr_issued_at')->nullable()->after('pee_ptr_date_issued');
            $table->string('pee_address')->nullable()->after('pee_ptr_issued_at');
            $table->string('pee_tin')->nullable()->after('pee_address');

            // SEW (Supervisor of Electrical Works)
            $table->string('sew_profession')->nullable()->after('pee_tin');
            $table->string('sew_name')->nullable()->after('sew_profession');
            $table->string('sew_prc_no')->nullable()->after('sew_name');
            $table->date('sew_prc_validity')->nullable()->after('sew_prc_no');
            $table->date('sew_date_signed')->nullable()->after('sew_prc_validity');
            $table->string('sew_ptr_no')->nullable()->after('sew_date_signed');
            $table->date('sew_ptr_date_issued')->nullable()->after('sew_ptr_no');
            $table->string('sew_ptr_issued_at')->nullable()->after('sew_ptr_date_issued');
            $table->string('sew_address')->nullable()->after('sew_ptr_issued_at');
            $table->string('sew_tin')->nullable()->after('sew_address');

            // Occupancy permit specific
            $table->string('fsec_no')->nullable()->after('bp_issued_date');
            $table->date('fsec_issued_date')->nullable()->after('fsec_no');
            $table->string('applies_for')->nullable()->after('fsec_issued_date');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'complexity', 'applies_to', 'occupancy_classified',
                'equipment_cost_1', 'equipment_cost_2', 'equipment_cost_3', 'equipment_cost_4',
                'applicant_date_signed', 'owner_id_place_issued',
                'pee_name', 'pee_prc_no', 'pee_prc_validity', 'pee_date_signed',
                'pee_ptr_no', 'pee_ptr_date_issued', 'pee_ptr_issued_at', 'pee_address', 'pee_tin',
                'sew_profession', 'sew_name', 'sew_prc_no', 'sew_prc_validity', 'sew_date_signed',
                'sew_ptr_no', 'sew_ptr_date_issued', 'sew_ptr_issued_at', 'sew_address', 'sew_tin',
                'fsec_no', 'fsec_issued_date', 'applies_for',
            ]);
        });
    }
};
