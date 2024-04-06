<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $client = new ClientRepository();

        $client->createPasswordGrantClient(null, 'Default password grant client', 'http://your.redirect.path');
        $client->createPersonalAccessClient(null, 'Default personal access client', 'http://your.redirect.path');
        $this->call([
            StaffSeeder::class,
            ProgrammeSeeder::class,
            ApplicationStatusTableSeeder::class,
            ApplicationTypeTableSeeder::class,            
            ConfigurationTableSeeder::class,
            CountryTableSeeder::class,
            EntryModeTableSeeder::class,
            ExamTypeTableSeeder::class,
            InvoiceTypeTableSeeder::class,
            LevelTableSeeder::class,
            StateTableSeeder::class,
            LGATableSeeder::class,            
            PermissionSeederTable::class,
            ProgrammeTypeTableSeeder::class,
            QualificationsTableSeeder::class,
            RoleSeederTable::class,
            SemestersTableSeeder::class,
            SessionsTableSeeder::class,
            SubjectSeeder::class,
            AdmissionBatch::class,
        ]);
    }
}
