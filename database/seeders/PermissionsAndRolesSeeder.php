<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionsAndRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Running permissions update...');

        // Run the permissions update command
        \Illuminate\Support\Facades\Artisan::call('permissions:update', ['--fresh' => true]);

        $this->command->info('Permissions and roles seeded successfully!');
        $this->command->line(\Illuminate\Support\Facades\Artisan::output());
    }
}
