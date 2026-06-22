<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Report Shared</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background-color: #0284c7; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .content { padding: 30px; }
        .report-title { font-size: 22px; font-weight: bold; color: #1e293b; margin-top: 0; }
        .badge { display: inline-block; padding: 4px 12px; background-color: #e0f2fe; color: #0369a1; font-size: 10px; font-weight: bold; border-radius: 9999px; text-transform: uppercase; margin-bottom: 20px; }
        .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .details-table td { padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .details-label { font-weight: bold; color: #64748b; width: 30%; font-size: 14px; }
        .details-value { color: #334155; font-size: 14px; }
        .message-box { background-color: #f8fafc; border-left: 4px solid #0284c7; padding: 15px; border-radius: 4px; margin: 20px 0; }
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
                <span class="badge">Report Notification</span>
                <h2 class="report-title">{{ $reportLabel }}</h2>
                
                <p>Hello,</p>
                <p>A compliance report has been generated and distributed for project <strong>{{ $projectName }}</strong>. Please check the email attachments to view the full report.</p>

                <table class="details-table">
                    <tr>
                        <td class="details-label">Project</td>
                        <td class="details-value">{{ $projectName }}</td>
                    </tr>
                    <tr>
                        <td class="details-label">Report Type</td>
                        <td class="details-value">{{ $reportLabel }}</td>
                    </tr>
                    <tr>
                        <td class="details-label">Date Generated</td>
                        <td class="details-value">{{ now()->format('F d, Y h:i A') }}</td>
                    </tr>
                </table>

                @if($messageBody)
                    <h3 style="font-size: 14px; color: #1e293b; margin-bottom: 5px;">Message / Note:</h3>
                    <div class="message-box">
                        {{ $messageBody }}
                    </div>
                @endif

                <p>The requested report file(s) are attached to this email.</p>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} ComplianceHub. All rights reserved.<br>
                This is an automated notification. Please do not reply directly.
            </div>
        </div>
    </div>
</body>
</html>
