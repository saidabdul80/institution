<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Lga;
use App\Models\MDA;
use App\Models\Agent;

use App\Models\Vendor;
use App\Models\Wallet;
use App\Models\Staffer;
use App\Models\Beneficiary;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Configuration;
use App\Models\RevenueSource;
use App\Enums\Status;
use App\Models\PaymentSpliting;

use App\Models\BusinessStructure;
use App\Models\CorporateTaxPayer;

use App\Models\IndividualTaxPayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Applicant;
use App\Models\Programme;
use App\Models\ProgrammeCurriculum;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Token;
use Modules\Staff\Repositories\StudentRepository;

class Util
{
    /**
     * Upload a file to S3 and return its URL.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     */
    public static function upload($file, $key, $dir = "public", $useFileName = false)
    {
        try {
            $filePath = Storage::disk('s3')->put($dir . "/" . $key . "/" . date('Y') . "/" . date('M'), $file);

            if ($filePath) {
                return $filePath;
            } else {
                Log::info("Failed to upload the file.");
            }
        } catch (\Exception $e) {
            Log::error('Error uploading file to S3: ' . $e->getMessage());
            return '';
        }
    }

    public static function deleteUpload($path)
    {
        try {
            $filePath = Storage::disk('s3')->delete($path);
        } catch (\Exception $e) {
            Log::error('Error uploading file to S3: ' . $e->getMessage());
            return '';
        }
    }


    public static function uploader($file, $key = 'storage')
    {
        return $file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile ? self::upload($file, $key, 'private') : $file;
    }

    /**
     * Generate a pre-signed URL for a file on S3.
     *
     * @param string $key
     * @param int $minutes
     * @return string
     */
    public static function publicUrl($relativePath, $minutes = 60)
    {
        try {
            // Ensure the relative path does not start with a leading slash
            $key = ltrim($relativePath, '/');

            // Log the key to ensure it's correct

        if (Str::contains($key, 'storage')) {
            return asset($relativePath);
        }

            // Get the S3 client directly
            $client = Storage::disk('s3')->getClient();
            $expiry = "+{$minutes} minutes";

            // Prepare the GetObject command
            $command = $client->getCommand('GetObject', [
                'Bucket' => config('default.aws.bucket'),
                'Key' => $key,
            ]);

            // Create a presigned request
            $request = $client->createPresignedRequest($command, $expiry);

            // Return the presigned URL
            return (string) $request->getUri();
        } catch (\Exception $e) {
            // Log the exception message
            // Log::error('Error generating presigned URL: ' . $e->getMessage());
            return "";
        }
    }

    /**
     * Upload a file from a request and return the updated request data with the file URL.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $fileName
     * @return array
     */
    public static function getRequestUploadedData(Request $request, $fileName = 'value')
    {
        $data = $request->all();
        $fileNames = is_array($fileName) ? $fileName : [$fileName];

        foreach ($fileNames as $name) {
            if ($request->hasFile($name)) {
                $file = $request->file($name);
                // Log::info('Uploading file:', ['name' => $file->getClientOriginalName(), 'mime' => $file->getClientMimeType()]);

                $pictureUrl = self::upload($file, $name);
                if (empty($pictureUrl)) {
                    Log::info("Failed to upload the file or generate the URL.");
                }

                $data[$name] = $pictureUrl;
            }
        }

        foreach ($data as $key => &$value) {
            if ($value == 'null') {
                $data[$key] = null;
            }
        }

        return $data;
    }



    public static function getUploadPath($url)
    {
        // Parse the URL to get its components
        $parsedUrl = parse_url($url);

        // Check if the path component exists
        if (isset($parsedUrl['path'])) {
            // Remove the leading slash from the path
            $key = ltrim($parsedUrl['path'], '/');

            // Check if the key contains query parameters and remove them
            if (isset($parsedUrl['query'])) {
                $key = explode('?', $key)[0];
            }

            return $key;
        }
        return null;
    }

    public static function getConfigValue($name)
    {
        $config = Configuration::where('name', $name)->first();
        if ($config) {
            if (($config->field_type == 'image' || $config->field_type == 'file') && $config->value != '' && !str_contains($config->value, 'http')) {
                return self::publicUrl($config->value);
            }
            return $config->value;
        }
        return null;
    }



    public static function sendSMS($to, $message, $type = "sms")
    {
        $config = config('default.sms');

        if (empty($config) || !isset($config['api_key']) || !isset($config['from'])) {
            Log::error('SMS configuration is incomplete.');
            return;
        }

        $payload = $config;
        //    Log::info($payload);
        $payload['to'] = $to;
        $payload['sms'] = config('default.title') . "\n" . $message;

        $route = "/api/sms/send";
        if ($type == "bulk") {
            $route = "/api/sms/send/bulk";
        }
        try {
            $response = Http::post(config('default.sms_base_url') . $route, $payload);

            if ($response->successful()) {
                //  Log::info('SMS sent successfully: ' . $response->body());
            } else {
                Log::error('SMS sending failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('SMS sending exception: ' . $e->getMessage());
        }
    }

    public static function verifyPhoneNumber($request)
    {
        $user = $request->user();

        if (is_null($user)) {
            abort(401, "Unauthorized");
        }

        $user = get_class($user)::where('id', $user->id)
            ->where('phone_number_otp', $request->otp)
            ->where(function ($query) use ($request) {
                $query->where('phone_number_1', $request->phone_number)
                    ->orWhere('phone_number_2', $request->phone_number);
            })
            ->where('phone_number_otp_expires_at', '>', now())
            ->first();

        if ($user) {
            $column_name = $user->phone_number_1 === $request->phone_number ? 'phone_number_1' : 'phone_number_2';
            $user->update([
                $column_name . '_verified_at' => now(),
                'phone_number_otp' => null,
                'phone_number_otp_expires_at' => null
            ]);
            return "Phone number verified successfully.";
        }

        return false;
    }

    /* 
    public static function clearGarbageUsers(){
        IndividualTaxPayer::whereNull('email_verified_at')->where('otp_expires_at', '<', now())->delete();
        CorporateTaxPayer::whereNull('email_verified_at')->where('otp_expires_at', '<', now())->delete();
    } */
    // static public function processPaymentCompletion($payment,$totalPaid){    
    //     $invoices = $payment->taxInvoices;
    //     foreach ($invoices as $invoice) {
    //         if ($totalPaid > 0) {
    //             if ($totalPaid >= $invoice->amount) {
    //                 self::revenueSubHeadResolver($invoice, $totalPaid);
    //                 $invoice->update([
    //                     'status' => 'paid'
    //                 ]);
    //                 $totalPaid -= $invoice->amount;
    //                 if($invoices->count() == 1 && $totalPaid > $invoice->amount){
    //                     $wallet = Wallet::getWalletWithInvoice($invoice);
    //                     $wallet->credit($totalPaid - $invoice->amount);
    //                 }
    //             } else {                        
    //                 if ($invoices->count() == 1) {
    //                     $invoice->update([
    //                         'status' => 'part_paid'
    //                     ]);
    //                 } else {
    //                     $wallet = Wallet::getWalletWithInvoice($invoice);
    //                     $wallet->credit($totalPaid);
    //                 }
    //                 $totalPaid = 0;
    //             }
    //         } else {
    //             InitiatedPaymentTaxInvoice::where([
    //                 'payment_id' => $payment->id,
    //                 'invoice_id' => $invoice->id,
    //             ])->delete();
    //         }
    //     }
    // }
    static public function processPaymentCompletion($payment, $totalPaid = 0, $date = null, $time = null)
    {
        // Get the single invoice associated with this payment
        $invoice = $payment->invoice;

        if (!$invoice) {
            Log::error("No invoice found for payment ID: {$payment->id}");
            return;
        }
        // Calculate remaining amount to apply
        $remainingAmount = max(0, $totalPaid - ($invoice->paid_amount ?? 0));

        if ($remainingAmount <= 0) {
            Log::info("No payment needed - invoice already fully paid for payment ID: {$payment->id}");
            return;
        }

        $newPaidAmount = ($invoice->paid_amount ?? 0) + $totalPaid;

        // Update invoice status based on payment
        $updateData = [
            'paid_amount' => $newPaidAmount,
            'status' => $newPaidAmount >= $invoice->amount ? 'paid' : 'part paid'
        ];

        self::resolvePayment($payment, $invoice, $totalPaid);
        $invoice->update($updateData);

        $payment->update([
            'payment_date' =>  $date ?? Carbon::now()->format('Y-m-d') . ' ' . $time ?? Carbon::now()->format('H:i:s')
        ]);

        if ($payment->gateway != 'wallet' && Util::getConfigValue('enable_split_payment') == 'true') {
            PaymentSpliting::where('payment_id', $payment->id)->update(['status' => 1]);
        }
        return $payment;
    }

    private static function resolvePayment($payment)
    {
        $paymentCategory = $payment->invoice->invoiceType->payment_category;

        switch ($paymentCategory->short_name) {
            case 'application_fee':
                $applicant = Applicant::where('id', $payment->invoice->owner_id)->first();
                $applicant->application_fee_paid = true;
                //$applicant->application_fee = 'paid';
                $applicant->application_fee_paid_at = now();
                $applicant->save();
                break;
            case 'registration_fee':
                self::promoteStudent($payment->invoice);
                break;
            case 'acceptance_fee':
                $documentService = new DocumentGenerationService();
                $paymentData = [
                    'payment_date' => $payment->paid_at ? $payment->paid_at->format('F j, Y') : now()->format('F j, Y'),
                    'payment_reference' => $payment->payment_reference,
                    'amount' => number_format($payment->amount, 2)
                ];

                $applicant = $payment->invoice->owner;
                $verificationSlip = $documentService->generateVerificationSlip($applicant, $paymentData);
                if (!$verificationSlip) {
                    Log::error('GenerateVerificationSlip: Failed to generate verification slip for applicant ' . $applicant->application_number);
                    return;
                }
                $applicant->update(['acceptance_fee_paid' => true, 'acceptance_fee_paid_at' => now()]);
                self::sendVerificationSlipEmail($applicant, $verificationSlip);

                break;
            case 'school_fee':
                self::promoteStudent($payment->invoice);
                break;
            default:
                break;
        }
    }

    /**
     * Send verification slip via email
     */
    static private function sendVerificationSlipEmail($applicant, $verificationSlip)
    {
        try {
            $schoolName = \App\Services\Util::getConfigValue('school_name') ?? 'Institution';
            $schoolEmail = \App\Services\Util::getConfigValue('school_email') ?? 'admissions@institution.edu';

            $subject = "Acceptance Fee Payment Confirmed - Next Steps | {$schoolName}";

            $data = [
                'applicant' => $applicant,
                'schoolName' => $schoolName,
                'verificationSlipHtml' => $verificationSlip['html'],
                'currentDate' => now()->format('F j, Y')
            ];

            Mail::send('emails.verification_slip', $data, function ($message) use ($applicant, $subject, $schoolName, $schoolEmail) {
                $message->to($applicant->email)
                    ->subject($subject)
                    ->from($schoolEmail, $schoolName . ' Admissions Office');
            });

            Log::info('GenerateVerificationSlip: Verification slip email sent to ' . $applicant->email);
        } catch (\Exception $e) {
            Log::error('GenerateVerificationSlip: Failed to send verification slip email - ' . $e->getMessage());
        }
    }
    static  public function promoteStudent($invoice)
    {

        if ($invoice->owner_type == 'student') {

            $graduation_level_id = $invoice->owner->graduation_level_id;
            $programme_max_duration = $invoice->owner->programme_max_duration;

            $final_semester_id = DB::table('configurations')->where('name', 'final_semester')->first()->value;
            $invoice_semester_id = $invoice->invoiceType()->semester_id;

            if (!empty($invoice_semester_id)) {
                if ($invoice_semester_id == $final_semester_id) {
                    $promote = true;
                } else {
                    $promote = false;
                }
            } else {
                $promote = true;
            }



            //promote to spill or promote to next level
            $current_session = Utilities::currentSession();
            $token = 'student' . $invoice->owner->id . $invoice->invoiceType()->session_id;
            $student_enrolment_record = [
                "owner_id" => $invoice->owner->id,
                "session_id" => $current_session,
                "level_id_from" => $invoice->owner->level_id,
                "token" => $token,
                "created_at" => date('Y-m-d h:i:s'),
                "updated_at" => date('Y-m-d h:i:s'),
            ];

            $updates = [
                'promote_count' => $invoice->owner->promote_count + 1,
            ];

            $check  = false;
            $updateStudentTable = false;

            $graduation_level_order = DB::table('levels')->where('id', $graduation_level_id)->first()->order;
            $current_level_order = DB::table('levels')->where('id', $invoice->owner->level_id)->first()->order;
            if ($current_level_order == 'spill') {
                $current_level_order = '10';
            }
            if ((int) $current_level_order >= (int) $graduation_level_order) {
                //promote to spill or withdraw
                if ((int) $current_level_order > (int) $graduation_level_order) {
                    $spill_id = $invoice->owner->level_id; //maintaining the spill id                      
                } else {
                    $spill_id = DB::table('levels')->where('order', 'spill')->first()->id; //selecting the spill id
                }

                $next_level_id = $spill_id;
                if ($invoice->owner->promote_count >= $programme_max_duration) {
                    //withdraw student
                    unset($updates['promote_count']);
                    $updates['status'] = 'academic withdrawal';
                    $updateStudentTable = true;
                } else {
                    //promte to student spill
                    $updates['level_id'] = $spill_id;
                }
                $check = DB::table('student_enrollments')->where(['token' => $token])->exists();
            } else {

                //promote to next level
                $level_id = $invoice->owner->level_id;
                $level = DB::table('levels')->where('id', $level_id)->first();
                $nextOrder = strval($level->order + 1);
                $next_level = DB::table('levels')->where('order', $nextOrder)->first();
                $next_level_id = $next_level->id;
                $updates['level_id'] = $next_level_id;
                $check = DB::table('student_enrollments')->where(['token' => $token])->exists();
            }

            $student_enrolment_record["level_id_to"] = $next_level_id;
            DB::transaction(function () use ($invoice, $student_enrolment_record, $updates, $check, $updateStudentTable) {
                if ($updateStudentTable == true) {
                    DB::table('students')->where('id', $invoice->owner->id)->update($updates);
                }

                if ($check == false) {
                    DB::table('students')->where('id', $invoice->owner->id)->update($updates);
                    DB::table('student_enrollments')->upsert([$student_enrolment_record], 'token');
                }
            });
            return 'success';
        } else
         if ($invoice->owner_type == 'applicant') {
            try {

                DB::beginTransaction();
                $applicant = Applicant::find($invoice->owner_id);
                $level_id = $applicant->level_id;
                $programme_curriculum_id = $applicant->programme_curriculum_id;
                $session_id = $applicant->session_id;
                $programme = ProgrammeCurriculum::with('programme')->find($programme_curriculum_id)->programme;

                $schoolName = DB::table('configurations')->where('name', 'school_name')->first()->value;
                $schoolLogo = DB::table('configurations')->where('name', 'school_logo')->first()->value;
                $password = Str::random(8);
                $preparedStudentdata = [
                    "first_name" => $applicant->first_name,
                    "surname" => $applicant->surname,
                    "middle_name" => $applicant->middle_name,
                    "email" => $applicant->email,
                    "phone_number" => $applicant->phone_number,
                    "gender" => $applicant->gender,
                    "status" => $applicant->status,
                    "created_at" => $applicant->created_at,
                    "updated_at" => $applicant->updated_at,
                    "promote_count" => 1,
                    "level_id" => $level_id,
                    "applied_level_id" => $level_id,
                    "programme_curriculum_id" => $programme_curriculum_id,
                    "programme_id" => $programme->id,
                    "applied_programme_curriculum_id" => $programme_curriculum_id,
                    "entry_session_id" => $session_id,
                    "mode_of_entry_id" => $applicant->mode_of_entry_id,
                    "department_id" => $programme->department_id,
                    "faculty_id" => $programme->faculty_id,
                    "date_of_birth" => $applicant->date_of_birth,
                    "marital_status" => $applicant->marital_status,
                    "religion" => $applicant->religion,
                    "present_address" => $applicant->present_address,
                    "permanent_address" => $applicant->permanent_address,
                    "guardian_full_name" => $applicant->guardian_full_name,
                    "guardian_phone_number" => $applicant->guardian_phone_number,
                    "guardian_email" => $applicant->guardian_email,
                    "guardian_relationship" => $applicant->guardian_relationship,
                    "guardian_address" => $applicant->guardian_address,
                    "next_of_kin_full_name" => $applicant->next_of_kin_full_name,
                    "next_of_kin_address" => $applicant->next_of_kin_address,
                    "next_of_kin_phone_number" => $applicant->next_of_kin_phone_number,
                    "next_of_kin_relationship" => $applicant->next_of_kin_relationship,
                    "application_id" => $applicant->id,
                    "session_id" => $applicant->session_id,
                    "batch_id" => $applicant->batch_id,
                    "lga_id" => $applicant->lga_id,
                    "country_id" => $applicant->country_id,
                    "state_id" => $applicant->state_id,
                    "password" => Hash::make($password),
                    "status" => 'active'
                ];
                $studentRepository = app(StudentRepository::class);
                $student = $studentRepository->createStudent($preparedStudentdata);
                $enrollment = [
                    "owner_id" => $student->id,
                    "owner_type" => 'student',
                    'level_id_from' => null,
                    'level_id_to' => $student->applied_level_id,
                    "session_id" => $student->entry_session_id,
                    "token" => 'student' . $student->id . $student->entry_session_id,
                    "created_at" => date('Y-m-d h:i:s'),
                    "updated_at" => date('Y-m-d h:i:s'),
                ];
                DB::table('student_enrollments')->insert($enrollment);

                $template = Util::getConfigValue('admission_letter_template');
                //send email to student with link to admission portal and their login details
                $templateData = self::prepareAdmissionLetterData($applicant);
                // Replace placeholders in template
                $admissionLetter = self::replacePlaceholders($template, $templateData);

                $pdf = Pdf::loadView('pdf.admission-letter', [
                    'applicant' => $applicant,
                    'admissionLetter' => $admissionLetter
                ]);

                Mail::send('emails.admitted', [
                    'applicant' => $applicant,
                    'schoolLogo' => $schoolLogo,
                    'schoolName' => $schoolName,
                ], function ($message) use ($applicant, $pdf) {
                    $message->to($applicant->email)
                        ->subject('Your Admission Letter')
                        ->attachData($pdf->output(), 'admission-letter.pdf');
                });

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error($e);
                throw $e;
            }
        }
    }

    /**
     * Replace placeholders in template with actual data
     */
    static private function replacePlaceholders($template, $data)
    {
        // Replace simple placeholders like {{name}}, {{email}}, etc.
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        // Handle conditional blocks like {{#if someKey}}...{{/if}}
        $template = preg_replace_callback('/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s', function ($matches) use ($data) {
            $condition = $matches[1];
            $content = $matches[2];
            return !empty($data[$condition]) ? $content : '';
        }, $template);

        return $template;
    }


    /**
     * Prepare data for admission letter template
     */
    static private function prepareAdmissionLetterData($applicant)
    {
        return [
            // School information
            'school_name' => \App\Services\Util::getConfigValue('school_name') ?? 'Institution Name',
            'school_address' => \App\Services\Util::getConfigValue('school_address') ?? '',
            'school_city' => \App\Services\Util::getConfigValue('school_city') ?? '',
            'school_state' => \App\Services\Util::getConfigValue('school_state') ?? '',
            'school_email' => \App\Services\Util::getConfigValue('school_email') ?? '',
            'school_phone' => \App\Services\Util::getConfigValue('school_phone') ?? '',
            'school_logo' => \App\Services\Util::getConfigValue('school_logo') ?? '',

            // Applicant information
            'applicant_title' => $applicant->title ?? 'Mr/Ms',
            'applicant_first_name' => $applicant->first_name,
            'applicant_middle_name' => $applicant->middle_name ?? '',
            'applicant_surname' => $applicant->surname,
            'applicant_address' => $applicant->address ?? '',
            'applicant_city' => $applicant->lga->name ?? '',
            'applicant_state' => $applicant->state->name ?? '',
            'application_number' => $applicant->application_number,

            // Academic information
            'programme_name' => $applicant->programme->name ?? 'N/A',
            'level_name' => $applicant->level->title ?? 'N/A',
            'faculty_name' => $applicant->programme->faculty->name ?? 'N/A',
            'department_name' => $applicant->programme->department->name ?? 'N/A',
            'mode_of_study' => $applicant->modeOfEntry->name ?? 'Full Time',
            'admission_batch' => $applicant->batch->name ?? 'N/A',
            'academic_session' => $applicant->session->name ?? 'N/A',

            // Dates
            'current_date' => now()->format('F j, Y'),
            'admission_date' => $applicant->published_at ? $applicant->published_at->format('F j, Y') : 'N/A',
        ];
    }

    static private function prepareShareLogic($array, $invoice, $payment_id, $paidAmount)
    {
        $source = $invoice->invoiceType->revenueSubHead->revenueHead->revenueSource;
        $state = Beneficiary::where('name', 'IRS')->first();
        $ubt = Beneficiary::where('name', 'UBT')->first();

        $vendor = null;

        // Determine vendor if present
        if ($invoice->issuer_type == Vendor::class || $invoice->issuer_type == Agent::class) {
            if ($invoice->issuer_type == Agent::class) {
                $vendor = Agent::with('vendor')->where('id', $invoice->issuer_id)->first()?->vendor;
            } else {
                $vendor = Vendor::find($invoice->issuer_id);
            }
        } elseif ($invoice->issued_through_type == Vendor::class || $invoice->issued_through_type == Agent::class) {
            if ($invoice->issued_through_type == Agent::class) {
                $vendor = Agent::with('vendor')->where('id', $invoice->issued_through_id)->first()?->vendor;
            } else {
                $vendor = Vendor::find($invoice->issued_through_id);
            }
        }

        $data = [];

        // Deduct vendor share first if vendor exists
        if ($vendor) {
            $vendorShareAmount = ($vendor->share / 100) * $paidAmount;
            $paidAmount -= $vendorShareAmount;

            $data[] = [
                'payment_id' => $payment_id,
                'beneficiary_type' => 'VENDOR',
                'beneficiary_id' => $vendor->id,
                'share_amount' => $vendorShareAmount
            ];

            Wallet::getWalletWithVendor($vendor)->credit($vendorShareAmount, $vendor->share . ' share of collection');
        }

        // Calculate UBT share from remaining amount
        $ubtShareAmount = ($ubt->share / 100) * $paidAmount;

        $lgaShareAmount = null;
        $stateShareAmount = null;

        // Determine shares based on source type
        if ($source->type == 'LGA') {
            $lga = Beneficiary::where('lga_id', $source->id)->first();
            $stateShareAmount = ($lga->state_share / 100) * $paidAmount;
            $lgaShareAmount = ($lga->share / 100) * $paidAmount;
        } else {
            $stateShareAmount = ($state->share / 100) * $paidAmount;
        }

        // Add shares to data array
        if ($lgaShareAmount) {
            $data[] = [
                'payment_id' => $payment_id,
                'beneficiary_type' => 'LGA',
                'beneficiary_id' => $source->id,
                'share_amount' => $lgaShareAmount
            ];
        }

        $data[] = [
            'payment_id' => $payment_id,
            'beneficiary_type' => 'UBT',
            'beneficiary_id' => $ubt->id,
            'share_amount' => $ubtShareAmount
        ];

        $data[] = [
            'payment_id' => $payment_id,
            'beneficiary_type' => 'STATE',
            'beneficiary_id' => $state->id,
            'share_amount' => $stateShareAmount
        ];

        return array_merge($array, $data);
    }


    static public function getTimeAt($dateTime)
    {
        $created_at = Carbon::parse($dateTime);
        // Get the current time as a Carbon instance
        $current_time = Carbon::now();

        // Calculate the time difference between now and the 'created_at' timestamp
        $diff_seconds = floor($created_at->diffInSeconds($current_time));
        $diff_minutes = floor($created_at->diffInMinutes($current_time));
        $diff_hours = floor($created_at->diffInHours($current_time));

        // Format the output based on the time difference
        if ($diff_seconds < 60) {
            $time_ago = "$diff_seconds second" . ($diff_seconds > 1 ? 's' : '') . " ago";
        } elseif ($diff_minutes < 60) {
            $time_ago = "$diff_minutes minute" . ($diff_minutes > 1 ? 's' : '') . " ago";
        } elseif ($diff_hours < 12) {
            $time_ago = "$diff_hours hour" . ($diff_hours > 1 ? 's' : '') . " ago";
        } else {
            // If more than a day ago, show the time in '2:30 PM' format
            $time_ago = $created_at->format('g:i A');
        }

        return $time_ago;
    }

    static public function withStrictModeOff(callable $callback)
    {
        try {
            // Turn off strict mode
            DB::statement('SET SESSION sql_mode = ""');

            // Execute the callback
            $result = $callback();
            // Turn on strict mode
            DB::statement('SET SESSION sql_mode = "STRICT_TRANS_TABLES,STRICT_ALL_TABLES"');

            return $result;
        } catch (\Exception $e) {
            // Handle any exceptions that occur during execution
            DB::statement('SET SESSION sql_mode = "STRICT_TRANS_TABLES,STRICT_ALL_TABLES"');
            throw $e;
        }
    }

    static public  function getMDASoureModel($name)
    {
        if (class_exists($name)) {
            return $name;
        }

        return MDA::where('name', $name)->first()?->model;
    }

    static public function getUserLgaIDs($user)
    {
        return Lga::where('state_id', Util::getConfigValue('state_id'))->whereIn('name', $user->abilities)->pluck('id')->toArray();
    }

    static public function calculatePercentage($total_revenue, $total_revenue_prev)
    {
        $percentage = 0;
        if ($total_revenue_prev != 0) {
            $percentage = (($total_revenue - $total_revenue_prev) / $total_revenue_prev) * 100;
        } elseif ($total_revenue > 0) {
            $percentage = 100;
        }

        return round($percentage, 2);
    }
    static public function reduceImageSize($imagePath)
    {
        // Increase execution time limit
        set_time_limit(120); // Increase the limit to 120 seconds
        //  Log::info('image test --->'.$imagePath);
        list($width, $height) = getimagesize($imagePath);

        // Handle different image types
        $imageType = exif_imagetype($imagePath);
        if ($imageType == IMAGETYPE_JPEG) {
            $image = imagecreatefromjpeg($imagePath);
        } elseif ($imageType == IMAGETYPE_PNG) {
            $image = @imagecreatefrompng($imagePath);
        } elseif ($imageType == IMAGETYPE_GIF) {
            $image = imagecreatefromgif($imagePath);
        } else {
            throw new \Exception('Unsupported image type.');
        }

        // Calculate new dimensions
        $newWidth = 1172; // Desired width
        $newHeight = ($height / $width) * $newWidth;

        // Create a new empty image with the new dimensions
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Copy the original image into the resized image
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Start output buffering to capture the image data
        ob_start();
        imagejpeg($resizedImage, null, 100);
        $imageData = ob_get_contents();
        ob_end_clean();

        // Free up memory
        imagedestroy($image);
        imagedestroy($resizedImage);

        // Return the image as a base64-encoded string
        return 'data:image/jpeg;base64,' . base64_encode($imageData);
    }


    static public function throwError(\Exception $e)
    {
        if (config('app.env') == 'local') {
            return $e;
        }
        return response()->json('something went wrong', 400);
    }


    static public function imageUrlToBase64($url)
    {
        try {

            $image = file_get_contents($url);
            if ($image !== false) {
                return 'data:image/jpg;base64,' . base64_encode($image);
            }
        } catch (\Exception $e) {
            if (self::getConfigValue('show_major_errors') == 'true') {
                // abort(400, "Failed to open stream");
            } else {
                Log::error("Failed to open stream: HTTP request failed! HTTP/1.1 404 Not Foun: Util::imageUrlToBase64");
            }
        }
    }


    private static function lastSubaccountShare($subaccounts)
    {

        if ($subaccounts->sum('share') != 100) {
            $totalShare = $subaccounts->sum('share');
            return (100 - $totalShare) + $subaccounts->last()['share'];
        }
        return $subaccounts->last()['share'];
    }


    public static function getSplitCode($payment, $amount)
    {
        $header = [
            "Authorization" => "Bearer " . config('paystack.secretKey'),
            "Content-Type" => "application/json",
        ];

        // Use the reusable method to prepare subaccounts
        $filtered_subaccounts = self::prepareSubaccounts($payment, $amount, false, "paystack");
        //Log::info("subaccount Array filter : " . json_encode($filtered_subaccounts->last()));
        $response = Http::withHeaders($header)->post("https://api.paystack.co/split", [
            'name' => 'Halfsies',
            'type' => "percentage",
            'currency' => "NGN",
            "bearer_type" => "subaccount",
            "bearer_subaccount" => $filtered_subaccounts->last()['subaccount'],
            'subaccounts' => $filtered_subaccounts->toArray(),
        ]);


        // return abort(400, $response->body());
        if (!$response->successful()) {
            $filtered_subaccounts = self::prepareSubaccounts($payment, $amount, true, "paystack");
            //Log::info("subaccount Array filter : " . json_encode($filtered_subaccounts->last()));
            $response = Http::withHeaders($header)->post("https://api.paystack.co/split", [
                'name' => 'Halfsies',
                'type' => "percentage",
                'currency' => "NGN",
                "bearer_type" => "subaccount",
                "bearer_subaccount" => $filtered_subaccounts->last()['subaccount'],
                'subaccounts' => $filtered_subaccounts->toArray(),
            ]);

            if (!$response->successful()) {
                abort(400, $response->body());
            }
        }

        return $response->json()['data']['split_code'];
    }

    public static function lineItems($payment, $amount)
    {
        $id = 0;

        // Use the reusable method to prepare subaccounts
        $filtered_subaccounts = self::prepareSubaccounts($payment, $amount, false, "remita");

        // Prepare line items for return
        $lineItems = $filtered_subaccounts->map(function ($subaccount) use (&$id) {
            return [
                'lineItemsId' => 'itemid' . $id++,
                'beneficiaryName' => $subaccount['account_name'],
                'beneficiaryAccount' => $subaccount['account_number'],
                'bankCode' => $subaccount['bank_code'],
                'beneficiaryAmount' => round($subaccount['amount'], 2),
                'deductFeeFrom' => $subaccount['deductFeeFrom'],
            ];
        });

        return $lineItems;
    }

    public static function prepareSubaccounts($payment, $amount, $new = false, $gateway = "paystack")
    {
        $invoices = $payment->taxInvoices;

        $subaccounts = self::splitPayments($invoices, $amount, $gateway);

        // Prepare subaccounts with updated subaccount code
        $prep_subaccounts = $subaccounts->map(function ($subaccount) use ($new) {

            // $subaccount_code = app(BeneficiaryController::class)->updateSubaccount($subaccount, $subaccount['share'], $new);    
            // //Log::info("subaccount Array : " . json_encode($subaccount));
            // if (!$subaccount_code && !$subaccount['subaccount_code']) {
            //     // Record the filtered-out subaccount with collected_share as 0
            //     return [
            //         "id" => $subaccount['id'],
            //         "subaccount" =>null, // use the original subaccount
            //         "share" => $subaccount['share'],
            //         "collected_share" => 0,
            //         "account_name" => $subaccount['account_name'], // preserve original data
            //         "account_number" => $subaccount['account_number'],
            //         "bank_code" => $subaccount['bank_code'],
            //         "deductFeeFrom" => $subaccount['deductFeeFrom'],
            //         "amount" => $subaccount['amount'],
            //         "status" => $subaccount['status']
            //     ];
            // }

            return [
                "id" => $subaccount['id'],
                "subaccount" => $subaccount['subaccount_code'],
                "share" => $subaccount['share'],
                "collected_share" => $subaccount['share'], // initially set to the same as share
                "account_name" => $subaccount['account_name'], // preserve original data
                "account_number" => $subaccount['account_number'],
                "bank_code" => $subaccount['bank_code'],
                "deductFeeFrom" => $subaccount['deductFeeFrom'],
                "amount" => $subaccount['amount'],
                "status" => $subaccount['status']
            ];
        });

        // Filter out subaccounts with collected_share as 0
        $filtered_subaccounts = $prep_subaccounts->filter(function ($subaccount) {
            return $subaccount['collected_share'] != 0 && $subaccount['status'] == 1;
        });

        // Adjust the share of the last subaccount if the total share is not 100
        if ($filtered_subaccounts->isEmpty()) {
            return collect([]);
        }

        $last_share = self::lastSubaccountShare($filtered_subaccounts);
        $filtered_subaccounts = $filtered_subaccounts->toArray();
        $filtered_subaccounts[count($filtered_subaccounts) - 1]['share'] = $last_share;

        // Adjust collected_share for the last subaccount to ensure total is 100
        $collected_share_total = collect($filtered_subaccounts)->sum('collected_share');
        if ($collected_share_total != 100) {
            $filtered_subaccounts[count($filtered_subaccounts) - 1]['collected_share'] += (100 - $collected_share_total);
        }

        // Prepare data for PaymentSpliting 
        $paymentSpliting = $prep_subaccounts->map(function ($subaccount) use ($payment) {
            return [
                'payment_id' => $payment->id,
                'beneficiary_id' => $subaccount['id'],
                'share' => $subaccount['share'],
                'collected_share' => $subaccount['collected_share'],
                'status' => 0
            ];
        });

        // Create PaymentSpliting records
        PaymentSpliting::insert($paymentSpliting->toArray());

        return collect($filtered_subaccounts);
    }

    public static function lineItemsTemp($payment, $amount)
    {
        $id = 0;

        // Use the reusable method to prepare subaccounts
        $filtered_subaccounts = self::prepareSubaccountsTemp($payment, $amount, false, "remita");

        // Prepare line items for return
        $lineItems = $filtered_subaccounts->map(function ($subaccount) use (&$id) {
            return [
                'lineItemsId' => 'itemid' . $id++,
                'beneficiaryName' => $subaccount['account_name'],
                'beneficiaryAccount' => $subaccount['account_number'],
                'bankCode' => $subaccount['bank_code'],
                'beneficiaryAmount' => round($subaccount['amount'], 2),
                'deductFeeFrom' => $subaccount['deductFeeFrom'],
            ];
        });

        return $lineItems;
    }

    private static function splitPaymentsTemp($invoices, $amountToPay, $gateway)
    {

        $allSubaccounts = collect();
        $amountLeft = $amountToPay;

        foreach ($invoices as $invoice) {
            $invoiceAmount = $invoice->amount;


            if ($amountLeft < $invoiceAmount) {
                $invoiceAmount  = $amountLeft;
            }


            if ($invoiceAmount == 0) {
                continue;
            }


            $subaccounts = self::prepareSubaccountsForSplit($invoice, $invoiceAmount, $gateway);

            $amountLeft -= $invoiceAmount;
            $allSubaccounts = $allSubaccounts->merge($subaccounts);
        }


        $totalAmount = $allSubaccounts->sum('amount');
        $distinctAccounts = [];
        foreach ($allSubaccounts as $subaccount) {
            if (empty($subaccount['bank_code'])) {
                abort(422, 'Bank code is required');
            }

            if (empty($subaccount['account_number'])) {
                abort(422, 'Account Number is required');
            }


            if (isset($distinctAccounts[$subaccount['name']])) {
                $distinctAccounts[$subaccount['name']]['amount'] += $subaccount['amount'];
            } else {
                $distinctAccounts[$subaccount['name']] = $subaccount;
            }
        }

        // if(count($distinctAccounts) == 1 && $distinctAccounts["IRS"]){
        //     $distinctAccounts["IRS"]["amount"] = $amountToPay;
        //     $distinctAccounts["IRS"]["share"] = 100;
        //     $totalAmount = $amountToPay;
        // }




        $ac = collect($distinctAccounts)->map(function ($subaccount) use ($totalAmount) {
            return [
                "id" => $subaccount['id'],
                "recipient_code" => $subaccount['recipient_code'],
                "subaccount_code" => $subaccount['subaccount_code'] ?? null,
                "share" => ($subaccount['amount'] / $totalAmount) * 100,
                "amount" => $subaccount['amount'],
                "account_name" => $subaccount['account_name'],
                "account_number" => $subaccount['account_number'],
                "bank_code" => $subaccount['bank_code'],
                "currency" => $subaccount['currency'],
                "status" => $subaccount['status'],
                "deductFeeFrom" => $subaccount['deductFeeFrom']
            ];
        })->values();
        return $ac;
    }

    public static function prepareSubaccountsTemp($payment, $amount, $new = false, $gateway = "paystack")
    {
        $invoices = $payment->taxInvoices;

        $subaccounts = self::splitPaymentsTemp($invoices, $amount, $gateway);

        // Prepare subaccounts with updated subaccount code
        $prep_subaccounts = $subaccounts->map(function ($subaccount) use ($new) {

            // $subaccount_code = app(BeneficiaryController::class)->updateSubaccount($subaccount, $subaccount['share'], $new);    
            // //Log::info("subaccount Array : " . json_encode($subaccount));
            // if (!$subaccount_code && !$subaccount['subaccount_code']) {
            //     // Record the filtered-out subaccount with collected_share as 0
            //     return [
            //         "id" => $subaccount['id'],
            //         "subaccount" =>null, // use the original subaccount
            //         "share" => $subaccount['share'],
            //         "collected_share" => 0,
            //         "account_name" => $subaccount['account_name'], // preserve original data
            //         "account_number" => $subaccount['account_number'],
            //         "bank_code" => $subaccount['bank_code'],
            //         "deductFeeFrom" => $subaccount['deductFeeFrom'],
            //         "amount" => $subaccount['amount'],
            //         "status" => $subaccount['status']
            //     ];
            // }

            return [
                "id" => $subaccount['id'],
                "subaccount" => $subaccount['subaccount_code'],
                "share" => $subaccount['share'],
                "collected_share" => $subaccount['share'], // initially set to the same as share
                "account_name" => $subaccount['account_name'], // preserve original data
                "account_number" => $subaccount['account_number'],
                "bank_code" => $subaccount['bank_code'],
                "deductFeeFrom" => $subaccount['deductFeeFrom'],
                "amount" => $subaccount['amount'],
                "status" => $subaccount['status']
            ];
        });

        // Filter out subaccounts with collected_share as 0
        $filtered_subaccounts = $prep_subaccounts->filter(function ($subaccount) {
            return $subaccount['collected_share'] != 0 && $subaccount['status'] == 1;
        });

        // Adjust the share of the last subaccount if the total share is not 100
        if ($filtered_subaccounts->isEmpty()) {
            return collect([]);
        }

        $last_share = self::lastSubaccountShare($filtered_subaccounts);
        $filtered_subaccounts = $filtered_subaccounts->toArray();
        $filtered_subaccounts[count($filtered_subaccounts) - 1]['share'] = $last_share;

        // Adjust collected_share for the last subaccount to ensure total is 100
        $collected_share_total = collect($filtered_subaccounts)->sum('collected_share');
        if ($collected_share_total != 100) {
            $filtered_subaccounts[count($filtered_subaccounts) - 1]['collected_share'] += (100 - $collected_share_total);
        }

        // Prepare data for PaymentSpliting 
        $paymentSpliting = $prep_subaccounts->map(function ($subaccount) use ($payment) {
            return [
                'payment_id' => $payment->id,
                'beneficiary_id' => $subaccount['id'],
                'share' => $subaccount['share'],
                'collected_share' => $subaccount['collected_share'],
                'status' => 0
            ];
        });

        return collect($filtered_subaccounts);
    }



    private static function prepareSubaccountsForSplit($invoice, $invoiceAmount, $gateway)
    {
        $issuer = $invoice->issuer;

        if (!empty($invoice->issuedThrough) && !($invoice->issuedThrough instanceof Staffer)) {
            $issuer = $invoice->issuedThrough instanceof Agent ? $invoice->issuedThrough->vendor : $invoice->issuedThrough;
        } elseif ($issuer instanceof Agent) {
            $issuer = $issuer->vendor;
        } elseif (!$issuer instanceof Vendor) {
            $issuer = null;
        }

        $revenueSubHead = $invoice->invoiceType->revenueSubHead;
        $irs = Beneficiary::where('name', 'IRS')->first();
        if (!$irs) {
            abort(422, "IRS Beneficiary not found");
        }

        $irsSubaccountFull = [
            "id" => $irs->id,
            "share" => 100,
            "amount" => $invoiceAmount,
            "name" => $irs->name,
            "account_number" => $irs->account_number,
            "account_name" => $irs->account_name,
            "bank_code" => $irs->bank_code,
            "currency" => $irs->currency,
            "recipient_code" => $irs->recipient_code,
            "subaccount_code" => $irs->gateways[$gateway]['code'] ?? $irs->subaccount_code,
            "status" => $irs->status,
            "deductFeeFrom" => 1,
        ];

        if ($revenueSubHead->mda_share == 0 && $revenueSubHead->vendor_share == 0 && $revenueSubHead->irs_share == 0) {
            $revenueSubHead->update(['share_type' => 'percentage']);
            return collect([$irsSubaccountFull]);
        } elseif ($revenueSubHead->irs_share == 100) {
            $revenueSubHead->update(['share_type' => 'percentage']);
            return collect([$irsSubaccountFull]);
        }

        $beneficiaries = Beneficiary::query();
        $queryExists = false;
        if ($issuer == null) {
            $revenueSubHead = clone $revenueSubHead; // Prevent modifying the original
            $revenueSubHead->irs_share += $revenueSubHead->vendor_share;
            $revenueSubHead->vendor_share = 0;
        } else {
            if ($issuer instanceof Vendor &&  $revenueSubHead->vendor_share > 0) {
                $beneficiaries->where('beneficiary_type', Vendor::class)
                    ->where('beneficiary_id', $issuer->id)
                    ->where('status', Status::ACTIVE);
                $queryExists = true;
            }
        }


        if (in_array($invoice->revenue_source_type, [Lga::class, RevenueSource::class]) && $revenueSubHead->mda_share > 0) {
            $beneficiaries->orWhere('beneficiary_type', $invoice->revenue_source_type)
                ->where('beneficiary_id', $invoice->revenue_source_id)
                ->where('status', Status::ACTIVE);
            $queryExists = true;
        }


        if (in_array($gateway, array_keys(Beneficiary::defaultGatewayOptions()))) {
            $beneficiaries->whereJsonContains('options->' . $gateway, true);
        } else {
            return collect([$irsSubaccountFull]);
        }


        if ($queryExists) {
            $beneficiaries = $beneficiaries->get();
        } else {
            return collect([$irsSubaccountFull]);
        }


        if ($revenueSubHead->irs_share > 0) {
            $beneficiaries = $beneficiaries->merge(collect([$irs]));
        }

        $subaccounts = $beneficiaries->map(function ($beneficiary) use ($revenueSubHead, $invoiceAmount, $gateway) {
            $share = match (true) {
                in_array($beneficiary->beneficiary_type, [LGA::class, RevenueSource::class]) => $revenueSubHead->mda_share,
                $beneficiary->beneficiary_type === Vendor::class => $revenueSubHead->vendor_share,
                $beneficiary->beneficiary_type === null && $beneficiary->name === 'IRS' => $revenueSubHead->irs_share,
                default => 0
            };

            if ($revenueSubHead->share_type === 'fixed') {
                $share = ($share / $invoiceAmount) * 100;
            }

            $amount = ($share / 100) * $invoiceAmount;
            $amount = round($amount, 2);
            return [
                "id" => $beneficiary->id,
                "share" => round($share, 2),
                //"amount" => floor(($share / 100) * $invoiceAmount),
                "amount" => $amount, //fix precision issue
                "name" => $beneficiary->name,
                "account_number" => $beneficiary->account_number,
                "account_name" => $beneficiary->account_name,
                "bank_code" => $beneficiary->bank_code,
                "currency" => $beneficiary->currency,
                "recipient_code" => $beneficiary->recipient_code,
                "subaccount_code" => $beneficiary->gateways[$gateway]['code'] ?? $beneficiary->subaccount_code,
                "status" => $beneficiary->status,
                "deductFeeFrom" => 0
            ];
        });


        if ($subaccounts->isNotEmpty()) {
            $subaccounts = $subaccounts->map(function ($subaccount, $index) use ($subaccounts) {
                if ($index === $subaccounts->count() - 1) {
                    $subaccount['deductFeeFrom'] = 1;
                }
                return $subaccount;
            });

            $sum = $subaccounts->sum('amount');
            $diff = round($invoiceAmount - $sum, 2);
            //       Log::info("diff : " . $diff);
            if ($diff != 0) {
                $subaccounts = $subaccounts->map(function ($item, $index) use ($diff, $subaccounts) {
                    if ($item['deductFeeFrom'] === 1) {
                        $item['amount'] = round($item['amount'] + $diff, 2);
                    }
                    return $item;
                });
            }
        }

        return $subaccounts;
    }

    private static function splitPayments($invoice, $amountToPay, $gateway)
    {

        $allSubaccounts = collect();
        $amountLeft = $amountToPay;


        $invoiceAmount = $invoice->amount;


        if ($amountLeft < $invoiceAmount) {
            $invoiceAmount  = $amountLeft;
        }


        $subaccounts = self::prepareSubaccountsForSplit($invoice, $invoiceAmount, $gateway);

        $amountLeft -= $invoiceAmount;
        $allSubaccounts = $allSubaccounts->merge($subaccounts);




        $totalAmount = $allSubaccounts->sum('amount');
        $distinctAccounts = [];
        foreach ($allSubaccounts as $subaccount) {
            if (empty($subaccount['bank_code'])) {
                abort(422, 'Bank code is required');
            }

            if (empty($subaccount['account_number'])) {
                abort(422, 'Account Number is required');
            }


            if (isset($distinctAccounts[$subaccount['name']])) {
                $distinctAccounts[$subaccount['name']]['amount'] += $subaccount['amount'];
            } else {
                $distinctAccounts[$subaccount['name']] = $subaccount;
            }
        }

        // if(count($distinctAccounts) == 1 && $distinctAccounts["IRS"]){
        //     $distinctAccounts["IRS"]["amount"] = $amountToPay;
        //     $distinctAccounts["IRS"]["share"] = 100;
        //     $totalAmount = $amountToPay;
        // }




        $ac = collect($distinctAccounts)->map(function ($subaccount) use ($totalAmount) {
            return [
                "id" => $subaccount['id'],
                "recipient_code" => $subaccount['recipient_code'],
                "subaccount_code" => $subaccount['subaccount_code'] ?? null,
                "share" => ($subaccount['amount'] / $totalAmount) * 100,
                "amount" => $subaccount['amount'],
                "account_name" => $subaccount['account_name'],
                "account_number" => $subaccount['account_number'],
                "bank_code" => $subaccount['bank_code'],
                "currency" => $subaccount['currency'],
                "status" => $subaccount['status'],
                "deductFeeFrom" => $subaccount['deductFeeFrom']
            ];
        })->values();
        return $ac;
    }

    public static function isSoleProrietor($businessStructureId)
    {
        return BusinessStructure::where('name', 'Like', '%proprietor%')
            ->where('id', $businessStructureId)
            ->exists();
    }

    public static function getUserFromRequest($request)
    {

        $token = $request->bearerToken();
        $user = null;

        if ($token &&  strlen($token) > 20) {
            //check if valid token

            // Find the token in the database
            // Find the token in the database
            $parser = app(\Lcobucci\JWT\Parser::class);
            $parsedToken = $parser->parse($token);

            // Extract the token ID (jti claim)
            $tokenId = $parsedToken->claims()->get('jti');

            // Find the token in the database using the token ID
            $tokenModel = Token::where('id', $tokenId)->first();

            if ($tokenModel && !$tokenModel->revoked) {
                if (in_array('api-staff', $tokenModel->scopes)) {
                    $user = Staffer::find($tokenModel->user_id);
                } else if (in_array('api-vendor', $tokenModel->scopes)) {
                    $user = Vendor::find($tokenModel->user_id);
                } else if (in_array('api-agent', $tokenModel->scopes)) {
                    $user = Agent::find($tokenModel->user_id);
                } else if (in_array('api-individual', $tokenModel->scopes)) {
                    $user = IndividualTaxPayer::find($tokenModel->user_id);
                } else if (in_array('api-corporate', $tokenModel->scopes)) {
                    $user = CorporateTaxPayer::find($tokenModel->user_id);
                } else {
                    $user = null;
                }
            }
            return $user;
        }
    }
}
