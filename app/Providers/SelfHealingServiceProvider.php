<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SelfHealingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Performs self-healing checks to ensure the database, tables,
     * roles, settings, and seed data are in place. Runs only during
     * HTTP requests (not artisan commands), unless serving via `php artisan serve`.
     */
    public function boot(): void
    {
        if (! $this->shouldRunSelfHealing()) {
            return;
        }

        try {
            $this->ensureFontCacheDirExists();
            $this->ensureDatabaseExists();
            $this->ensureTablesExist();
            $this->ensureRolesExist();
            $this->ensureSettingsExist();
            $this->ensureAdminUserExists();
            $this->ensureReferenceDataExists();
        } catch (\Throwable $e) {
            Log::warning('Self-healing encountered an error: '.$e->getMessage());
        }
    }

    /**
     * Ensure DomPDF's font cache directory exists. Without it, DomPDF can't
     * persist parsed font metrics (font_cache config points here) and re-parses
     * every font — including the DejaVu Sans font used for checkmarks/peso signs
     * across most PDF templates — on every single render, which is slow.
     */
    private function ensureFontCacheDirExists(): void
    {
        $dir = storage_path('fonts');

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    /**
     * Determine whether self-healing should run.
     *
     * Runs for HTTP requests (not console), or when running via `php artisan serve`.
     */
    private function shouldRunSelfHealing(): bool
    {
        if (! app()->runningInConsole()) {
            return true;
        }

        // Allow self-healing when running through `php artisan serve`
        $argv = $_SERVER['argv'] ?? [];

        return isset($argv[1]) && $argv[1] === 'serve';
    }

    /**
     * Ensure the database exists. If using MySQL and the database
     * does not exist, attempt to create it.
     */
    private function ensureDatabaseExists(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $driver = config('database.default');

            if ($driver !== 'mysql') {
                return;
            }

            try {
                $host = config('database.connections.mysql.host');
                $port = config('database.connections.mysql.port');
                $username = config('database.connections.mysql.username');
                $password = config('database.connections.mysql.password');
                $database = config('database.connections.mysql.database');
                $charset = config('database.connections.mysql.charset', 'utf8mb4');
                $collation = config('database.connections.mysql.collation', 'utf8mb4_unicode_ci');

                $pdo = new \PDO(
                    "mysql:host={$host};port={$port}",
                    $username,
                    $password
                );

                $pdo->exec(
                    "CREATE DATABASE IF NOT EXISTS `{$database}` "
                    ."CHARACTER SET {$charset} COLLATE {$collation}"
                );

                // Purge and reconnect so Laravel picks up the new database
                DB::purge('mysql');
                DB::reconnect('mysql');

                Log::info("Self-healing: created database '{$database}'.");
            } catch (\Throwable $createEx) {
                Log::warning('Self-healing: could not create database - '.$createEx->getMessage());
            }
        }
    }

    /**
     * Ensure critical tables exist. If any are missing, run migrations.
     */
    private function ensureTablesExist(): void
    {
        $criticalTables = [
            'users',
            'settings',
            'permissions',
            'roles',
            'permit_types',
            'application_types',
            'occupancy_groups',
            'fee_categories',
        ];

        $missingTables = [];

        foreach ($criticalTables as $table) {
            try {
                if (! Schema::hasTable($table)) {
                    $missingTables[] = $table;
                }
            } catch (\Throwable $e) {
                // If we can't even check, assume migration is needed
                $missingTables[] = $table;
            }
        }

        if (! empty($missingTables)) {
            try {
                Log::info('Self-healing: running migrations (missing tables: '.implode(', ', $missingTables).').');
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::warning('Self-healing: migration failed - '.$e->getMessage());
            }
        }
    }

    /**
     * Ensure required roles exist. If not, run the RolePermissionSeeder.
     */
    private function ensureRolesExist(): void
    {
        try {
            if (! Schema::hasTable('roles')) {
                return;
            }

            $requiredRoles = ['super-admin', 'administrator', 'engineering-officer', 'client'];
            $existingCount = DB::table('roles')
                ->whereIn('name', $requiredRoles)
                ->count();

            if ($existingCount < count($requiredRoles)) {
                Log::info('Self-healing: seeding roles and permissions.');
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\RolePermissionSeeder',
                    '--force' => true,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Self-healing: role seeding failed - '.$e->getMessage());
        }
    }

    /**
     * Ensure application settings exist. If not, run the SettingsSeeder.
     */
    private function ensureSettingsExist(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $settingsCount = DB::table('settings')->count();

            if ($settingsCount === 0) {
                Log::info('Self-healing: seeding settings.');
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\SettingsSeeder',
                    '--force' => true,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Self-healing: settings seeding failed - '.$e->getMessage());
        }
    }

    /**
     * Ensure the admin user exists. If not, run the AdminUserSeeder.
     */
    private function ensureAdminUserExists(): void
    {
        try {
            if (! Schema::hasTable('users')) {
                return;
            }

            $adminExists = DB::table('users')
                ->where('email', 'admin@epms.local')
                ->exists();

            if (! $adminExists) {
                Log::info('Self-healing: seeding admin user.');
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\AdminUserSeeder',
                    '--force' => true,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Self-healing: admin user seeding failed - '.$e->getMessage());
        }
    }

    /**
     * Ensure reference data (permit types, etc.) exists. If not, run the ReferenceDataSeeder.
     */
    private function ensureReferenceDataExists(): void
    {
        try {
            if (! Schema::hasTable('permit_types')) {
                return;
            }

            $permitTypesCount = DB::table('permit_types')->count();

            if ($permitTypesCount === 0) {
                Log::info('Self-healing: seeding reference data.');
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\ReferenceDataSeeder',
                    '--force' => true,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Self-healing: reference data seeding failed - '.$e->getMessage());
        }
    }
}
