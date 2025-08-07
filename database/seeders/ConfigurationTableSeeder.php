<?php

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ConfigurationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.,     
     *
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $configurations =     [
                [
                    "name"=>"school_acronym",
                    "value"=>'ABC',                
                    "field_type"=>"input",
                    "model"=>"",
                    "seeds" => "",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name"=>"school_name",
                    "value"=>'ABC School',                
                    "field_type"=>"input",
                    "model"=>"",
                    "seeds" => "",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                
                [
                    "name"=>"school_logo",
                    "value"=>'',
                    "field_type"=>"image",
                    "model"=>"",
                    "seeds" => "",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name"=>"current_session",
                    "value"=>'1',                
                    "field_type"=>"select",
                    "model"=>"App\\Models\\Session",
                    "seeds" => "",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name"=>"current_admission_batch",
                    "value"=>'1',                
                    "field_type"=>"select",
                    "model"=>"App\\Models\\AdmissionBatch",
                    "seeds" => "",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name"=>"admission_letter_template",
                    "value"=>'
                        <style>
                            body { font-family: "Times New Roman", serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; }
                            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                            .logo { max-height: 100px; margin-bottom: 10px; }
                            .school-name { font-size: 24px; font-weight: bold; margin: 10px 0; text-transform: uppercase; }
                            .school-address { font-size: 14px; margin: 5px 0; }
                            .letter-title { text-align: center; font-size: 18px; font-weight: bold; margin: 30px 0; text-decoration: underline; }
                            .content { margin: 20px 0; text-align: justify; }
                            .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                            .details-table th, .details-table td { border: 1px solid #333; padding: 8px; text-align: left; }
                            .details-table th { background-color: #f5f5f5; font-weight: bold; }
                            .signature-section { margin-top: 50px; }
                            .signature-box { display: inline-block; width: 200px; text-align: center; margin: 0 50px; }
                            .signature-line { border-bottom: 1px solid #333; margin-bottom: 5px; height: 50px; }
                            .footer { margin-top: 40px; text-align: center; font-size: 12px; border-top: 1px solid #ccc; padding-top: 20px; }
                        </style>
                        <div class="header">
                            {{#if school_logo}}
                            <img src="{{school_logo}}" alt="{{school_name}} Logo" class="logo">
                            {{/if}}
                            <div class="school-name">{{school_name}}</div>
                            <div class="school-address">{{school_address}}</div>
                            <div class="school-address">{{school_city}}, {{school_state}}</div>
                            <div class="school-address">Email: {{school_email}} | Phone: {{school_phone}}</div>
                        </div>

                        <div class="letter-title">LETTER OF ADMISSION</div>

                        <div class="content">
                            <p><strong>Date:</strong> {{current_date}}</p>

                            <p><strong>{{applicant_title}} {{applicant_first_name}} {{applicant_middle_name}} {{applicant_surname}}</strong><br>
                            {{applicant_address}}<br>
                            {{applicant_city}}, {{applicant_state}}</p>

                            <p><strong>Dear {{applicant_first_name}},</strong></p>

                            <p>I am pleased to inform you that you have been offered <strong>PROVISIONAL ADMISSION</strong> to study at {{school_name}} for the {{academic_session}} academic session.</p>

                            <table class="details-table">
                                <tr>
                                    <th>Application Number</th>
                                    <td>{{application_number}}</td>
                                </tr>
                                <tr>
                                    <th>Programme of Study</th>
                                    <td>{{programme_name}}</td>
                                </tr>
                                <tr>
                                    <th>Level of Entry</th>
                                    <td>{{level_name}}</td>
                                </tr>
                                <tr>
                                    <th>Faculty</th>
                                    <td>{{faculty_name}}</td>
                                </tr>
                                <tr>
                                    <th>Department</th>
                                    <td>{{department_name}}</td>
                                </tr>
                                <tr>
                                    <th>Mode of Study</th>
                                    <td>{{mode_of_study}}</td>
                                </tr>
                                <tr>
                                    <th>Admission Batch</th>
                                    <td>{{admission_batch}}</td>
                                </tr>
                                <tr>
                                    <th>Date of Admission</th>
                                    <td>{{admission_date}}</td>
                                </tr>
                            </table>

                            <p><strong>CONDITIONS OF ADMISSION:</strong></p>
                            <ol>
                                <li>This admission is provisional and subject to verification of your credentials.</li>
                                <li>You are required to accept this offer within <strong>14 days</strong> of the date of this letter.</li>
                                <li>Payment of acceptance fee and other prescribed fees as applicable.</li>
                                <li>Submission of all required original documents for verification.</li>
                                <li>Compliance with all academic and administrative requirements of the institution.</li>
                            </ol>

                            <p><strong>NEXT STEPS:</strong></p>
                            <ol>
                                <li>Log in to your applicant portal to accept this admission offer.</li>
                                <li>Print your admission letter and keep it safe.</li>
                                <li>Proceed to pay your acceptance fee and other required fees.</li>
                                <li>Report for registration on the date specified in the academic calendar.</li>
                            </ol>

                            <p>Please note that failure to accept this offer within the stipulated time will result in the withdrawal of the admission offer.</p>

                            <p>Congratulations on your admission, and we look forward to welcoming you to {{school_name}}.</p>
                        </div>

                        <div class="signature-section">
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div><strong>Registrar</strong></div>
                                <div>{{school_name}}</div>
                            </div>
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div><strong>Date</strong></div>
                            </div>
                        </div>

                        <div class="footer">
                            <p><strong>{{school_name}}</strong> - Excellence in Education</p>
                            <p>This is an official document. Please keep it safe for your records.</p>
                        </div>',
                    "field_type"=>"textarea",
                    "model"=>"",
                    "seeds" => "",
                    "description"=>"HTML template for admission letters with placeholders",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name"=>"admission_verification_slip_template",
                    "value"=>'
                     <style>
                            body { font-family: "Times New Roman", serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; }
                            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                            .logo { max-height: 100px; margin-bottom: 10px; }
                            .school-name { font-size: 24px; font-weight: bold; margin: 10px 0; text-transform: uppercase; }
                            .school-address { font-size: 14px; margin: 5px 0; }
                            .slip-title { text-align: center; font-size: 18px; font-weight: bold; margin: 30px 0; text-decoration: underline; color: #2c5aa0; }
                            .content { margin: 20px 0; text-align: justify; }
                            .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                            .details-table th, .details-table td { border: 1px solid #333; padding: 8px; text-align: left; }
                            .details-table th { background-color: #f5f5f5; font-weight: bold; }
                            .success-notice { background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px; }
                            .instruction-box { background-color: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; margin: 20px 0; border-radius: 5px; }
                            .checklist { background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 20px 0; border-radius: 5px; }
                            .footer { margin-top: 40px; text-align: center; font-size: 12px; border-top: 1px solid #ccc; padding-top: 20px; }
                        </style>
                    
                        <div class="header">
                            {{#if school_logo}}
                            <img src="{{school_logo}}" alt="{{school_name}} Logo" class="logo">
                            {{/if}}
                            <div class="school-name">{{school_name}}</div>
                            <div class="school-address">{{school_address}}</div>
                            <div class="school-address">{{school_city}}, {{school_state}}</div>
                            <div class="school-address">Email: {{school_email}} | Phone: {{school_phone}}</div>
                        </div>

                        <div class="slip-title">ADMISSION VERIFICATION SLIP</div>

                        <div class="content">
                            <p><strong>Date:</strong> {{current_date}}</p>

                            <div class="success-notice">
                                <h4 style="margin-top: 0; color: #155724;">✅ Acceptance Fee Payment Confirmed</h4>
                                <p style="margin-bottom: 0;">Your acceptance fee has been successfully received and processed.</p>
                            </div>

                            <p><strong>Dear {{applicant_first_name}} {{applicant_middle_name}} {{applicant_surname}},</strong></p>

                            <p>Congratulations! Your acceptance fee payment has been confirmed. This slip contains important information about the next steps in your admission process.</p>

                            <table class="details-table">
                                <tr>
                                    <th>Application Number</th>
                                    <td>{{application_number}}</td>
                                </tr>
                                <tr>
                                    <th>Full Name</th>
                                    <td>{{applicant_first_name}} {{applicant_middle_name}} {{applicant_surname}}</td>
                                </tr>
                                <tr>
                                    <th>Programme</th>
                                    <td>{{programme_name}}</td>
                                </tr>
                                <tr>
                                    <th>Faculty</th>
                                    <td>{{faculty_name}}</td>
                                </tr>
                                <tr>
                                    <th>Department</th>
                                    <td>{{department_name}}</td>
                                </tr>
                                <tr>
                                    <th>Acceptance Fee Status</th>
                                    <td><strong style="color: #28a745;">PAID ✓</strong></td>
                                </tr>
                                <tr>
                                    <th>Payment Date</th>
                                    <td>{{payment_date}}</td>
                                </tr>
                                <tr>
                                    <th>Current Status</th>
                                    <td><strong>AWAITING DOCUMENT VERIFICATION</strong></td>
                                </tr>
                            </table>

                            <div class="instruction-box">
                                <h4 style="margin-top: 0;">📋 NEXT STEPS - IMPORTANT INSTRUCTIONS</h4>

                                <h5>1. MEDICAL EXAMINATION</h5>
                                <ul>
                                    <li>Visit the designated medical center for your medical examination</li>
                                    <li>Bring this slip and a valid ID</li>
                                    <li>Medical forms will be provided at the center</li>
                                    <li>Medical examination must be completed within 30 days</li>
                                </ul>

                                <h5>2. SCHOOL FEES PAYMENT</h5>
                                <ul>
                                    <li>Log in to your student portal to generate school fees invoice</li>
                                    <li>Pay school fees before the deadline specified in the academic calendar</li>
                                    <li>Keep payment receipts for verification</li>
                                </ul>

                                <h5>3. DOCUMENT VERIFICATION</h5>
                                <ul>
                                    <li>Prepare original copies of all your credentials</li>
                                    <li>Visit the admissions office for physical document verification</li>
                                    <li>Verification appointment will be scheduled via email/SMS</li>
                                </ul>
                            </div>

                            <div class="checklist">
                                <h4 style="margin-top: 0;">📝 DOCUMENT VERIFICATION CHECKLIST</h4>
                                <p>Bring the following original documents for verification:</p>
                                <ul>
                                    <li>☐ O-Level Results (WAEC/NECO/NABTEB)</li>
                                    <li>☐ JAMB Result Slip</li>
                                    <li>☐ Birth Certificate or Age Declaration</li>
                                    <li>☐ State of Origin Certificate</li>
                                    <li>☐ Passport Photographs (6 copies)</li>
                                    <li>☐ Medical Certificate (after medical examination)</li>
                                    <li>☐ Previous Academic Transcripts (if applicable)</li>
                                    <li>☐ Change of Name Certificate (if applicable)</li>
                                    <li>☐ Marriage Certificate (if applicable)</li>
                                </ul>
                            </div>

                            <div class="instruction-box">
                                <h4 style="margin-top: 0;">⚠️ IMPORTANT REMINDERS</h4>
                                <ul>
                                    <li><strong>Keep this slip safe</strong> - You will need it for all verification processes</li>
                                    <li><strong>Check your email regularly</strong> - Important updates will be sent to your registered email</li>
                                    <li><strong>Monitor your portal</strong> - Log in regularly for updates and announcements</li>
                                    <li><strong>Meet all deadlines</strong> - Failure to complete requirements on time may affect your admission</li>
                                    <li><strong>Contact us</strong> - Reach out if you have any questions or concerns</li>
                                </ul>
                            </div>

                            <p><strong>Contact Information:</strong></p>
                            <ul>
                                <li>Admissions Office: {{school_phone}}</li>
                                <li>Email: {{school_email}}</li>
                                <li>Office Hours: Monday - Friday, 8:00 AM - 4:00 PM</li>
                            </ul>

                            <p>We look forward to welcoming you to {{school_name}} family!</p>
                        </div>

                        <div class="footer">
                            <p><strong>{{school_name}} Admissions Office</strong></p>
                            <p>This slip is issued after acceptance fee payment confirmation.</p>
                        </div>',
                    "field_type"=>"textarea",
                    "model"=>"",
                    "seeds" => "",
                    "description"=>"HTML template for verification slip issued after acceptance fee payment with next steps instructions",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name"=>"current_application_session",
                    "value"=>1,
                    "field_type"=>"select",
                    "model"=>"App\\Models\\Session",
                    "seeds" => "",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name"=>"entrance_exam_schedule",
                    "value"=>"",                
                    "field_type"=>"input",   
                    "model"=>"",
                    "seeds" => "",           
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [ 
                    "name" => "grad_min_cgpa",
                    "value" => "1.50",                
                    "field_type"=>"input",                
                    "model"=>"",
                    "seeds" => "",  
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "grad_level_id",
                    "value" => 4,
                    "field_type"=>"select",
                    "model"=>"App\\Models\\Level",
                    "seeds" => "",  
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "grad_min_credit_units",
                    "value" => 10,
                    "field_type"=>"input",
                    "model"=>"",
                    "seeds" => "",  
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "admission_notification_letter_template",
                    "value" => '
                                    <style>
                                        body { font-family: "Times New Roman", serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; }
                                        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                                        .logo { max-height: 100px; margin-bottom: 10px; }
                                        .school-name { font-size: 24px; font-weight: bold; margin: 10px 0; text-transform: uppercase; }
                                        .school-address { font-size: 14px; margin: 5px 0; }
                                        .letter-title { text-align: center; font-size: 18px; font-weight: bold; margin: 30px 0; text-decoration: underline; color: #2c5aa0; }
                                        .content { margin: 20px 0; text-align: justify; }
                                        .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                                        .details-table th, .details-table td { border: 1px solid #333; padding: 8px; text-align: left; }
                                        .details-table th { background-color: #f5f5f5; font-weight: bold; }
                                        .congratulations { background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px; text-align: center; }
                                        .important-notice { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px; }
                                        .footer { margin-top: 40px; text-align: center; font-size: 12px; border-top: 1px solid #ccc; padding-top: 20px; }
                                    </style>
                                    <div class="header">
                                        {{#if school_logo}}
                                        <img src="{{school_logo}}" alt="{{school_name}} Logo" class="logo">
                                        {{/if}}
                                        <div class="school-name">{{school_name}}</div>
                                        <div class="school-address">{{school_address}}</div>
                                        <div class="school-address">{{school_city}}, {{school_state}}</div>
                                        <div class="school-address">Email: {{school_email}} | Phone: {{school_phone}}</div>
                                    </div>

                                    <div class="letter-title">ADMISSION NOTIFICATION</div>

                                    <div class="content">
                                        <p><strong>Date:</strong> {{current_date}}</p>

                                        <p><strong>{{applicant_title}} {{applicant_first_name}} {{applicant_middle_name}} {{applicant_surname}}</strong><br>
                                        {{applicant_address}}<br>
                                        {{applicant_city}}, {{applicant_state}}</p>

                                        <div class="congratulations">
                                            <h3 style="margin: 0; color: #155724;">🎉 CONGRATULATIONS! 🎉</h3>
                                            <p style="margin: 10px 0 0 0; font-size: 16px;"><strong>You have been offered PROVISIONAL ADMISSION</strong></p>
                                        </div>

                                        <p><strong>Dear {{applicant_first_name}},</strong></p>

                                        <p>We are pleased to inform you that following the review of your application, you have been offered <strong>PROVISIONAL ADMISSION</strong> to study at {{school_name}} for the {{academic_session}} academic session.</p>

                                        <table class="details-table">
                                            <tr>
                                                <th>Application Number</th>
                                                <td>{{application_number}}</td>
                                            </tr>
                                            <tr>
                                                <th>Programme of Study</th>
                                                <td>{{programme_name}}</td>
                                            </tr>
                                            <tr>
                                                <th>Level of Entry</th>
                                                <td>{{level_name}}</td>
                                            </tr>
                                            <tr>
                                                <th>Faculty</th>
                                                <td>{{faculty_name}}</td>
                                            </tr>
                                            <tr>
                                                <th>Department</th>
                                                <td>{{department_name}}</td>
                                            </tr>
                                            <tr>
                                                <th>Mode of Study</th>
                                                <td>{{mode_of_study}}</td>
                                            </tr>
                                            <tr>
                                                <th>Admission Status</th>
                                                <td><strong style="color: #28a745;">PROVISIONALLY ADMITTED</strong></td>
                                            </tr>
                                        </table>

                                        <div class="important-notice">
                                            <h4 style="margin-top: 0;">IMPORTANT: This is an Informal Notification</h4>
                                            <p>This notification serves as an informal notice of your provisional admission. To complete your admission process, you must:</p>
                                            <ol>
                                                <li><strong>Accept this admission offer</strong> through your applicant portal</li>
                                                <li><strong>Pay the acceptance fee</strong> within the stipulated time</li>
                                                <li><strong>Complete document verification</strong> process</li>
                                                <li><strong>Await your official admission letter</strong> after verification</li>
                                            </ol>
                                        </div>

                                        <p><strong>IMMEDIATE NEXT STEPS:</strong></p>
                                        <ol>
                                            <li>Log in to your applicant portal to accept this admission offer</li>
                                            <li>Pay your acceptance fee as directed in the portal</li>
                                            <li>Upload any missing documents if required</li>
                                            <li>Wait for further instructions regarding document verification</li>
                                        </ol>

                                        <p><strong>IMPORTANT DEADLINES:</strong></p>
                                        <ul>
                                            <li>Acceptance of offer: <strong>14 days from the date of this notification</strong></li>
                                            <li>Payment of acceptance fee: <strong>As specified in your portal</strong></li>
                                        </ul>

                                        <p>Please note that this admission is provisional and subject to:</p>
                                        <ul>
                                            <li>Verification of your credentials and documents</li>
                                            <li>Payment of prescribed fees</li>
                                            <li>Compliance with admission requirements</li>
                                        </ul>

                                        <p>Failure to complete these requirements within the stipulated time may result in the withdrawal of this admission offer.</p>

                                        <p>Once again, congratulations on your provisional admission to {{school_name}}!</p>
                                    </div>

                                    <div class="footer">
                                        <p><strong>{{school_name}} Admissions Office</strong></p>
                                        <p>This is an informal admission notification. Official admission letter will be issued after verification.</p>
                                    </div>',
                    "field_type"=>"textarea",
                    "model"=>"",
                    "seeds" => "",
                    "description"=>"HTML template for informal admission notification sent after admission publication",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "admission_acknowledgement_letter_template",
                    "value" => '
                            <style>
                                body { font-family: "Times New Roman", serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; }
                                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                                .logo { max-height: 100px; margin-bottom: 10px; }
                                .school-name { font-size: 24px; font-weight: bold; margin: 10px 0; text-transform: uppercase; }
                                .school-address { font-size: 14px; margin: 5px 0; }
                                .slip-title { text-align: center; font-size: 18px; font-weight: bold; margin: 30px 0; text-decoration: underline; }
                                .content { margin: 20px 0; text-align: justify; }
                                .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                                .details-table th, .details-table td { border: 1px solid #333; padding: 8px; text-align: left; }
                                .details-table th { background-color: #f5f5f5; font-weight: bold; }
                                .important-notice { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px; }
                                .footer { margin-top: 40px; text-align: center; font-size: 12px; border-top: 1px solid #ccc; padding-top: 20px; }
                                .print-date { text-align: right; font-size: 12px; margin-bottom: 20px; }
                            </style>
                        </head>
                        <body>
                            <div class="print-date">Printed on: {{current_date}}</div>

                            <div class="header">
                                {{#if school_logo}}
                                <img src="{{school_logo}}" alt="{{school_name}} Logo" class="logo">
                                {{/if}}
                                <div class="school-name">{{school_name}}</div>
                                <div class="school-address">{{school_address}}</div>
                                <div class="school-address">{{school_city}}, {{school_state}}</div>
                                <div class="school-address">Email: {{school_email}} | Phone: {{school_phone}}</div>
                            </div>

                            <div class="slip-title">APPLICATION ACKNOWLEDGMENT SLIP</div>

                            <div class="content">
                                <p><strong>Dear {{applicant_first_name}} {{applicant_middle_name}} {{applicant_surname}},</strong></p>

                                <p>This is to acknowledge that your application for admission into {{school_name}} has been successfully submitted and received by our admissions office.</p>

                                <table class="details-table">
                                    <tr>
                                        <th>Application Number</th>
                                        <td><strong>{{application_number}}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Full Name</th>
                                        <td>{{applicant_first_name}} {{applicant_middle_name}} {{applicant_surname}}</td>
                                    </tr>
                                    <tr>
                                        <th>Email Address</th>
                                        <td>{{applicant_email}}</td>
                                    </tr>
                                    <tr>
                                        <th>Phone Number</th>
                                        <td>{{applicant_phone}}</td>
                                    </tr>
                                    <tr>
                                        <th>Programme Applied For</th>
                                        <td>{{programme_name}}</td>
                                    </tr>
                                    <tr>
                                        <th>Level of Entry</th>
                                        <td>{{level_name}}</td>
                                    </tr>
                                    <tr>
                                        <th>Academic Session</th>
                                        <td>{{academic_session}}</td>
                                    </tr>
                                    <tr>
                                        <th>Submission Date</th>
                                        <td>{{submission_date}}</td>
                                    </tr>
                                    <tr>
                                        <th>Application Status</th>
                                        <td><strong>SUBMITTED</strong></td>
                                    </tr>
                                </table>

                                <div class="important-notice">
                                    <h4 style="margin-top: 0;">IMPORTANT INFORMATION:</h4>
                                    <ul>
                                        <li><strong>Keep this slip safe</strong> - You will need your application number for all future correspondence.</li>
                                        <li><strong>Application Processing</strong> - Your application is now being processed by our admissions committee.</li>
                                        <li><strong>Result Publication</strong> - Admission results will be published on our website and sent to your registered email.</li>
                                        <li><strong>Portal Access</strong> - Continue to check your applicant portal for updates on your application status.</li>
                                        <li><strong>Contact Information</strong> - For inquiries, contact our admissions office using the details above.</li>
                                    </ul>
                                </div>

                                <p><strong>Next Steps:</strong></p>
                                <ol>
                                    <li>Wait for the publication of admission results</li>
                                    <li>Check your email and applicant portal regularly for updates</li>
                                    <li>If admitted, follow the instructions provided in your admission notification</li>
                                </ol>

                                <p>Thank you for choosing {{school_name}}. We wish you the best in your academic pursuits.</p>
                            </div>

                            <div class="footer">
                                <p><strong>{{school_name}} Admissions Office</strong></p>
                                <p>This is an automatically generated acknowledgment slip.</p>
                            </div>
                        </body>',
                    "field_type"=>"textarea",
                    "model"=>"",
                    "seeds" => "",
                    "description"=>"HTML template for application acknowledgment slip issued after final submission",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "application_screening_schedule",
                    "value" => "",
                    "field_type"=>"input",
                    "model"=>"",
                    "seeds" => "", 
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "application_notice",
                    "value" => "",
                    "field_type"=>"textarea",
                    "model"=> "",
                    "seeds" => "", 
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "students_notice",
                    "value" => "",
                    "field_type"=>"textarea",
                    "model"=>"",
                    "seeds" => "", 
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "show_photo_on_receipt",
                    "value" => "true",
                    "field_type"=>"radio",
                    "model"=>"",                
                    "seeds" => "true,false",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "show_photo_on_result_slip",
                    "value" => "true",
                    "field_type"=>"radio",
                    "model"=>"",                
                    "seeds" => "true,false",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "show_photo_on_olevel_slip",
                    "value" => "true",
                    "field_type"=>"radio",
                    "model"=>"",                
                    "seeds" => "true,false",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "show_photo_on_invoice",
                    "value" => "true",
                    "field_type"=>"radio",
                    "model"=>"",
                    "seeds" => "true,false",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                    
                ],
                [
                    "name" => "show_photo_on_transaction_slip",
                    "value" => "true",
                    "field_type"=>"radio",
                    "model"=>"",                
                    "seeds" => "true,false",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "show_photo_on_course_reg",
                    "value" => "true",
                    "field_type"=>"radio",
                    "model"=>"",                
                    "seeds" => "true,false",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    "name" => "show_photo_on_exam_card",
                    "value" => "true",
                    "field_type"=>"radio",
                    "model"=>"",                
                    "seeds" => "true,false",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name' => "show_photo_on_biodata_slip",
                    'value' => 'true',
                    "field_type"=>"radio",
                    "model"=>"",                
                    "seeds" => "true,false",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'application_number_format', 
                    'value'=>'COE/{session}/{programme_type}/{number}',
                    'field_type'=> 'select',
                    "model"=>"",                
                    "seeds" => '{school_acronym},{faculty},{department},{entry_mode},{programme_code},{programme_type},{session},{number}',
                    "description"=>"",
                    "programme_type_id"=>'2',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'matric_number_format', 
                    'value'=>'UG/{session}/{faculty}/{programme}/{number}',
                    'field_type'=> 'select',
                    "model"=>"",                                
                    "seeds" => '{school_acronym},{faculty},{department},{entry_mode},{programme_code},{programme_type},{session},{number}',
                    "description"=>"",
                    "programme_type_id"=>'2',
                    "updated_by"=>"",
                ],
            
                [
                    'name'=>'application_number_format', 
                    'value'=>'COE/{session}/{programme_type}/{number}',
                    'field_type'=> 'select',
                    "model"=>"",                
                    "seeds" => '{school_acronym},{faculty},{department},{entry_mode},{programme_code},{programme_type},{session},{number}',
                    "description"=>"",
                    "programme_type_id"=>'1',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'matric_number_format', 
                    'value'=>'UG/{session}/{faculty}/{programme}/{number}',
                    'field_type'=> 'select',
                    "model"=>"",                                
                    "seeds" => '{school_acronym},{faculty},{department},{entry_mode},{programme_code},{programme_type},{session},{number}',
                    "description"=>"",
                    "programme_type_id"=>'1',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'allow_course_registration', 
                    'value'=>'',
                    'field_type'=> 'checkbox',
                    "model"=>"App\\Models\\Level",                
                    "seeds" => '',
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'allow_payments', 
                    'value'=>'',
                    'field_type'=> 'checkbox',
                    "model"=>"App\\Models\\Level",                
                    "seeds" => '',
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],            
                [
                    'name'=>'school_state_of_origin', 
                    'value'=>10,
                    'field_type'=> 'select',
                    "model"=>"App\\Models\\State",                
                    "seeds" => "",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'current_semester', 
                    'value'=>1,
                    'field_type'=> 'select',
                    "model"=>"App\\Models\\Semester",                
                    "seeds" => "",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'final_semester', 
                    'value'=>2,
                    'field_type'=> 'select',
                    "model"=>"App\\Models\\Semester",                
                    "seeds" => "",
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'tp_course_setting', 
                    'value'=>'13,3',
                    'field_type'=> 'dual-data',
                    "model"=> "App\\Models\\Level",                
                    "seeds" => '',
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ], 
                [
                    'name'=>'matric_number_numbering_format', 
                    'value'=>'zero_prefix',
                    'field_type'=> 'select',
                    "model"=> "",                
                    "seeds" => 'zero_prefix,level_prefix,session_prefix',
                    "description"=>"",
                    "programme_type_id"=>'1',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'matric_number_numbering_format', 
                    'value'=>'level_prefix',
                    'field_type'=> 'select',
                    "model"=> "",                
                    "seeds" => 'zero_prefix,level_prefix,session_prefix',
                    "description"=>"",
                    "programme_type_id"=>'2',
                    "updated_by"=>"",
                ],            
                [
                    'name'=>'application_number_numbering_format', 
                    'value'=>'zero_prefix',
                    'field_type'=> 'select',
                    "model"=> "",                
                    "seeds" => 'zero_prefix,level_prefix,session_prefix',
                    "description"=>"",
                    "programme_type_id"=>'1',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'application_number_numbering_format', 
                    'value'=>'level_prefix',
                    'field_type'=> 'select',
                    "model"=> "",                
                    "seeds" => 'zero_prefix,level_prefix,session_prefix',
                    "description"=>"",
                    "programme_type_id"=>'2',
                    "updated_by"=>"",
                ],
                [             
                    'name' => 'exam_rules',
                    'value' => '<h4 style="text-decoration: underline">EXAMINATION RULES AND REGULATIONS </h4>
                    <p>1. Candidates should be in vicinity of examination HALL 30 minutes before the examination is due to begin. <br>
                    2. Candidates are required to sign the attendance slip on their desk. <br>
                    3. No candidates will be permitted <br>
                    i. To enter examination hall if he/she is more than 30 minutes late. <br>
                    ii. To leave the examination hall before end of 45 minutes after the commencement of the examination. <br>
                    iii. To leave the examination hall during the last 15 minutes of the exam. <br>
                    4. Candidates must sit at desk with number corresponding to those on their admission card. They are not permitted to move their
                    desk. <br>
                    5. If a student needs anything, the Invigilator should be called upon by the student by raising up his/her hand. <br>
                    6. Candidates are not permitted to introduce into the examination hall papers of any kind violation of this rule attract out right
                    dismissal. <br>
                    7. Candidate must write their examination number only on each separate answer book and on each supplementary sheet used under
                    no circumstance must they write their names. <br>
                    8. If they wish to attract the attention of the invigilator they should raise their hands. <br>
                    9. No smoking No chewing and No Girafffing. <br>
                    10. Candidates must not jot down any points or write on the examination cards or question paper. <br>
                    11. Communication of any kinds is strickly forbidden. <br>
                    12. Candidates must not enter the examination hall with handset or any computerized gadget </p>',
                    'field_type'=> 'textarea',
                    'model'=> '',
                    "seeds" => '',
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name' => 'disable_ca_score_upload',
                    'value' => '',
                    'field_type'=> 'checkbox',
                    'model'=> 'App\\Models\\Course',
                    "seeds" => '',
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name' => 'disable_exam_score_upload',
                    'value' => '',
                    'field_type'=> 'checkbox',
                    'model'=> 'App\\Models\\Course',
                    "seeds" => '',
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'allow_alevel_edit', 
                    'value'=>'',
                    'field_type'=> 'select',
                    "model"=> "App\\Models\\ProgrammeType",                
                    "seeds" => '',    
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",            
                ],
                [
                    'name'=>'allow_olevel_edit', 
                    'value'=>'',
                    'field_type'=> 'select',
                    "model"=> "App\\Models\\ProgrammeType",                
                    "seeds" => '',  
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",              
                ],
                [
                    'name'=>'allow_profile_edit', 
                    'value'=>'',
                    'field_type'=> 'select',
                    "model"=> "App\\Models\\ProgrammeType",                
                    "seeds" => '',                
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'required_jamb_for_application', 
                    'value'=>"false",
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => "true,false",    
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",            
                ],            
                [
                    'name'=>'allow_print_admission_letter', 
                    'value'=>"false",
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => "true,false",   
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",             
                ],
                [
                    'name'=>'allow_print_notification_letter', 
                    'value'=>"false",
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => "true,false",   
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",             
                ],        
                [
                    'name'=>'allow_print_acknowledgement_letter', 
                    'value'=>"false",
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => "true,false",      
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",          
                ],            
                [
                    "name" => "require_level_for_application",
                    "value" => 1,
                    "field_type"=>"select",
                    "model"=>"App\\Models\\Level",
                    "seeds" => "",  
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'active_programme_types', 
                    'value'=>'2',
                    'field_type'=> 'checkbox',
                    "model"=> "App\\Models\\ProgrammeType",                
                    "seeds" => '',    
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",            
                ],
                [
                    'name'=>'applicant_registration', 
                    'value'=>'true',
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => 'true,false',                
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'display_application_notice', 
                    'value'=>'true',
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => 'true,false',   
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",             
                ],
                [
                    'name'=>'display_students_notice', 
                    'value'=>'true',
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => 'true,false',      
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",          
                ],
                [
                    'name'=>'enable_theme_string', 
                    'value'=>'true',
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => 'true,false',      
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",          
                ],    
                [
                    'name'=>'enable_programme_type', 
                    'value'=>'false',
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => 'true,false',      
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",          
                ],    
                [
                    'name'=>'default_password', 
                    'value'=>'0000',
                    'field_type'=> 'text',
                    "model"=> "",                
                    "seeds" => '',      
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",          
                ],    
                [
                    'name'=>'enable_faculty', 
                    'value'=>'false',
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => 'true,false',      
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",          
                ],    
                [
                    'name'=>'enable_department', 
                    'value'=>'false',
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => 'true,false',      
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",          
                ],    
                [
                    'name'=>'enable_entry_mode', 
                    'value'=>'false',
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => 'true,false',      
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",          
                ],
                [
                    'name'=>'enable_acceptance_fee', 
                    'value'=>'true',
                    'field_type'=> 'radio',
                    "model"=> "",                
                    "seeds" => 'true,false',      
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",          
                ],
                [
                    'name'=>'enable_split_payment',
                    'value'=>'true',
                    'field_type'=> 'radio',
                    "model"=> "",
                    "seeds" => 'true,false',
                    "description"=>"",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ],
                [
                    'name'=>'admission_letter_process_mode',
                    'value'=>'manual',
                    'field_type'=> 'radio',
                    "model"=> "",
                    "seeds" => 'manual,automated',
                    "description"=>"Control whether final admission letters are issued manually by admission officers or automatically after document verification",
                    "programme_type_id"=>'',
                    "updated_by"=>"",
                ]
            ];

              // Retrieve existing names
       $existingNames = DB::table('configurations')
            ->whereIn('name', array_column($configurations, 'name'))
            ->pluck('name')
            ->toArray();

        $toInsert = [];

        foreach ($configurations as $item) {
            if (in_array($item['name'], $existingNames)) {
                if (!empty($item['update']) && $item['update']) {
                    unset($item['update']);
                    Configuration::updateOrCreate(['name' => $item['name']], $item);
                }
            } else {
                unset($item['update']);
                $toInsert[] = $item;
            }
        }

        if (!empty($toInsert)) {
            DB::table('configurations')->insert($toInsert);
        }
    }
}
