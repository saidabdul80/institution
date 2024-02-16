<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;

class SeedTenantCommand extends Command
{
    protected $signature = 'tenant-c:seed {identifier}';

    protected $description = 'Run seeders for a specific tenant';

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
        // Example: Set the database connection to the specified tenant's database
        tenancy($tenant->id)->run(function () {
            // Run seeders for the specified tenant
            Artisan::call('db:seed', [
                '--class' => 'DatabaseSeeder', // Replace with your seeder class
            ]);
        });
        

        $this->info('Seeders ran successfully for tenant: ' . $tenant->name);
    }
}
