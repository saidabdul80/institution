<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Configuration;
use Illuminate\Support\Facades\Log;

class DocumentGenerationService
{
    /**
     * Generate acknowledgment slip for applicant after final submission
     */
    public function generateAcknowledgmentSlip(Applicant $applicant)
    {
        try {
            $template = Configuration::where('name', 'admission_acknowledgement_letter_template')->first()?->value;
            
            if (!$template) {
                Log::warning('Acknowledgment letter template not found');
                return null;
            }

            $placeholders = $this->getApplicantPlaceholders($applicant);
            $html = $this->replacePlaceholders($template, $placeholders);

            return [
                'html' => $html,
                'filename' => "acknowledgment_slip_{$applicant->application_number}.pdf",
                'title' => 'Application Acknowledgment Slip'
            ];

        } catch (\Exception $e) {
            Log::error('Error generating acknowledgment slip: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate admission notification letter (informal notice)
     */
    public function generateAdmissionNotification(Applicant $applicant)
    {
        try {
            $template = Configuration::where('name', 'admission_notification_letter_template')->first()?->value;
            
            if (!$template) {
                Log::warning('Admission notification template not found');
                return null;
            }

            $placeholders = $this->getApplicantPlaceholders($applicant);
            $html = $this->replacePlaceholders($template, $placeholders);

            return [
                'html' => $html,
                'filename' => "admission_notification_{$applicant->application_number}.pdf",
                'title' => 'Admission Notification'
            ];

        } catch (\Exception $e) {
            Log::error('Error generating admission notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate admission verification slip (after acceptance fee payment)
     */
    public function generateVerificationSlip(Applicant $applicant, $paymentData = null)
    {
        try {
            $template = Configuration::where('name', 'admission_verification_slip_template')->first()?->value;
            
            if (!$template) {
                Log::warning('Verification slip template not found');
                return null;
            }

            $placeholders = $this->getApplicantPlaceholders($applicant);
            
            // Add payment-specific placeholders
            if ($paymentData) {
                $placeholders['payment_date'] = $paymentData['payment_date'] ?? date('Y-m-d');
                $placeholders['payment_reference'] = $paymentData['payment_reference'] ?? '';
                $placeholders['payment_amount'] = $paymentData['amount'] ?? '';
            }

            $html = $this->replacePlaceholders($template, $placeholders);

            return [
                'html' => $html,
                'filename' => "verification_slip_{$applicant->application_number}.pdf",
                'title' => 'Admission Verification Slip'
            ];

        } catch (\Exception $e) {
            Log::error('Error generating verification slip: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate final admission letter (official letter)
     */
    public function generateAdmissionLetter(Applicant $applicant)
    {
        try {
            $template = Configuration::where('name', 'admission_letter_template')->first()?->value;
            
            if (!$template) {
                Log::warning('Admission letter template not found');
                return null;
            }

            $placeholders = $this->getApplicantPlaceholders($applicant);
            $html = $this->replacePlaceholders($template, $placeholders);

            return [
                'html' => $html,
                'filename' => "admission_letter_{$applicant->application_number}.pdf",
                'title' => 'Official Admission Letter'
            ];

        } catch (\Exception $e) {
            Log::error('Error generating admission letter: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all placeholders for an applicant
     */
    private function getApplicantPlaceholders(Applicant $applicant)
    {
        // Get school information
        $schoolInfo = $this->getSchoolInfo();
        
        // Get applicant information
        $applicantInfo = $this->getApplicantInfo($applicant);
        
        // Get programme information
        $programmeInfo = $this->getProgrammeInfo($applicant);
        
        // Get session information
        $sessionInfo = $this->getSessionInfo($applicant);

        return array_merge($schoolInfo, $applicantInfo, $programmeInfo, $sessionInfo, [
            'current_date' => date('F j, Y'),
            'current_year' => date('Y'),
            'submission_date' => $applicant->final_submitted_at ? $applicant->final_submitted_at->format('F j, Y') : date('F j, Y'),
            'admission_date' => $applicant->published_at ? $applicant->published_at->format('F j, Y') : date('F j, Y'),
        ]);
    }

    /**
     * Get school information placeholders
     */
    private function getSchoolInfo()
    {
        $configs = Configuration::whereIn('name', [
            'school_name', 'school_acronym', 'school_logo', 'school_address', 
            'school_city', 'school_state', 'school_email', 'school_phone'
        ])->pluck('value', 'name');

        return [
            'school_name' => $configs['school_name'] ?? 'Institution Name',
            'school_acronym' => $configs['school_acronym'] ?? 'INST',
            'school_logo' => $configs['school_logo'] ?? '',
            'school_address' => $configs['school_address'] ?? '',
            'school_city' => $configs['school_city'] ?? '',
            'school_state' => $configs['school_state'] ?? '',
            'school_email' => $configs['school_email'] ?? '',
            'school_phone' => $configs['school_phone'] ?? '',
        ];
    }

    /**
     * Get applicant information placeholders
     */
    private function getApplicantInfo(Applicant $applicant)
    {
        return [
            'applicant_title' => $applicant->title ?? 'Mr/Ms',
            'applicant_first_name' => $applicant->first_name ?? '',
            'applicant_middle_name' => $applicant->middle_name ?? '',
            'applicant_surname' => $applicant->surname ?? '',
            'applicant_full_name' => $applicant->full_name ?? '',
            'applicant_email' => $applicant->email ?? '',
            'applicant_phone' => $applicant->phone_number ?? '',
            'applicant_address' => $applicant->present_address ?? '',
            'applicant_city' => $applicant->city ?? '',
            'applicant_state' => $applicant->state ?? '',
            'application_number' => $applicant->application_number ?? '',
            'jamb_number' => $applicant->jamb_number ?? '',
        ];
    }

    /**
     * Get programme information placeholders
     */
    private function getProgrammeInfo(Applicant $applicant)
    {
        return [
            'programme_name' => $applicant->programme_name ?? '',
            'programme_type' => $applicant->programme_type ?? '',
            'faculty_name' => $applicant->faculty ?? '',
            'department_name' => $applicant->department ?? '',
            'level_name' => $applicant->level ?? '',
            'entry_mode' => $applicant->entry_mode ?? '',
            'mode_of_study' => $applicant->entry_mode ?? 'Full Time',
        ];
    }

    /**
     * Get session information placeholders
     */
    private function getSessionInfo(Applicant $applicant)
    {
        $session = $applicant->session;
        
        return [
            'academic_session' => $session?->name ?? '',
            'session_name' => $session?->name ?? '',
        ];
    }

    /**
     * Replace placeholders in template with actual values
     */
    private function replacePlaceholders($template, $placeholders)
    {
        // Replace simple placeholders
        foreach ($placeholders as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        // Handle conditional blocks (basic implementation)
        $template = preg_replace_callback('/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s', function ($matches) use ($placeholders) {
            $condition = $matches[1];
            $content = $matches[2];

            return !empty($placeholders[$condition]) ? $content : '';
        }, $template);

        return $template;
    }

}
