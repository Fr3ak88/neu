<?php

namespace App\Mail;

use App\Models\RechnungAuftrag;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuftragVersendetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RechnungAuftrag $auftrag,
        public Tenant $tenant,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Auftrag {$this->auftrag->auftragsnummer}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.auftrag-versendet', [
                'auftrag' => $this->auftrag,
                'tenant'  => $this->tenant,
            ])->render(),
        );
    }

    public function attachments(): array
    {
        $html = view('rechnungen.auftraege.pdf', [
            'auftrag'   => $this->auftrag,
            'tenant'    => $this->tenant,
            'positions' => $this->auftrag->positions,
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHtml($html)
            ->setPaper('a4')
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'sans-serif')
            ->output();

        $tempFile = tempnam(sys_get_temp_dir(), 'auftrag_');
        file_put_contents($tempFile, $pdf);

        return [
            \Illuminate\Mail\Mailables\Attachment::fromPath($tempFile)
                ->as("Auftrag-{$this->auftrag->auftragsnummer}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
