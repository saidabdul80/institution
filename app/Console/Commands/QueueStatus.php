<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show queue status and pending jobs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Queue Status Report');
        $this->info('==================');
        
        // Check queue configuration
        $this->info('Queue Connection: ' . config('queue.default'));
        
        // Check jobs table
        try {
            $pendingJobs = DB::table('jobs')->count();
            $this->info("Pending Jobs: {$pendingJobs}");
            
            if ($pendingJobs > 0) {
                $this->info("\nPending Jobs Details:");
                $jobs = DB::table('jobs')
                    ->select('queue', 'attempts', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
                
                $this->table(['Queue', 'Attempts', 'Created At'], 
                    $jobs->map(function($job) {
                        return [
                            $job->queue,
                            $job->attempts,
                            date('Y-m-d H:i:s', $job->created_at)
                        ];
                    })->toArray()
                );
            }
            
            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            $this->info("Failed Jobs: {$failedJobs}");
            
        } catch (\Exception $e) {
            $this->error('Error checking jobs table: ' . $e->getMessage());
            $this->info('You may need to run: php artisan migrate');
        }
        
        return 0;
    }
}
