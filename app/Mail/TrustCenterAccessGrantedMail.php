<?php

namespace App\Mail;

use App\Modules\TrustCenter\Models\TrustCenter;
use App\Modules\TrustCenter\Models\TrustCenterAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrustCenterAccessGrantedMail extends Mailable
{
    use Queueable, SerializesModels;

    public TrustCenter $trustCenter;
    public TrustCenterAccessRequest $accessRequest;

    public function __construct(TrustCenter $trustCenter, TrustCenterAccessRequest $accessRequest)
    {
        $this->trustCenter = $trustCenter;
        $this->accessRequest = $accessRequest;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Access Granted: {$this->trustCenter->headline}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trust-center-access-granted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
