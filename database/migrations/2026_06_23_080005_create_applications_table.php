<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_type_id')->constrained();
            $table->foreignId('application_type_id')->constrained();

            // Application number: YYYY-MM-NNNNN
            $table->year('app_year');
            $table->unsignedTinyInteger('app_month');
            $table->unsignedInteger('app_counter');
            $table->string('application_number', 30)->unique();
            $table->string('area_number', 20)->nullable();

            // Workflow state
            $table->string('status', 30)->default('draft');
            // draft -> submitted -> zoning_assessed -> engineering_assessed -> billed -> paid -> permit_generated -> released
            // cancelled at any point

            $table->string('source', 20)->default('walk_in'); // walk_in, online

            // Applicant
            $table->string('applicant_first_name');
            $table->string('applicant_middle_name')->nullable();
            $table->string('applicant_last_name');
            $table->string('applicant_suffix')->nullable();
            $table->string('applicant_tin')->nullable();
            $table->string('applicant_contact_no')->nullable();
            $table->string('applicant_email')->nullable();
            $table->string('applicant_govt_id')->nullable();
            $table->date('applicant_id_date_issued')->nullable();
            $table->string('applicant_id_place_issued')->nullable();

            // Enterprise / Ownership
            $table->string('enterprise_name')->nullable();
            $table->foreignId('form_of_ownership_id')->nullable()->constrained()->nullOnDelete();

            // Applicant address
            $table->foreignId('applicant_province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('applicant_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('applicant_barangay_id')->nullable()->constrained('barangays')->nullOnDelete();
            $table->string('applicant_street')->nullable();
            $table->string('applicant_zip_code', 10)->nullable();

            // Project details
            $table->string('project_title')->nullable();
            $table->foreignId('scope_of_work_id')->nullable()->constrained()->nullOnDelete();
            $table->text('scope_of_work_details')->nullable();

            // Building location
            $table->string('lot_no')->nullable();
            $table->string('block_no')->nullable();
            $table->string('tct_no')->nullable();
            $table->string('tax_dec_no')->nullable();
            $table->foreignId('land_classification_id')->nullable()->constrained()->nullOnDelete();
            $table->string('building_street')->nullable();
            $table->foreignId('building_barangay_id')->nullable()->constrained('barangays')->nullOnDelete();

            // Building specs
            $table->unsignedInteger('no_of_storeys')->nullable();
            $table->unsignedInteger('no_of_units')->nullable();
            $table->decimal('total_floor_area', 15, 2)->nullable();
            $table->decimal('lot_area', 15, 2)->nullable();

            // Cost estimates
            $table->decimal('building_cost', 15, 2)->default(0);
            $table->decimal('electrical_cost', 15, 2)->default(0);
            $table->decimal('mechanical_cost', 15, 2)->default(0);
            $table->decimal('electronics_cost', 15, 2)->default(0);
            $table->decimal('plumbing_cost', 15, 2)->default(0);
            $table->decimal('other_equipment_cost', 15, 2)->default(0);
            $table->decimal('total_estimated_cost', 15, 2)->default(0);

            // Timeline
            $table->date('proposed_construction_date')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->text('remarks')->nullable();

            // For occupancy permits
            $table->string('bp_number')->nullable();
            $table->date('bp_issued_date')->nullable();
            $table->date('completion_date')->nullable();

            // Engineer / Architect info
            $table->string('engineer_name')->nullable();
            $table->string('engineer_prc_no')->nullable();
            $table->date('engineer_prc_validity')->nullable();
            $table->string('engineer_ptr_no')->nullable();
            $table->date('engineer_ptr_date_issued')->nullable();
            $table->string('engineer_ptr_issued_at')->nullable();
            $table->string('engineer_tin')->nullable();
            $table->string('engineer_address')->nullable();
            $table->date('engineer_date_signed')->nullable();

            // Owner info
            $table->string('owner_name')->nullable();
            $table->string('owner_address')->nullable();
            $table->string('owner_govt_id')->nullable();
            $table->date('owner_id_date_issued')->nullable();
            $table->date('owner_date_signed')->nullable();

            // Electrical permit data
            $table->boolean('include_electrical')->default(false);
            $table->decimal('total_connected_load', 15, 4)->nullable();
            $table->decimal('total_transformer_capacity', 15, 4)->nullable();
            $table->decimal('total_generator_capacity', 15, 4)->nullable();

            // Processing metadata
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            // Online application extras
            $table->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('issued_date')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['permit_type_id', 'status']);
            $table->index(['app_year', 'app_month']);
            $table->index('status');
        });

        Schema::create('application_occupancy_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occupancy_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occupancy_sub_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('others_text')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'occupancy_sub_group_id'], 'app_occ_subgroup_unique');
        });

        Schema::create('application_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('requirement_name');
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('reviewer_remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_requirements');
        Schema::dropIfExists('application_occupancy_groups');
        Schema::dropIfExists('applications');
    }
};
