<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fencing_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_type_id')->nullable()->constrained()->nullOnDelete();

            // Application number: FP-YYYY-MM-NNNNN
            $table->year('app_year');
            $table->unsignedTinyInteger('app_month');
            $table->unsignedInteger('app_counter');
            $table->string('application_number', 30)->unique();

            // Workflow state
            $table->string('status', 30)->default('draft');
            $table->string('source', 20)->default('walk_in');

            // Applicant
            $table->string('applicant_first_name');
            $table->string('applicant_middle_name')->nullable();
            $table->string('applicant_last_name');
            $table->string('applicant_tin')->nullable();
            $table->string('applicant_telephone')->nullable();

            // Enterprise / Ownership
            $table->boolean('owned_by_enterprise')->default(false);
            $table->string('enterprise_name')->nullable();
            $table->foreignId('form_of_ownership_id')->nullable()->constrained()->nullOnDelete();

            // Applicant address
            $table->foreignId('applicant_province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('applicant_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('applicant_barangay_id')->nullable()->constrained('barangays')->nullOnDelete();
            $table->string('applicant_street')->nullable();
            $table->string('applicant_zip_code', 10)->nullable();

            // Location of Construction
            $table->string('lot_no')->nullable();
            $table->string('block_no')->nullable();
            $table->string('tct_no')->nullable();
            $table->string('tax_dec_no')->nullable();
            $table->string('construction_street')->nullable();
            $table->foreignId('construction_barangay_id')->nullable()->constrained('barangays')->nullOnDelete();

            // Scope of Work: new_construction | erection | addition | repair | others
            $table->string('scope_of_work', 20)->nullable();
            $table->text('scope_of_work_detail')->nullable();

            // Design Professional, Plans and Specifications
            $table->string('design_professional_name')->nullable();
            $table->string('design_professional_address')->nullable();
            $table->string('design_professional_prc_no')->nullable();
            $table->date('design_professional_prc_validity')->nullable();
            $table->string('design_professional_ptr_no')->nullable();
            $table->date('design_professional_ptr_date_issued')->nullable();
            $table->string('design_professional_ptr_issued_at')->nullable();
            $table->string('design_professional_tin')->nullable();

            // Consent of Lot Owner
            $table->string('owner_name')->nullable();
            $table->string('owner_address')->nullable();
            $table->string('owner_ctc_no')->nullable();
            $table->date('owner_ctc_date_issued')->nullable();
            $table->string('owner_ctc_issued_at')->nullable();

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

            // Online application (future use)
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
        Schema::dropIfExists('fencing_applications');
    }
};
