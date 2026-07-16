<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fencing_inspectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fencing_application_id')->constrained()->cascadeOnDelete();

            // Full-Time Inspector and Supervisor
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('prc_no')->nullable();
            $table->date('prc_validity')->nullable();
            $table->string('ptr_no')->nullable();
            $table->date('ptr_date_issued')->nullable();
            $table->string('ptr_issued_at')->nullable();
            $table->string('tin')->nullable();

            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fencing_inspectors');
    }
};
