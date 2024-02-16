<?php

namespace App\Jobs;

use App\Models\Agent;
use App\Models\Applicant;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateWallet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $owner;
    public $wallet_number;
    public $tenant_id;
    public function __construct(Applicant | Tenant | Agent | Student $owner, $wallet_number)
    {
        $this->owner = $owner;
        $this->wallet_number = $wallet_number;
        $this->tenant_id = tenant('id');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $bonus = 0;
        try {
            Log::info('pased 1', [$this->job->getJobId()]);
            if ($this->owner instanceof Applicant) {
                $owner_type = 'App\\Models\\Applicant';
            } else if ($this->owner instanceof Tenant) {
                $owner_type = 'App\\Models\\Tenant';
            } else if ($this->owner instanceof Student) {
                $owner_type = 'App\\Models\\Student';
            } else if ($this->owner instanceof Agent) {
                $owner_type = 'App\\Models\\Agent';
            }

            Wallet::insert([
                'owner_type' => $owner_type,
                'owner_id' => $this->owner->id,
                'tenant_id' => $this->tenant_id,
                'wallet_number' => $this->wallet_number,
                'bonus' => $bonus,
                'created_at' => now()
            ]);
            Log::info('pased 2');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getMessage(), [$this->job->getJobId()]);
        }
    }
}
