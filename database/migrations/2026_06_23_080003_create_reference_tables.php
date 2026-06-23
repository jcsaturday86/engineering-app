<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permit_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('application_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('scope_of_works', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('form_of_ownerships', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('occupancy_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('occupancy_sub_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occupancy_group_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('occupancy_divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occupancy_group_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->enum('assessment_mode', ['cumulative', 'non_cumulative'])->default('non_cumulative');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('building_parts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('signatories', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('designation')->nullable();
            $table->string('department')->nullable();
            $table->string('license_no')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('land_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_classifications');
        Schema::dropIfExists('signatories');
        Schema::dropIfExists('building_parts');
        Schema::dropIfExists('occupancy_divisions');
        Schema::dropIfExists('occupancy_sub_groups');
        Schema::dropIfExists('occupancy_groups');
        Schema::dropIfExists('form_of_ownerships');
        Schema::dropIfExists('scope_of_works');
        Schema::dropIfExists('application_types');
        Schema::dropIfExists('permit_types');
    }
};
