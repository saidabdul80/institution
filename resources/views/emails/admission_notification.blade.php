<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Notification - {{ $schoolName }}</title>
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
            border-bottom: 3px solid #2c5aa0;
            padding-bottom: 20px;
        }
        .logo {
            max-height: 80px;
            margin-bottom: 15px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c5aa0;
            margin: 10px 0;
            text-transform: uppercase;
        }
        .congratulations-banner {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .congratulations-banner h2 {
            margin: 0;
            font-size: 28px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        .congratulations-banner p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            margin: 20px 0;
            text-align: left;
        }
        .applicant-details {
            background-color: #f8f9fa;
            border-left: 4px solid #2c5aa0;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .detail-value {
            color: #333;
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
        .next-steps {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .next-steps h4 {
            color: #0c5460;
            margin-top: 0;
        }
        .next-steps ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin: 8px 0;
        }
        .cta-button {
            display: inline-block;
            background-color: #2c5aa0;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 15px 0;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #1e3d6f;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .contact-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
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
            @if($schoolLogo)
                <img src="{{ $schoolLogo }}" alt="{{ $schoolName }} Logo" class="logo">
            @endif
            <div class="school-name">{{ $schoolName }}</div>
            <p style="margin: 0; color: #666;">Admissions Office</p>
        </div>

        <div class="congratulations-banner">
            <h2>🎉 CONGRATULATIONS! 🎉</h2>
            <p>You have been offered PROVISIONAL ADMISSION</p>
        </div>

        <div class="content">
            <p><strong>Dear {{ $applicant->first_name }} {{ $applicant->surname }},</strong></p>

            <p>We are delighted to inform you that following the review of your application, you have been offered <strong>PROVISIONAL ADMISSION</strong> to study at {{ $schoolName }} for the {{ $currentDate }} academic session.</p>

            <div class="applicant-details">
                <h4 style="margin-top: 0; color: #2c5aa0;">📋 Admission Details</h4>
                <div class="detail-row">
                    <span class="detail-label">Application Number:</span>
                    <span class="detail-value">{{ $applicant->application_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Programme of Study:</span>
                    <span class="detail-value">{{ $programme->name ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Level of Entry:</span>
                    <span class="detail-value">{{ $level->name ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Admission Status:</span>
                    <span class="detail-value" style="color: #28a745; font-weight: bold;">PROVISIONALLY ADMITTED ✓</span>
                </div>
            </div>

            <div class="important-notice">
                <h4>⚠️ IMPORTANT: This is an Informal Notification</h4>
                <p>This notification serves as an informal notice of your provisional admission. This admission is provisional and subject to:</p>
                <ul>
                    <li>Acceptance of this admission offer through your applicant portal</li>
                    <li>Payment of the prescribed acceptance fee</li>
                    <li>Verification of your credentials and documents</li>
                    <li>Compliance with all admission requirements</li>
                </ul>
            </div>

            <div class="next-steps">
                <h4>📝 IMMEDIATE NEXT STEPS</h4>
                <ol>
                    <li><strong>Log in to your applicant portal</strong> to accept this admission offer</li>
                    <li><strong>Pay your acceptance fee</strong> as directed in the portal</li>
                    <li><strong>Upload any missing documents</strong> if required</li>
                    <li><strong>Wait for further instructions</strong> regarding document verification</li>
                </ol>
                
                <p style="margin-top: 15px;"><strong>⏰ Important Deadlines:</strong></p>
                <ul>
                    <li>Acceptance of offer: <strong>14 days from the date of this notification</strong></li>
                    <li>Payment of acceptance fee: <strong>As specified in your portal</strong></li>
                </ul>
            </div>

            <div style="text-align: center; margin: 25px 0;">
                <a href="#" class="cta-button">Access Your Applicant Portal</a>
            </div>

            <p><strong>Please note:</strong> Failure to complete these requirements within the stipulated time may result in the withdrawal of this admission offer.</p>

            <p>Once again, congratulations on your provisional admission to {{ $schoolName }}! We look forward to welcoming you to our academic community.</p>
        </div>

        <div class="contact-info">
            <h4 style="margin-top: 0; color: #2c5aa0;">📞 Need Help?</h4>
            <p>Contact our Admissions Office for any inquiries:</p>
            <p><strong>Email:</strong> admissions@{{ strtolower(str_replace(' ', '', $schoolName)) }}.edu.ng</p>
            <p><strong>Office Hours:</strong> Monday - Friday, 8:00 AM - 4:00 PM</p>
        </div>

        <div class="footer">
            <p><strong>{{ $schoolName }} Admissions Office</strong></p>
            <p>This is an informal admission notification. Official admission letter will be issued after verification.</p>
            <p style="margin-top: 10px; font-size: 10px;">
                This email was sent to {{ $applicant->email }}. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
