<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mechanical_permit_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mechanical_application_id')->constrained('mechanical_applications')->cascadeOnDelete();

            // 'AC' | 'MACH' | 'ESC' | 'ELEV' | 'GENSET'
            $table->string('group_code', 10);
            $table->string('description');
            $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('amount', 12, 2);

            // Set at generation time; links this unit to the specific Permit issued for it.
            $table->foreignId('permit_id')->nullable()->constrained('permits')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('mechanical_application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mechanical_permit_units');
    }
};
