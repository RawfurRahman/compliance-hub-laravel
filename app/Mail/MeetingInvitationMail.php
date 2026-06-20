<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $meeting;
    public $isRescheduled;

    /**
     * Create a new message instance.
     */
    public function __construct(Meeting $meeting, bool $isRescheduled = false)
    {
        $this->meeting = $meeting;
        $this->isRescheduled = $isRescheduled;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $prefix = $this->isRescheduled ? 'RESCHEDULED: ' : 'Invitation: ';
        return new Envelope(
            subject: $prefix . 'ComplianceHub Meeting - ' . $this->meeting->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.meeting-invitation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
