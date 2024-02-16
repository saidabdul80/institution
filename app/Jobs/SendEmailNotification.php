<?php

namespace App\Jobs;

use App\Models\Parents;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\BasicSchoolAPI\Entities\Notification;
use Modules\BasicSchoolAPI\Entities\StudentEnrollments;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emails;
    protected $content;
    protected $subject;
    protected $saveNotification;    
    protected $filters;
    /**
     * Create a new job instance.
     *
     * @param array $emails
     * @param string $content
     * @return void
     */
    public function __construct($emails, $subject, $content, $saveNotification,$filters)
    {
        $this->emails = $emails;
        $this->subject = $subject;
        $this->content = $content;
        $this->saveNotification =$saveNotification;        
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $emails = $this->emails;
        $subject = $this->subject;
        $content = $this->content;        
        $saveNotification = $this->saveNotification;
        $filters = $this->filters;
        if ($saveNotification == 1) {
            $this->saveNotification($subject,$content);
        }   

        $this->process($content, $emails,$filters);
    }

    /**
     * Replace placeholders with actual values for a specific email recipient.
     *
     * @param string $content
     * @param string $email
     * @return string
     */
    private function process($content, $emails,$filters)
    {
        // Retrieve all recipients' information based on their emails
        if (preg_match('/\{.+?\}/', $content)) {                               
            $users1 = Parents::whereIn('email', $emails)->get();
            $users2 = StudentEnrollments::filter($filters ?? [])->with('student.parent')->get()->pluck('student.parent');
            $users = $users1->concat($users2)->unique();

            $placeholders = [];
            foreach ($users as $user) {
                $placeholders[$user->email] = [
                    '{first_name}' => $user->first_name,
                    '{surname}' => $user->surname,
                    '{middle_name}' => $user->middle_name,
                ];
                if (isset($placeholders[$user->email])) {
                    $personalizedContent = str_replace(
                        array_keys($placeholders[$user->email]),
                        array_values($placeholders[$user->email]),
                        $content
                    );
                } else {
                    $personalizedContent = $content;
                }
                $this->sendMail($user->email,$this->subject,$personalizedContent);                           
            }                           
        } else {
            
            $users = StudentEnrollments::filter($filters ?? [])->with('student.parent')->get();

            $users2_emails = Parents::whereIn('email', $emails)->pluck('email');
            
            $emails = array_merge($users->pluck('student.parent.email')->toArray(), $users2_emails->toArray());
            if(count($emails) >0){
                $this->sendMail($emails, $this->subject, $content);
            }
        }
    }

    private function saveNotification($subject,$content){
        Notification::updateOrCreate(
            ['subject' => $subject],
            ['body' => $content]
        );
    }

    private function sendMail($address, $subject, $content)
    {
        Mail::html($content, function ($message) use ($address,$subject) {
            $message->to($address);
            $message->subject($subject);                
        });
    }
}
