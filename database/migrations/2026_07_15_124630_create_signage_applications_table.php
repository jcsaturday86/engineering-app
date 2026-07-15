<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signage_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_type_id')->nullable()->constrained()->nullOnDelete();

            // Application number: SGP-YYYY-MM-NNNNN
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

            // Applicant address
            $table->foreignId('applicant_province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('applicant_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('applicant_barangay_id')->nullable()->constrained('barangays')->nullOnDelete();
            $table->string('applicant_street')->nullable();
            $table->string('applicant_zip_code', 10)->nullable();

            // Scope of Work
            $table->boolean('install')->default(false);
            $table->text('install_detail')->nullable();
            $table->boolean('attach')->default(false);
            $table->text('attach_detail')->nullable();
            $table->boolean('paint')->default(false);
            $table->text('paint_detail')->nullable();

            $table->text('wordings')->nullable();
            $table->string('premises_of')->nullable();

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
        Schema::dropIfExists('signage_applications');
    }
};
