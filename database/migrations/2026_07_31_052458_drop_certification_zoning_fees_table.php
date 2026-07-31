<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('certification_zoning_fees');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('certification_zoning_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('occupancy_sub_group_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('occupancy_sub_group_id')->references('id')->on('occupancy_sub_groups');
        });
    }
};
