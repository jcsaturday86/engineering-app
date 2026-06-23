<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('name')->nullable();
            $table->string('middle_name')->after('first_name')->nullable();
            $table->string('last_name')->after('middle_name')->nullable();
            $table->string('suffix')->after('last_name')->nullable();
            $table->string('phone')->after('email')->nullable();
            $table->string('department')->after('phone')->nullable();
            $table->string('position')->after('department')->nullable();
            $table->string('avatar')->after('position')->nullable();
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('must_change_password');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'middle_name', 'last_name', 'suffix',
                'phone', 'department', 'position', 'avatar',
                'is_active', 'must_change_password',
                'last_login_at', 'last_login_ip',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
