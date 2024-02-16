<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;

class MigrateSeedTenantCommand extends Command
{
    protected $signature = 'tenant:migrate-seed {identifier}';

    protected $description = 'Run fresh migration and seed for a specific tenant';

    public function handle()
    {
        $identifier = $this->argument('identifier');

        // Identify the tenant based on the provided identifier
        $tenant = Tenant::whereJsonContains('data->tenancy_db_name', $identifier)->first();

        if (!$tenant) {
            $this->error('Tenant not found.');
            return;
        }

        // Set the tenant context
        tenancy($tenant->id);

        // Run fresh migration for the specified tenant
        $this->info('Running fresh migration for tenant: ' . $tenant->name);
        Artisan::call('migrate:fresh');

        // Run seeders for the specified tenant
        $this->info('Running seeders for tenant: ' . $tenant->name);
        Artisan::call('db:seed');

        $this->info('Migration and seeding completed successfully for tenant: ' . $tenant->name);
    }
}
