<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('permits', 'verification_token')) {
            Schema::table('permits', function (Blueprint $table) {
                $table->string('verification_token', 36)->nullable()->after('permit_number');
            });
        }

        \App\Models\Permit::whereNull('verification_token')->each(function ($permit) {
            $permit->update(['verification_token' => (string) Str::uuid()]);
        });

        Schema::table('permits', function (Blueprint $table) {
            $table->unique('verification_token');
        });
    }

    public function down(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->dropUnique(['verification_token']);
            $table->dropColumn('verification_token');
        });
    }
};
