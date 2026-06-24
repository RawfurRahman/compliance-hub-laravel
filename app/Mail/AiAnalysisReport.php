<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AiAnalysisReport extends Mailable
{
    use Queueable, SerializesModels;

    public string $observations;
    public string $recommendations;
    public string $fileName;

    public function __construct(string $observations, string $recommendations, string $fileName)
    {
        $this->observations = $observations;
        $this->recommendations = $recommendations;
        $this->fileName = $fileName;
    }

    public function build(): self
    {
        return $this->subject("AI Analysis Report: {$this->fileName}")
            ->markdown('emails.ai-analysis-report');
    }
}
