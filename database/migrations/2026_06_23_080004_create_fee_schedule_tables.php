<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_type_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_category_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('computation_method', [
                'fixed', 'per_unit', 'range_based', 'cumulative_range', 'percentage', 'formula',
            ])->default('fixed');
            $table->boolean('has_excess')->default(false);
            $table->boolean('has_minimum')->default(false);
            $table->boolean('has_maximum')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['fee_category_id', 'code']);
        });

        Schema::create('fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occupancy_division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('occupancy_sub_group_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('range_from', 15, 2)->default(0);
            $table->decimal('range_to', 15, 2)->default(0);
            $table->decimal('fixed_fee', 15, 2)->default(0);
            $table->decimal('fee_per_unit', 15, 4)->default(0);
            $table->decimal('percentage', 10, 6)->default(0);
            $table->decimal('excess_threshold', 15, 2)->default(0);
            $table->decimal('excess_fee', 15, 4)->default(0);
            $table->decimal('excess_every', 15, 2)->default(1);
            $table->decimal('minimum_fee', 15, 2)->default(0);
            $table->decimal('maximum_fee', 15, 2)->default(0);
            $table->text('formula')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['fee_type_id', 'range_from', 'range_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_schedules');
        Schema::dropIfExists('fee_types');
        Schema::dropIfExists('fee_categories');
    }
};
