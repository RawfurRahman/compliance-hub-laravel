<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trust Center Access Granted</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .header { background-color: #059669; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .content { padding: 30px; }
        .greeting { font-size: 22px; font-weight: bold; color: #1e293b; margin-top: 0; }
        .footer { background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>ComplianceHub Portal</h1>
            </div>
            <div class="content">
                <h2 class="greeting">Trust Center Access Granted</h2>
                <p>Hello {{ $accessRequest->requester_name }},</p>
                <p>
                    Your request to access the <strong>{{ $trustCenter->headline }}</strong>
                    trust center has been approved.
                </p>
                <p>
                    You can now visit the trust center at any time:
                </p>
                <p style="text-align: center; margin: 30px 0;">
                    <a href="{{ route('trust-center.public.show', $trustCenter->public_slug) }}"
                       style="display: inline-block; padding: 12px 30px; background-color: #059669; color: #ffffff !important; text-decoration: none; font-weight: bold; font-size: 14px; border-radius: 8px;">
                        View Trust Center
                    </a>
                </p>
                @if($trustCenter->contact_email)
                    <p>
                        If you have any questions, feel free to contact us at
                        <a href="mailto:{{ $trustCenter->contact_email }}">{{ $trustCenter->contact_email }}</a>.
                    </p>
                @endif
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} ComplianceHub. All rights reserved.<br>
                This is an automated notification. Please do not reply directly.
            </div>
        </div>
    </div>
</body>
</html>
