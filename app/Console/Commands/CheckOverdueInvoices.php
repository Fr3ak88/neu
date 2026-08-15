<?php

namespace App\Console\Commands;

use App\Models\Rechnung;
use Illuminate\Console\Command;

class CheckOverdueInvoices extends Command
{
    protected $signature = 'invoices:check-overdue';
    protected $description = 'Markiere überfällige Rechnungen automatisch';

    public function handle(): int
    {
        $updated = Rechnung::withoutGlobalScope('tenant')
            ->query()
            ->where('status', Rechnung::STATUS_VERSENDET)
            ->where('faelligkeitsdatum', '<', now()->toDateString())
            ->update(['status' => Rechnung::STATUS_UEBERFAELLIG]);

        $this->info("{$updated} Rechnung(en) als überfällig markiert.");

        return self::SUCCESS;
    }
}
