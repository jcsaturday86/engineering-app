<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('assessment_type', 30); // building, occupancy, zoning
            $table->decimal('filing_fee', 15, 2)->default(0);
            $table->decimal('processing_fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('draft'); // draft, finalized
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['application_id', 'assessment_type']);
        });

        Schema::create('assessment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fee_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fee_code', 50);
            $table->string('description');
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_fee', 15, 4)->default(0);
            $table->decimal('excess_fee', 15, 4)->default(0);
            $table->decimal('inspection_fee', 15, 4)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->json('computation_details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['assessment_id', 'fee_code']);
        });

        // Zoning-specific assessment details
        Schema::create('zoning_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('project_lifespan')->nullable();
            $table->string('project_significance')->nullable();
            $table->string('project_classification')->nullable();
            $table->string('site_zoning_classification')->nullable();
            $table->string('right_over_lands')->nullable();
            $table->string('radius_covered')->nullable();
            $table->string('land_use_radius')->nullable();
            $table->text('findings_evaluation')->nullable();
            $table->text('decision_recommended')->nullable();
            $table->date('date_evaluation')->nullable();
            $table->string('project_status')->nullable();
            $table->string('boundary_north')->nullable();
            $table->string('boundary_south')->nullable();
            $table->string('boundary_east')->nullable();
            $table->string('boundary_west')->nullable();
            $table->string('building_coverage')->nullable();
            $table->boolean('secure_ecc')->default(false);
            $table->boolean('off_street_parking')->default(false);
            $table->unsignedInteger('decision_no')->nullable();
            $table->date('certificate_date')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoning_assessments');
        Schema::dropIfExists('assessment_items');
        Schema::dropIfExists('assessments');
    }
};
