<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'general.api_key'],
            [
                'group' => 'general',
                'value' => bin2hex(random_bytes(32)),
                'type' => 'api_key',
                'description' => 'Secret key external systems must send to authenticate API requests to this system. Regenerating immediately invalidates the old key for all integrations.',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('key', 'general.api_key')->delete();
    }
};
