<?php

namespace App\Mail;

use App\Models\Rechnung;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MahnungMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Rechnung $rechnung,
        public Tenant $tenant,
        public ?string $customText = null,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->rechnung->mahnungen_count + 1;

        return new Envelope(
            subject: "Mahnung {$count} — Rechnung {$this->rechnung->rechnungsnummer}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.mahnung', [
                'rechnung'   => $this->rechnung,
                'tenant'     => $this->tenant,
                'customText' => $this->customText,
            ])->render(),
        );
    }

    public function attachments(): array
    {
        $service = new ZugferdService($this->tenant);
        $filePath = $service->generatePdf($this->rechnung);

        return [
            \Illuminate\Mail\Mailables\Attachment::fromStoragePath("app/rechnung-{$this->rechnung->rechnungsnummer}.pdf")
                ->as("Rechnung-{$this->rechnung->rechnungsnummer}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
