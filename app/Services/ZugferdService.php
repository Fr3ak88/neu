<?php

namespace App\Services;

use App\Models\Rechnung;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdDocumentPdfBuilder;
use horstoeko\zugferd\ZugferdProfiles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

class ZugferdService
{
    public function __construct(
        private readonly \App\Models\Tenant $tenant
    ) {}

    public function buildDocument(Rechnung $rechnung): ZugferdDocumentBuilder
    {
        $doc = ZugferdDocumentBuilder::createNew(ZugferdProfiles::PROFILE_XRECHNUNG_3);

        // ── Dokument-Info ────────────────────────────────────
        $doc->setDocumentInformation(
            $rechnung->rechnungsnummer,
            '380', // Rechnung (Invoice)
            $rechnung->datum,
            $rechnung->waehrung
        );

        $doc->setDocumentBusinessProcess('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0');

        if ($rechnung->leistungsdatum) {
            $doc->setDocumentBillingPeriod($rechnung->leistungsdatum, $rechnung->leistungsdatum, null);
            $doc->setDocumentSupplyChainEvent($rechnung->leistungsdatum);
        }

        // ── Verkäufer (Seller) ──────────────────────────────
        $doc->setDocumentSeller(
            $this->tenant->company ?? $this->tenant->name,
            null,
            null
        );

        $doc->setDocumentSellerAddress(
            $this->tenant->street,
            null,
            null,
            $this->tenant->zip,
            $this->tenant->city,
            $this->tenant->country ?? 'DE'
        );

        if ($this->tenant->ust_id) {
            $doc->addDocumentSellerId($this->tenant->ust_id);
            $doc->addDocumentSellerVATRegistrationNumber($this->tenant->ust_id);
        }
        if ($this->tenant->steuernummer) {
            $doc->addDocumentSellerTaxNumber($this->tenant->steuernummer);
        }

        // ── Verkäufer-Kontakt (BR-DE-2) ─────────────────────
        if ($this->tenant->name && $this->tenant->phone && $this->tenant->email) {
            $doc->setDocumentSellerContact(
                $this->tenant->name,
                null,
                $this->tenant->phone,
                null,
                $this->tenant->email
            );
        }

        if ($this->tenant->email) {
            $doc->setDocumentSellerCommunication('EM', $this->tenant->email);
        }

        // ── Käufer (Buyer) ──────────────────────────────────
        $buyerName = $rechnung->kunde_firma
            ? ($rechnung->kunde_name ? "{$rechnung->kunde_firma}, {$rechnung->kunde_name}" : $rechnung->kunde_firma)
            : ($rechnung->kunde_name ?? 'Kunde');

        $doc->setDocumentBuyer($buyerName, null, null);

        $doc->setDocumentBuyerAddress(
            $rechnung->kunde_strasse,
            null,
            null,
            $rechnung->kunde_plz,
            $rechnung->kunde_ort,
            $rechnung->kunde_land ?? 'DE'
        );

        if ($rechnung->kunde_steuernummer) {
            $doc->addDocumentBuyerTaxRegistration('FC', $rechnung->kunde_steuernummer);
        }

        if ($rechnung->intern_ref) {
            $doc->setDocumentBuyerReference($rechnung->intern_ref ?: '-');
        }

        if ($rechnung->kunde_email) {
            $doc->setDocumentBuyerCommunication('EM', $rechnung->kunde_email);
        }

        // ── Zahlungsinformation ──────────────────────────────
        $doc->setDocumentGeneralPaymentInformation(null, $this->getPaymentReference($rechnung));

        $ibanToUse = $rechnung->iban ?: $this->tenant->iban;
        $bicToUse  = $rechnung->bic ?: $this->tenant->bic;
        $bankNameToUse = $rechnung->bank_name ?: $this->tenant->bank_name;

        if ($ibanToUse) {
            $doc->addDocumentPaymentMeanToCreditTransfer(
                $ibanToUse,
                $bankNameToUse,
                null,
                $bicToUse,
                $this->getPaymentReference($rechnung)
            );
        }

        $doc->addDocumentPaymentTerm(
            'Zahlbar innerhalb 30 Tagen netto',
            $rechnung->faelligkeitsdatum
        );

        // ── Positionen ───────────────────────────────────────
        foreach ($rechnung->positions as $pos) {
            $doc->addNewPosition((string) $pos->position);

            $doc->setDocumentPositionProductDetails(
                $pos->beschreibung
            );

            $doc->setDocumentPositionQuantity(
                (float) $pos->menge,
                $this->mapUnit($pos->einheit)
            );

            $doc->setDocumentPositionNetPrice(
                (float) $pos->einzelpreis,
                1.0,
                $this->mapUnit($pos->einheit)
            );

            $taxAmount = round($pos->nettobetrag * $pos->steuersatz / 100, 2);

            $doc->addDocumentPositionTax(
                'S',
                'VAT',
                (float) $pos->steuersatz,
            );

            $doc->setDocumentPositionLineSummationExt(
                $pos->nettobetrag,
                null,
                null,
                null,
                round($pos->nettobetrag + $taxAmount, 2),
                null
            );
        }

        // ── Summen ───────────────────────────────────────────
        $doc->addDocumentTaxSimple(
            'S',
            'VAT',
            $rechnung->nettobetrag,
            $rechnung->steuerbetrag,
            (float) $rechnung->steuersatz
        );

        $doc->setDocumentSummation(
            $rechnung->bruttobetrag,
            $rechnung->bruttobetrag,
            $rechnung->nettobetrag,
            null,
            null,
            $rechnung->nettobetrag,
            $rechnung->steuerbetrag
        );

        return $doc;
    }

    public function generateXml(Rechnung $rechnung): string
    {
        return (string) $this->buildDocument($rechnung);
    }

    public function generatePdf(Rechnung $rechnung): string
    {
        $relativePath = "invoices/{$rechnung->rechnungsnummer}.pdf";
        $absolutePath = storage_path("app/{$relativePath}");

        if (File::exists($absolutePath)) {
            return $relativePath;
        }

        File::ensureDirectoryExists(dirname($absolutePath));

        $doc = $this->buildDocument($rechnung);

        $html = view('rechnungen.pdf', [
            'rechnung'         => $rechnung,
            'tenant'           => $this->tenant,
            'positions'        => $rechnung->positions,
            'paymentReference' => $this->getPaymentReference($rechnung),
            'qrCodeDataUri'    => $this->generateQrCodeDataUri($rechnung),
        ])->render();

        $pdf = Pdf::loadHtml($html)
            ->setPaper('a4')
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'sans-serif')
            ->output();

        $docPdf = ZugferdDocumentPdfBuilder::fromPdfString($doc, $pdf);
        $docPdf->generateDocument();
        $docPdf->saveDocument($absolutePath);

        return $relativePath;
    }

    public function generateStornoPdf(Rechnung $storno): string
    {
        $originalRechnung = $storno->stornoVon;
        $relativePath = "invoices/{$storno->rechnungsnummer}.pdf";
        $absolutePath = storage_path("app/{$relativePath}");

        File::ensureDirectoryExists(dirname($absolutePath));

        $html = view('rechnungen.storno-pdf', [
            'rechnung'         => $storno,
            'originalRechnung' => $originalRechnung,
            'tenant'           => $this->tenant,
            'positions'        => $storno->positions,
        ])->render();

        $pdf = Pdf::loadHtml($html)
            ->setPaper('a4')
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'sans-serif')
            ->output();

        file_put_contents($absolutePath, $pdf);

        return $relativePath;
    }

    private function mapUnit(string $unit): string
    {
        return match(strtolower($unit)) {
            'stk', 'st', 'stück', 'pcs' => 'C62',
            'kg'                          => 'KGM',
            'g'                           => 'GRM',
            'm', 'meter'                  => 'MTR',
            'cm'                          => 'CMT',
            'l', 'liter'                  => 'LTR',
            'ml'                          => 'MLT',
            default                       => 'C62',
        };
    }

    private function getPaymentReference(Rechnung $rechnung): string
    {
        return $rechnung->intern_ref ?: $rechnung->rechnungsnummer;
    }

    private function generateQrCodeDataUri(Rechnung $rechnung): string
    {
        $payload = $this->buildGiroPayload($rechnung);

        $qrCode = new QrCode(
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 200,
            margin: 10,
        );

        $result = (new PngWriter())->write($qrCode);

        return 'data:' . $result->getMimeType() . ';base64,' . base64_encode($result->getString());
    }

    private function buildGiroPayload(Rechnung $rechnung): string
    {
        // $reference = $this->getPaymentReference($rechnung);
        $amountNumeric = number_format((float) $rechnung->bruttobetrag, 2, '.', '');

        // Normalize creditor name, IBAN and BIC
        $creditor = substr(($this->tenant->name) ?? '', 0, 70);
        $iban = $this->tenant->iban ? preg_replace('/\s+/', '', strtoupper($this->tenant->iban)) : '';
        $bic = $this->tenant->bic ? preg_replace('/\s+/', '', strtoupper($this->tenant->bic)) : '';

        // Use version 001 (more widely supported)
            // Correct GiroCode order: BIC, Creditor name, IBAN, Amount, Purpose, Remittance
            $giro = [
                'BCD',
                '001',
                '1',
                'SCT',
                $bic,
                $creditor,
                $iban,
                'EUR' . $amountNumeric,
                '', // purpose (optional)
                substr($rechnung->rechnungsnummer, 0, 140),
            ];

        // Ensure explicit empty lines are preserved and use CRLF line endings (some banks expect CRLF)
        return implode("\r\n", $giro) . "\r\n";
    }
}
