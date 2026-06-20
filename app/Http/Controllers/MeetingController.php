<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Meeting;
use App\Mail\MeetingInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MeetingController extends Controller
{
    /**
     * Display a listing of meetings for a specific project.
     */
    public function index(Project $project)
    {
        $meetings = $project->meetings()
            ->with(['creator', 'attendees'])
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $projectUsers = $project->assignedUsers()
            ->get()
            ->push($project->user)
            ->unique('id')
            ->values();

        return view('meetings.index', compact('project', 'meetings', 'projectUsers'));
    }

    /**
     * Store a newly created meeting.
     */
    public function store(Request $request, Project $project)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date',
            'meeting_link' => 'nullable|url|max:500',
            'attendees' => 'nullable|array',
            'attendees.*' => 'exists:users,id',
            'manual_emails' => 'nullable|string',
        ]);

        // Parse manual emails
        $manualEmails = [];
        if ($request->filled('manual_emails')) {
            $rawEmails = explode(',', $request->manual_emails);
            foreach ($rawEmails as $rawEmail) {
                $trimmed = trim($rawEmail);
                if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
                    $manualEmails[] = $trimmed;
                }
            }
        }

        $meeting = $project->meetings()->create([
            'title' => $request->title,
            'description' => $request->description,
            'scheduled_at' => $request->scheduled_at,
            'meeting_link' => $request->meeting_link,
            'additional_emails' => $manualEmails,
            'created_by' => Auth::id(),
            'status' => 'scheduled',
        ]);

        if ($request->has('attendees')) {
            $meeting->attendees()->sync($request->attendees);
        }

        // Send invitation emails
        $this->sendNotificationEmails($meeting, false);

        return redirect()->route('meetings.index', $project)
            ->with('success', 'Meeting scheduled and invitation emails sent successfully!');
    }

    /**
     * Update/reschedule an existing meeting.
     */
    public function update(Request $request, Project $project, Meeting $meeting)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date',
            'meeting_link' => 'nullable|url|max:500',
            'attendees' => 'nullable|array',
            'attendees.*' => 'exists:users,id',
            'manual_emails' => 'nullable|string',
        ]);

        // Parse manual emails
        $manualEmails = [];
        if ($request->filled('manual_emails')) {
            $rawEmails = explode(',', $request->manual_emails);
            foreach ($rawEmails as $rawEmail) {
                $trimmed = trim($rawEmail);
                if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
                    $manualEmails[] = $trimmed;
                }
            }
        }

        $meeting->update([
            'title' => $request->title,
            'description' => $request->description,
            'scheduled_at' => $request->scheduled_at,
            'meeting_link' => $request->meeting_link,
            'additional_emails' => $manualEmails,
        ]);

        $meeting->attendees()->sync($request->input('attendees', []));

        // Send reschedule emails
        $this->sendNotificationEmails($meeting, true);

        return redirect()->route('meetings.index', $project)
            ->with('success', 'Meeting rescheduled and update emails sent successfully!');
    }

    /**
     * Update the status of a meeting.
     */
    public function updateStatus(Request $request, Project $project, Meeting $meeting)
    {
        $request->validate([
            'status' => 'required|string|in:scheduled,completed,cancelled',
        ]);

        $meeting->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Meeting status updated successfully!',
            'meeting_status' => $meeting->status,
        ]);
    }

    /**
     * Send notification emails to all attendees.
     */
    protected function sendNotificationEmails(Meeting $meeting, bool $isRescheduled)
    {
        $emails = collect();

        // 1. Gather attendee emails
        $meeting->load('attendees');
        foreach ($meeting->attendees as $attendee) {
            if ($attendee->email) {
                $emails->push($attendee->email);
            }
        }

        // 2. Gather manual emails
        if (is_array($meeting->additional_emails)) {
            foreach ($meeting->additional_emails as $email) {
                $emails->push($email);
            }
        }

        // 3. Make sure project owner and current user are also notified if relevant
        if ($meeting->project && $meeting->project->user && $meeting->project->user->email) {
            $emails->push($meeting->project->user->email);
        }

        // Get unique, non-empty emails
        $recipientEmails = $emails->unique()->filter()->values()->toArray();

        if (!empty($recipientEmails)) {
            try {
                Mail::to($recipientEmails)->send(new MeetingInvitationMail($meeting, $isRescheduled));
                Log::info("Meeting emails sent to: " . implode(', ', $recipientEmails));
            } catch (\Exception $e) {
                Log::error("Failed to send meeting notification email: " . $e->getMessage());
            }
        }
    }
}
