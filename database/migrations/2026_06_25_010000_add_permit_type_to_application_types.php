<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_types', function (Blueprint $table) {
            $table->foreignId('permit_type_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('application_types', function (Blueprint $table) {
            $table->dropForeign(['permit_type_id']);
            $table->dropColumn('permit_type_id');
        });
    }
};
