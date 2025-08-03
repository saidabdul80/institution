<!DOCTYPE html>
<html>
<head>
    <title>Admission Offer</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
        .header { text-align: center; padding: 20px 0; border-bottom: 2px solid #f0f0f0; }
        .logo { max-height: 80px; }
        .content { padding: 20px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #777; border-top: 2px solid #f0f0f0; }
        .highlight { background-color: #f8f8f8; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="header">
        @if($schoolLogo)
            <img src="{{ $message->embed(public_path($schoolLogo)) }}" alt="{{ $schoolName }} Logo" class="logo">
        @endif
        <h2>{{ $schoolName }}</h2>
    </div>

    <div class="content">
        <p>Dear {{ $applicant->first_name }} {{ $applicant->last_name }},</p>
        
        <p>We are pleased to inform you that you have been offered provisional admission to study at {{ $schoolName }} for the {{ $level->title }} programme in {{ $programme->name }}.</p>
        
        <div class="highlight">
            <p><strong>Admission Details:</strong></p>
            <p>Application Number: {{ $applicant->application_number }}</p>
            <p>Programme: {{ $programme->name }}</p>
            <p>Level: {{ $level->title }}</p>
            <p>Batch: {{ $applicant->batch->name }}</p>
            <p>Date of Offer: {{ $currentDate }}</p>
        </div>

        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Log in to your applicant portal within 14 days to accept this offer</li>
            <li>Complete your registration process</li>
            <li>Pay the required acceptance fee</li>
        </ol>

        <p>Please note that this offer will expire if not accepted within the specified timeframe.</p>

        <p>Congratulations and we look forward to welcoming you to {{ $schoolName }}!</p>

        <p>Sincerely,</p>
        <p>The Admissions Office<br>
        {{ $schoolName }}</p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply directly to this email.</p>
    </div>
</body>
</html>