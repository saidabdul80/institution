<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\TestJob;
use App\Jobs\SendAdmissionEmail;
use App\Models\Applicant;
use App\Models\Programme;
use App\Models\Level;

class TestQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:test {--type=test : Type of job to test (test|email)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test queue functionality by dispatching test jobs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->option('type');
        
        $this->info("Testing queue with {$type} job...");
        
        if ($type === 'email') {
            // Test SendAdmissionEmail job
            $applicant = Applicant::first();
            $programme = Programme::first();
            $level = Level::first();
            
            if (!$applicant || !$programme || !$level) {
                $this->error('Missing required data (applicant, programme, or level) to test email job');
                return 1;
            }
            
            $this->info("Dispatching SendAdmissionEmail job for applicant: {$applicant->full_name}");
            
            SendAdmissionEmail::dispatch(
                $applicant, 
                'Test School', 
                'logo.png', 
                $programme, 
                $level
            )->onQueue('emails');
            
        } else {
            // Test basic TestJob
            $this->info('Dispatching TestJob...');
            TestJob::dispatch('Queue test from command at ' . now());
        }
        
        $this->info('Job dispatched successfully!');
        $this->info('Check queue status with: php artisan queue:status');
        $this->info('Process jobs with: php artisan queue:work');
        
        return 0;
    }
}
