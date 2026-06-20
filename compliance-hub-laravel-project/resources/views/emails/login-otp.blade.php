<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your OTP Code</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { font-size: 24px; font-weight: bold; color: #1e3a8a; text-align: center; }
        .otp-code { font-size: 36px; font-weight: bold; color: #2563eb; text-align: center; letter-spacing: 8px; margin: 20px 0; padding: 10px; background-color: #f8fafc; border-radius: 4px; }
        .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">ComplianceHub Login</div>
        <p>Hello,</p>
        <p>Your One-Time Password (OTP) for logging into ComplianceHub is:</p>
        <div class="otp-code">{{ $otp }}</div>
        <p>This code is valid for 5 minutes. Please do not share it with anyone.</p>
        <p>If you did not request this code, please ignore this email or contact support.</p>
        <div class="footer">
            &copy; {{ date('Y') }} ComplianceHub. All rights reserved.
        </div>
    </div>
</body>
</html>
