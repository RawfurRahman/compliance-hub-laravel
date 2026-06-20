<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Invitation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background-color: #4f46e5; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .content { padding: 30px; }
        .meeting-title { font-size: 22px; font-weight: bold; color: #1e293b; margin-top: 0; }
        .badge { display: inline-block; padding: 4px 12px; background-color: #fef3c7; color: #d97706; font-size: 10px; font-weight: bold; border-radius: 9999px; text-transform: uppercase; margin-bottom: 20px; }
        .rescheduled-badge { background-color: #fee2e2; color: #dc2626; }
        .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .details-table td { padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .details-label { font-weight: bold; color: #64748b; width: 30%; font-size: 14px; }
        .details-value { color: #334155; font-size: 14px; }
        .description-box { background-color: #f8fafc; border-left: 4px solid #4f46e5; padding: 15px; border-radius: 4px; margin: 20px 0; font-style: italic; }
        .button-container { text-align: center; margin: 30px 0 10px; }
        .btn { display: inline-block; padding: 12px 30px; background-color: #4f46e5; color: #ffffff !important; text-decoration: none; font-weight: bold; font-size: 14px; border-radius: 8px; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2); }
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
                @if($isRescheduled)
                    <span class="badge rescheduled-badge">Rescheduled</span>
                @else
                    <span class="badge">New Meeting Invitation</span>
                @endif
                
                <h2 class="meeting-title">{{ $meeting->title }}</h2>
                
                <p>Hello,</p>
                @if($isRescheduled)
                    <p>Please note that the meeting scheduled for the project <strong>{{ $meeting->project->name }}</strong> has been rescheduled. Below are the updated details:</p>
                @else
                    <p>You have been invited to a meeting regarding the project <strong>{{ $meeting->project->name }}</strong>. Below are the schedule details:</p>
                @endif

                <table class="details-table">
                    <tr>
                        <td class="details-label">Date & Time</td>
                        <td class="details-value"><strong>{{ $meeting->scheduled_at->format('F d, Y \a\t h:i A') }}</strong></td>
                    </tr>
                    <tr>
                        <td class="details-label">Project</td>
                        <td class="details-value">{{ $meeting->project->name }}</td>
                    </tr>
                    <tr>
                        <td class="details-label">Organizer</td>
                        <td class="details-value">{{ $meeting->creator->username }}</td>
                    </tr>
                </table>

                @if($meeting->description)
                    <h3 style="font-size: 14px; color: #1e293b; margin-bottom: 5px;">Agenda / Description:</h3>
                    <div class="description-box">
                        {{ $meeting->description }}
                    </div>
                @endif

                @if($meeting->meeting_link)
                    <div class="button-container">
                        <a href="{{ $meeting->meeting_link }}" target="_blank" class="btn">Join Meeting Room</a>
                    </div>
                    <p style="font-size: 12px; text-align: center; color: #94a3b8; margin-top: 5px;">
                        Or copy this URL: <a href="{{ $meeting->meeting_link }}" target="_blank">{{ $meeting->meeting_link }}</a>
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
