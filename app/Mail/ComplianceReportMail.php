<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComplianceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $projectName;
    public string $reportLabel;
    public ?string $messageBody;
    protected array $attachmentsData;

    /**
     * Create a new message instance.
     */
    public function __construct(string $projectName, string $reportLabel, ?string $messageBody = null, array $attachmentsData = [])
    {
        $this->projectName = $projectName;
        $this->reportLabel = $reportLabel;
        $this->messageBody = $messageBody;
        $this->attachmentsData = $attachmentsData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "ComplianceHub: {$this->reportLabel} for Project {$this->projectName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.compliance-report',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->attachmentsData as $fileInfo) {
            $attachments[] = Attachment::fromData(
                fn () => $fileInfo['data'],
                $fileInfo['name']
            )->withMime($fileInfo['mime']);
        }

        return $attachments;
    }
}
