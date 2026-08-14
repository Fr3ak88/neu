<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RechnungAuftrag extends Model
{
    protected $table = 'rechnung_auftraege';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (RechnungAuftrag $model) {
            if (empty($model->auftragsnummer)) {
                $model->auftragsnummer = self::naechsteAuftragsnummer();
            }
        });
    }

    public static function naechsteAuftragsnummer(): string
    {
        $year = date('Y');
        $prefix = 'AF-' . $year . '-';
        $last = self::where('auftragsnummer', 'like', $prefix . '%')
            ->orderByDesc('auftragsnummer')
            ->value('auftragsnummer');

        if ($last) {
            $next = (int) substr($last, -4) + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    const TYP_EINMALIG       = 'einmalig';
    const TYP_WIEDERKEHREND  = 'wiederkehrend';

    const TYPEN = [
        self::TYP_EINMALIG      => 'Einmalig',
        self::TYP_WIEDERKEHREND => 'Wiederkehrend',
    ];

    const INTERVALL_WOECHENTLICH  = 'woechentlich';
    const INTERVALL_MONATLICH     = 'monatlich';
    const INTERVALL_VIERTELJAHR  = 'vierteljaehrlich';
    const INTERVALL_JAEHRLICH    = 'jaehrlich';

    const INTERVALLE = [
        self::INTERVALL_WOECHENTLICH  => 'Wöchentlich',
        self::INTERVALL_MONATLICH     => 'Monatlich',
        self::INTERVALL_VIERTELJAHR  => 'Vierteljährlich',
        self::INTERVALL_JAEHRLICH    => 'Jährlich',
    ];

    protected $fillable = [
        'auftragsnummer',
        'bezeichnung',
        'typ',
        'customer_id',
        'kunde_name',
        'kunde_firma',
        'kunde_email',
        'kunde_strasse',
        'kunde_plz',
        'kunde_ort',
        'kunde_land',
        'kunde_steuernummer',
        'intervall',
        'faelligkeit_tage',
        'steuersatz',
        'notizen',
        'startdatum',
        'enddatum',
        'naechste_erstellung',
        'letzte_erstellung',
        'erstellt_count',
        'aktiv',
    ];

    protected $casts = [
        'startdatum'          => 'date',
        'enddatum'            => 'date',
        'naechste_erstellung' => 'date',
        'letzte_erstellung'   => 'date',
        'steuersatz'          => 'decimal:2',
        'aktiv'               => 'boolean',
    ];

    // ── Beziehungen ──────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(RechnungAuftragPosition::class, 'rechnung_auftrag_id')->orderBy('position');
    }

    public function rechnungen(): HasMany
    {
        return $this->hasMany(Rechnung::class, 'rechnung_auftrag_id');
    }

    // ── Logik ────────────────────────────────────────────────

    public function isEinmalig(): bool
    {
        return $this->typ === self::TYP_EINMALIG;
    }

    public function isWiederkehrend(): bool
    {
        return $this->typ === self::TYP_WIEDERKEHREND;
    }

    public function intervallLabel(): string
    {
        return self::INTERVALLE[$this->intervall] ?? $this->intervall ?? '';
    }

    public function isFaelig(): bool
    {
        return $this->aktiv
            && !$this->isAbgelaufen()
            && $this->naechste_erstellung->lte(now());
    }

    public function isAbgelaufen(): bool
    {
        return $this->enddatum && $this->enddatum->isPast();
    }

    public function statusLabel(): string
    {
        if ($this->isEinmalig()) {
            return 'Erstellt';
        }

        if ($this->isAbgelaufen()) {
            return 'Abgelaufen';
        }

        return $this->aktiv ? 'Aktiv' : 'Pausiert';
    }

    public function statusClass(): string
    {
        if ($this->isEinmalig()) {
            return 'status-info';
        }

        if ($this->isAbgelaufen()) {
            return 'status-pending';
        }

        return $this->aktiv ? 'status-ok' : 'status-warn';
    }

    public function naechsteErstellungBerechnen(): \Carbon\Carbon
    {
        return match($this->intervall) {
            self::INTERVALL_WOECHENTLICH  => $this->naechste_erstellung->copy()->addWeek(),
            self::INTERVALL_MONATLICH     => $this->naechste_erstellung->copy()->addMonth(),
            self::INTERVALL_VIERTELJAHR  => $this->naechste_erstellung->copy()->addMonths(3),
            self::INTERVALL_JAEHRLICH    => $this->naechste_erstellung->copy()->addYear(),
            default => $this->naechste_erstellung->copy()->addMonth(),
        };
    }

    public function monatlicherBetrag(): float
    {
        $brutto = $this->positions->sum(fn($p) => $p->menge * $p->einzelpreis * (1 - $p->rabatt / 100) * (1 + $p->steuersatz / 100));
        return match($this->intervall) {
            self::INTERVALL_WOECHENTLICH  => $brutto * 4.33,
            self::INTERVALL_MONATLICH     => $brutto,
            self::INTERVALL_VIERTELJAHR  => $brutto / 3,
            self::INTERVALL_JAEHRLICH    => $brutto / 12,
        };
    }

    // ── Summen berechnen ─────────────────────────────────────

    public function nettobetrag(): float
    {
        return round($this->positions->sum(fn($p) => $p->menge * $p->einzelpreis * (1 - $p->rabatt / 100)), 2);
    }

    public function steuerbetrag(): float
    {
        return round($this->positions->sum(fn($p) => $p->menge * $p->einzelpreis * (1 - $p->rabatt / 100) * $p->steuersatz / 100), 2);
    }

    public function bruttobetrag(): float
    {
        return $this->nettobetrag() + $this->steuerbetrag();
    }

    public function steuerAufschluesselung(): array
    {
        $gruppen = [];

        foreach ($this->positions as $pos) {
            $satz = (float) $pos->steuersatz;
            $netto = round((float) $pos->menge * (float) $pos->einzelpreis * (1 - (float) $pos->rabatt / 100), 2);

            if (!isset($gruppen[$satz])) {
                $gruppen[$satz] = [
                    'satz'   => $satz,
                    'netto'  => 0.0,
                    'steuer' => 0.0,
                ];
            }

            $gruppen[$satz]['netto']  += $netto;
            $gruppen[$satz]['steuer'] += round($netto * $satz / 100, 2);
        }

        ksort($gruppen);

        return array_values($gruppen);
    }

    // ── Rechnung erstellen ───────────────────────────────────

    public function erstelleRechnung(): Rechnung
    {
        $rechnung = Rechnung::create([
            'rechnung_auftrag_id' => $this->id,
            'kunde_name'        => $this->kunde_name,
            'kunde_firma'       => $this->kunde_firma,
            'kunde_email'       => $this->kunde_email,
            'kunde_strasse'     => $this->kunde_strasse,
            'kunde_plz'         => $this->kunde_plz,
            'kunde_ort'         => $this->kunde_ort,
            'kunde_land'        => $this->kunde_land ?? 'DE',
            'kunde_steuernummer'=> $this->kunde_steuernummer,
            'datum'             => now()->toDateString(),
            'faelligkeitsdatum' => now()->addDays((int) $this->faelligkeit_tage)->toDateString(),
            'leistungsdatum'    => now()->toDateString(),
            'steuersatz'        => $this->steuersatz,
            'notizen'           => $this->notizen,
            'intern_ref'        => $this->bezeichnung,
        ]);

        foreach ($this->positions as $pos) {
            $netto = round($pos->menge * $pos->einzelpreis * (1 - $pos->rabatt / 100), 2);
            $rechnung->positions()->create([
                'position'     => $pos->position,
                'beschreibung' => $pos->beschreibung,
                'menge'        => $pos->menge,
                'einheit'      => $pos->einheit,
                'einzelpreis'  => $pos->einzelpreis,
                'nettobetrag'  => $netto,
                'steuersatz'   => $pos->steuersatz,
                'rabatt'       => $pos->rabatt,
                'notizen'      => $pos->notizen,
            ]);
        }

        $rechnung->recalculate();

        if ($this->isEinmalig()) {
            $this->update([
                'aktiv'             => false,
                'letzte_erstellung' => now()->toDateString(),
                'erstellt_count'    => $this->erstellt_count + 1,
            ]);
        } else {
            $this->update([
                'letzte_erstellung'   => now()->toDateString(),
                'naechste_erstellung' => $this->naechsteErstellungBerechnen()->toDateString(),
                'erstellt_count'      => $this->erstellt_count + 1,
            ]);
        }

        return $rechnung;
    }
}
