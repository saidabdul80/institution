<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class UpdatePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:update {--fresh : Remove all existing permissions and roles before updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update permissions and roles without affecting the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting permission update...');

        if ($this->option('fresh')) {
            $this->warn('Fresh mode: This will remove all existing permissions and roles!');
            if ($this->confirm('Are you sure you want to continue?')) {
                $this->freshUpdate();
            } else {
                $this->info('Operation cancelled.');
                return;
            }
        } else {
            $this->incrementalUpdate();
        }

        $this->info('Permission update completed successfully!');
    }

    /**
     * Fresh update - removes all permissions and roles, then recreates them
     */
    private function freshUpdate()
    {
        $this->info('Removing all existing permissions and roles...');

        // Clear all permissions and roles
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('model_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Clear the cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createPermissions();
        $this->createRoles();
        $this->assignPermissionsToRoles();
    }

    /**
     * Incremental update - only adds new permissions and roles
     */
    private function incrementalUpdate()
    {
        $this->info('Performing incremental update...');
        
        $this->createPermissions();
        $this->createRoles();
        $this->assignPermissionsToRoles();
    }

    /**
     * Create all permissions
     */
    private function createPermissions()
    {
        $this->info('Creating permissions...');

        $permissions = $this->getPermissions();
        $created = 0;
        $existing = 0;

        foreach ($permissions as $category => $categoryPermissions) {
            $this->line("Processing {$category} permissions...");
            
            foreach ($categoryPermissions as $permission) {
                if (!Permission::where('name', $permission)->exists()) {
                    Permission::create([
                        'name' => $permission,
                        'guard_name' => 'api-staff'
                    ]);
                    $created++;
                } else {
                    $existing++;
                }
            }
        }

        $this->info("Permissions created: {$created}, Already existing: {$existing}");
    }

    /**
     * Create all roles
     */
    private function createRoles()
    {
        $this->info('Creating roles...');

        $roles = $this->getRoles();
        $created = 0;
        $existing = 0;

        foreach (array_keys($roles) as $roleName) {
            if (!Role::where('name', $roleName)->exists()) {
                Role::create([
                    'name' => $roleName,
                    'guard_name' => 'api-staff'
                ]);
                $created++;
            } else {
                $existing++;
            }
        }

        $this->info("Roles created: {$created}, Already existing: {$existing}");
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles()
    {
        $this->info('Assigning permissions to roles...');

        $roles = $this->getRoles();

        foreach ($roles as $roleName => $roleData) {
            $role = Role::where('name', $roleName)->first();
            
            if ($role && isset($roleData['permissions'])) {
                // Get current permissions
                $currentPermissions = $role->permissions->pluck('name')->toArray();
                $newPermissions = $roleData['permissions'];
                
                // Find permissions to add
                $permissionsToAdd = array_diff($newPermissions, $currentPermissions);

                if (!empty($permissionsToAdd)) {
                    // Filter out permissions that don't exist
                    $existingPermissions = Permission::whereIn('name', $permissionsToAdd)->pluck('name')->toArray();
                    $validPermissions = array_intersect($permissionsToAdd, $existingPermissions);

                    if (!empty($validPermissions)) {
                        $role->givePermissionTo($validPermissions);
                        $this->line("Added " . count($validPermissions) . " permissions to {$roleName}");
                    }

                    // Report missing permissions
                    $missingPermissions = array_diff($permissionsToAdd, $existingPermissions);
                    if (!empty($missingPermissions)) {
                        $this->warn("Skipped missing permissions for {$roleName}: " . implode(', ', $missingPermissions));
                    }
                }
            }
        }
    }

    /**
     * Get all permissions organized by category
     */
    private function getPermissions()
    {
        $permissions = config('permissions.permissions', []);
        $result = [];

        foreach ($permissions as $category => $categoryPermissions) {
            $result[$category] = array_keys($categoryPermissions);
        }

        return $result;
    }

    /**
     * Get all roles with their permissions
     */
    private function getRoles()
    {
        $roles = config('permissions.roles', []);
        $result = [];

        foreach ($roles as $roleName => $roleData) {
            $permissions = $roleData['permissions'] ?? [];

            // Handle special 'all' permissions case
            if ($permissions === 'all') {
                $permissions = $this->getAllPermissions();
            }

            $result[$roleName] = [
                'permissions' => $permissions
            ];
        }

        return $result;
    }

    /**
     * Get all permissions as a flat array
     */
    private function getAllPermissions()
    {
        $allPermissions = [];
        foreach ($this->getPermissions() as $permissions) {
            $allPermissions = array_merge($allPermissions, $permissions);
        }
        return $allPermissions;
    }
}
