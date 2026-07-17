<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mechanical_applications', function (Blueprint $table) {
            $table->id();

            // Application number: MP-YYYY-MM-NNNNN
            $table->year('app_year');
            $table->unsignedTinyInteger('app_month');
            $table->unsignedInteger('app_counter');
            $table->string('application_number', 30)->unique();

            // Workflow state
            $table->string('status', 30)->default('draft');
            $table->string('source', 20)->default('walk_in');

            // New vs Yearly (annual re-inspection)
            $table->string('application_kind', 10)->default('new');

            // Owner / Lessee
            $table->string('owner_name');

            // Location Address
            $table->string('location_street')->nullable();
            $table->foreignId('location_barangay_id')->nullable()->constrained('barangays')->nullOnDelete();

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
        Schema::dropIfExists('mechanical_applications');
    }
};
