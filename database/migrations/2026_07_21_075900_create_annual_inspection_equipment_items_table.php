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
        Schema::create('annual_inspection_equipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annual_inspection_application_id')
                ->constrained('annual_inspection_applications', 'id', 'ai_equipment_items_application_id_fk')
                ->cascadeOnDelete();
            $table->string('fee_code');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('remarks')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('annual_inspection_application_id', 'ai_equipment_items_application_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_inspection_equipment_items');
    }
};
