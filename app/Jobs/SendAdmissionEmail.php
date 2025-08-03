<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Applicant;
use App\Models\Programme;
use App\Models\Level;

class SendAdmissionEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $applicant;
    public $schoolName;
    public $schoolLogo;
    public $programme;
    public $level;

public function __construct(Applicant $applicant, $schoolName, $schoolLogo, Programme $programme, Level $level)
{
    $this->applicant = $applicant;
    $this->schoolName = $schoolName;
    $this->schoolLogo = $schoolLogo;
    $this->programme = $programme;
    $this->level = $level;
    }

    public function handle()
    {
        try {
            Log::info('SendAdmissionEmail job started for applicant: ' . $this->applicant->id);

            $to = $this->applicant->email;

            if (empty($to)) {
                Log::warning('No email address for applicant: ' . $this->applicant->id);
                return;
            }

            $subject = "Congratulations! Admission Offer from {$this->schoolName}";

            $data = [
                'applicant' => $this->applicant,
                'schoolName' => $this->schoolName,
                'schoolLogo' => $this->schoolLogo,
                'programme' => $this->programme,
                'level' => $this->level,
                'currentDate' => now()->format('F j, Y')
            ];

            Mail::send('emails.admission_offer', $data, function($message) use ($to, $subject) {
                $message->to($to)
                        ->subject($subject)
                        ->from('admissions@' . strtolower(str_replace(' ', '', $this->schoolName)) . '.edu.ng',
                               $this->schoolName);
            });

            Log::info('SendAdmissionEmail job completed successfully for applicant: ' . $this->applicant->id);

        } catch (\Exception $e) {
            Log::error('SendAdmissionEmail job failed for applicant: ' . $this->applicant->id . ' - Error: ' . $e->getMessage());
            throw $e; // Re-throw to mark job as failed
        }
    }
}