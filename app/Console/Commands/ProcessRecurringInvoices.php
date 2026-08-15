<?php

namespace App\Console\Commands;

use App\Models\RechnungAuftrag;
use Illuminate\Console\Command;

class ProcessRecurringInvoices extends Command
{
    protected $signature = 'invoices:process-recurring';
    protected $description = 'Erstellt Rechnungen aus fälligen wiederkehrenden Aufträgen';

    public function handle(): int
    {
        $auftraege = RechnungAuftrag::withoutGlobalScope('tenant')
            ->query()
            ->where('typ', 'wiederkehrend')
            ->where('aktiv', true)
            ->where('naechste_erstellung', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('enddatum')
                  ->orWhere('enddatum', '>=', now()->toDateString());
            })
            ->get();

        $count = 0;

        foreach ($auftraege as $auftrag) {
            try {
                $rechnung = $auftrag->erstelleRechnung();
                $this->info("  ✓ Rechnung {$rechnung->rechnungsnummer} erstellt aus Auftrag \"{$auftrag->bezeichnung}\"");
                $count++;
            } catch (\Throwable $e) {
                $this->error("  ✗ Fehler bei Auftrag \"{$auftrag->bezeichnung}\": {$e->getMessage()}");
            }
        }

        $this->info("{$count} Rechnung(en) erstellt.");

        return self::SUCCESS;
    }
}
