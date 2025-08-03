<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdmissionPublicationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create new permissions for admission publication
        $permissions = [
            'can_publish_admissions' => 'Publish admitted applicants',
            'can_unpublish_admissions' => 'Unpublish admitted applicants',
            'can_view_publication_stats' => 'View admission publication statistics',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // Assign permissions to relevant roles
        $adminRole = Role::where('name', 'Admin')->first();
        $registrarRole = Role::where('name', 'Registrar')->first();
        $admissionOfficerRole = Role::where('name', 'Admission Officer')->first();

        if ($adminRole) {
            $adminRole->givePermissionTo([
                'can_publish_admissions',
                'can_unpublish_admissions',
                'can_view_publication_stats'
            ]);
        }

        if ($registrarRole) {
            $registrarRole->givePermissionTo([
                'can_publish_admissions',
                'can_unpublish_admissions',
                'can_view_publication_stats'
            ]);
        }

        if ($admissionOfficerRole) {
            $admissionOfficerRole->givePermissionTo([
                'can_publish_admissions',
                'can_view_publication_stats'
            ]);
        }

        $this->command->info('Admission publication permissions seeded successfully!');
    }
}
