<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GeneratorAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $generatorName,
        public string $alertType,      // 'critical_ticket' | 'high_ticket' | 'generator_failed'
        public string $title,
        public string $description,
        public string $severity,
        public string $detectedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[ALERT] {$this->severity} — {$this->generatorName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.generator-alert',
        );
    }
}