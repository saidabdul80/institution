<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class UpdateConfigurations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configurations:update {--fresh : Remove all existing configurations and roles before updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update configurations and roles without affecting the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting permission update...');

        if ($this->option('fresh')) {
            $this->warn('Fresh mode: This will remove all existing configurations and roles!');
            if ($this->confirm('Are you sure you want to continue?')) {
                $this->freshUpdate();
            } else {
                $this->info('Operation cancelled.');
                return;
            }
        } else {
            $this->incrementalUpdate();
        }

        
        $this->info('Configurations and roles updated successfully!');
    }

    /**
     * Fresh update - removes all configurations and roles, then recreates them
     */
    private function freshUpdate()
    {
        $this->info('Removing all existing configurations...');

        // Clear all configurations and roles
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('configurations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Artisan::call('db:seed', ['--class' => 'ConfigurationTableSeeder']);
    }

    /**
     * Incremental update - only adds new configurations and roles
     */
    private function incrementalUpdate()
    {
        $this->info('Performing incremental update...');
        Artisan::call('db:seed', ['--class' => 'ConfigurationTableSeeder']);
    }

    
}
