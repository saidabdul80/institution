<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Admission Letter</title>
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
        <p>Dear {{ $applicant->full_name ?? $applicant->first_name . ' ' . $applicant->surname }},</p>

        <p>We are pleased to inform you that you have been successfully admitted.</p>

        <p>Your admission letter is attached to this email as a PDF.</p>

        <p>Best regards,<br>
        Admissions Office</p>
    </div>
</body>
</html>