<?php

namespace App\Mail;

use App\Models\Rechnung;
use App\Models\Tenant;
use App\Services\ZugferdService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RechnungVersendetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Rechnung $rechnung,
        public Tenant $tenant,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Rechnung {$this->rechnung->rechnungsnummer}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.rechnung-versendet', [
                'rechnung' => $this->rechnung,
                'tenant'   => $this->tenant,
            ])->render(),
        );
    }

    public function attachments(): array
    {
        $service = new ZugferdService($this->tenant);
        $relativePath = $service->generatePdf($this->rechnung);
        $absolutePath = storage_path("app/{$relativePath}");

        return [
            \Illuminate\Mail\Mailables\Attachment::fromPath($absolutePath)
                ->as("Rechnung-{$this->rechnung->rechnungsnummer}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
