<?php

namespace Fazzinipierluigi\JustAGate\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PermissionInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize the package for the first time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $operation_performed = FALSE;

        $migrations = [
            '2026_02_16_114737_create_roles_table',
            '2026_02_16_114750_create_permissions_table',
            '2026_02_16_114809_create_permission_role_table',
            '2026_02_16_114826_create_role_user_table',
        ];

        $pendingMigrations = [];

        foreach ($migrations as $migration) {
            $exists = \DB::table('migrations')->where('migration', 'like', "%{$migration}%")->exists();
            if (!$exists) {
                $pendingMigrations[] = $migration;
            }
        }

        if (!empty($pendingMigrations)) {
            $this->info('Running migrations...');
            Artisan::call('migrate', [
                '--path' => 'vendor/fazzinipierluigi/just_a_gate/database/migrations',
            ]);
            $this->line(Artisan::output());
            $operation_performed = TRUE;
        }

        $adminRole = \Fazzinipierluigi\JustAGate\Models\Role::where('slug', 'admin')->first();
        if (!$adminRole) {
            $this->info('Creating admin role...');
            $adminRole = new \Fazzinipierluigi\JustAGate\Models\Role();
            $adminRole->name = 'Administrator';
            $adminRole->slug = 'admin';
            $adminRole->is_admin = true;
            $adminRole->is_system = true;
            $adminRole->save();
            $this->info('Admin role created successfully');
            $operation_performed = TRUE;
        }

        if($operation_performed)
            $this->info('Just a gate package has been initialized successfully');

        return Command::SUCCESS;
    }
}
