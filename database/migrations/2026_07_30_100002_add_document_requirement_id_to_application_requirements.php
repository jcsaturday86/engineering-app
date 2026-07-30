<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_requirements', function (Blueprint $table) {
            // Nullable so legacy/free-form uploads survive, and nullOnDelete so
            // retiring a requirement never destroys an already-attached document.
            // requirement_name stays as a denormalized snapshot of the label at
            // upload time, in case the requirement is later renamed.
            $table->foreignId('document_requirement_id')
                ->nullable()
                ->after('applicationable_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('application_requirements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_requirement_id');
        });
    }
};
