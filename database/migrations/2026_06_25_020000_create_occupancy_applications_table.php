<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupancy_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_type_id')->constrained();

            // Application number: OP-YYYY-MM-NNNNN
            $table->year('app_year');
            $table->unsignedTinyInteger('app_month');
            $table->unsignedInteger('app_counter');
            $table->string('application_number', 30)->unique();
            $table->string('area_number', 20)->nullable();

            // Workflow state
            $table->string('status', 30)->default('draft');
            $table->string('source', 20)->default('walk_in');

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
            $table->date('applicant_date_signed')->nullable();

            // Enterprise / Ownership
            $table->string('enterprise_name')->nullable();
            $table->foreignId('form_of_ownership_id')->nullable()->constrained()->nullOnDelete();

            // Applicant address
            $table->foreignId('applicant_province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('applicant_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('applicant_barangay_id')->nullable()->constrained('barangays')->nullOnDelete();
            $table->string('applicant_street')->nullable();
            $table->string('applicant_zip_code', 10)->nullable();

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
            $table->string('occupancy_classified')->nullable();
            $table->decimal('total_floor_area', 15, 2)->nullable();
            $table->decimal('lot_area', 15, 2)->nullable();

            // Owner info
            $table->string('owner_name')->nullable();
            $table->string('owner_address')->nullable();
            $table->string('owner_govt_id')->nullable();
            $table->date('owner_id_date_issued')->nullable();
            $table->string('owner_id_place_issued')->nullable();
            $table->date('owner_date_signed')->nullable();

            // OP-specific fields
            $table->string('bp_number')->nullable();
            $table->date('bp_issued_date')->nullable();
            $table->string('fsec_no')->nullable();
            $table->date('fsec_issued_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->string('applies_for')->nullable();

            // Misc
            $table->text('remarks')->nullable();

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

            // Online application
            $table->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('issued_date')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index(['app_year', 'app_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occupancy_applications');
    }
};
