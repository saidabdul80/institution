<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceptance Fee Payment Confirmed - {{ $schoolName }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #28a745;
            padding-bottom: 20px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            margin: 10px 0;
            text-transform: uppercase;
        }
        .success-banner {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .success-banner h2 {
            margin: 0;
            font-size: 24px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        .success-banner p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            margin: 20px 0;
            text-align: left;
        }
        .payment-details {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px dotted #c3e6cb;
        }
        .detail-label {
            font-weight: bold;
            color: #155724;
        }
        .detail-value {
            color: #155724;
        }
        .next-steps {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .next-steps h3 {
            color: #0c5460;
            margin-top: 0;
            font-size: 18px;
        }
        .step-section {
            margin: 15px 0;
        }
        .step-section h4 {
            color: #0c5460;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .step-section ul {
            margin: 8px 0;
            padding-left: 20px;
        }
        .step-section li {
            margin: 5px 0;
        }
        .checklist {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .checklist h4 {
            color: #495057;
            margin-top: 0;
        }
        .checklist ul {
            list-style: none;
            padding-left: 0;
        }
        .checklist li {
            margin: 8px 0;
            padding-left: 25px;
            position: relative;
        }
        .checklist li:before {
            content: "☐";
            position: absolute;
            left: 0;
            font-size: 16px;
            color: #6c757d;
        }
        .important-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .important-notice h4 {
            color: #856404;
            margin-top: 0;
        }
        .contact-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .contact-info h4 {
            color: #495057;
            margin-top: 0;
        }
        .cta-button {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 15px 0;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #218838;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .email-container {
                padding: 20px;
            }
            .detail-row {
                flex-direction: column;
            }
            .detail-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="school-name">{{ $schoolName }}</div>
            <p style="margin: 0; color: #666;">Admissions Office</p>
        </div>

        <div class="success-banner">
            <h2>✅ Payment Confirmed!</h2>
            <p>Your acceptance fee has been successfully processed</p>
        </div>

        <div class="content">
            <p><strong>Dear {{ $applicant->first_name }} {{ $applicant->surname }},</strong></p>

            <p>Congratulations! Your acceptance fee payment has been confirmed and processed successfully. This email contains important information about the next steps in your admission process.</p>

            <div class="payment-details">
                <h4 style="margin-top: 0; color: #155724;">💳 Payment Confirmation</h4>
                <div class="detail-row">
                    <span class="detail-label">Application Number:</span>
                    <span class="detail-value">{{ $applicant->application_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Status:</span>
                    <span class="detail-value" style="font-weight: bold;">CONFIRMED ✓</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Date:</span>
                    <span class="detail-value">{{ $currentDate }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Current Status:</span>
                    <span class="detail-value" style="font-weight: bold;">AWAITING DOCUMENT VERIFICATION</span>
                </div>
            </div>

            <div class="next-steps">
                <h3>📋 NEXT STEPS - IMPORTANT INSTRUCTIONS</h3>
                
                <div class="step-section">
                    <h4>1. 🏥 MEDICAL EXAMINATION</h4>
                    <ul>
                        <li>Visit the designated medical center for your medical examination</li>
                        <li>Bring this email and a valid ID</li>
                        <li>Medical forms will be provided at the center</li>
                        <li><strong>Deadline:</strong> Medical examination must be completed within 30 days</li>
                    </ul>
                </div>

                <div class="step-section">
                    <h4>2. 💰 SCHOOL FEES PAYMENT</h4>
                    <ul>
                        <li>Log in to your student portal to generate school fees invoice</li>
                        <li>Pay school fees before the deadline specified in the academic calendar</li>
                        <li>Keep payment receipts for verification</li>
                        <li>Partial payment options may be available (check your portal)</li>
                    </ul>
                </div>

                <div class="step-section">
                    <h4>3. 📄 DOCUMENT VERIFICATION</h4>
                    <ul>
                        <li>Prepare original copies of all your credentials</li>
                        <li>Visit the admissions office for physical document verification</li>
                        <li>Verification appointment will be scheduled via email/SMS</li>
                        <li>Bring all documents listed in the checklist below</li>
                    </ul>
                </div>
            </div>

            <div class="checklist">
                <h4>📝 DOCUMENT VERIFICATION CHECKLIST</h4>
                <p>Bring the following original documents for verification:</p>
                <ul>
                    <li>O-Level Results (WAEC/NECO/NABTEB)</li>
                    <li>JAMB Result Slip</li>
                    <li>Birth Certificate or Age Declaration</li>
                    <li>State of Origin Certificate</li>
                    <li>Passport Photographs (6 copies)</li>
                    <li>Medical Certificate (after medical examination)</li>
                    <li>Previous Academic Transcripts (if applicable)</li>
                    <li>Change of Name Certificate (if applicable)</li>
                    <li>Marriage Certificate (if applicable)</li>
                </ul>
            </div>

            <div class="important-notice">
                <h4>⚠️ IMPORTANT REMINDERS</h4>
                <ul>
                    <li><strong>Keep this email safe</strong> - You will need it for all verification processes</li>
                    <li><strong>Check your email regularly</strong> - Important updates will be sent to your registered email</li>
                    <li><strong>Monitor your portal</strong> - Log in regularly for updates and announcements</li>
                    <li><strong>Meet all deadlines</strong> - Failure to complete requirements on time may affect your admission</li>
                    <li><strong>Contact us</strong> - Reach out if you have any questions or concerns</li>
                </ul>
            </div>

            <div style="text-align: center; margin: 25px 0;">
                <a href="#" class="cta-button">Access Your Student Portal</a>
            </div>

            <p>We look forward to welcoming you to the {{ $schoolName }} family!</p>
        </div>

        <div class="contact-info">
            <h4>📞 Need Help?</h4>
            <p>Contact our Admissions Office for any inquiries:</p>
            <p><strong>Office Hours:</strong> Monday - Friday, 8:00 AM - 4:00 PM</p>
        </div>

        <div class="footer">
            <p><strong>{{ $schoolName }} Admissions Office</strong></p>
            <p>This email was sent to {{ $applicant->email }}. Please do not reply to this email.</p>
            <p style="margin-top: 10px; font-size: 10px;">
                This verification slip is issued after acceptance fee payment confirmation.
            </p>
        </div>
    </div>

    <!-- Embedded Verification Slip HTML -->
    <div style="page-break-before: always; margin-top: 40px;">
        {!! $verificationSlipHtml !!}
    </div>
</body>
</html>
