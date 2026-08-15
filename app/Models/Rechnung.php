<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rechnung extends Model
{
    use BelongsToTenant;
    protected $table = 'rechnungen';

    const STATUS_DRAFT      = 'draft';
    const STATUS_BESTAETIGT = 'bestaetigt';
    const STATUS_VERSENDET  = 'versendet';
    const STATUS_BEZAHLT    = 'bezahlt';
    const STATUS_UEBERFAELLIG = 'ueberfaellig';
    const STATUS_STORNIERT  = 'storniert';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_BESTAETIGT,
        self::STATUS_VERSENDET,
        self::STATUS_BEZAHLT,
        self::STATUS_UEBERFAELLIG,
        self::STATUS_STORNIERT,
    ];

    protected $fillable = [
        'tenant_id',
        'rechnungsnummer',
        'rechnung_auftrag_id',
        'kunde_name',
        'kunde_firma',
        'kunde_email',
        'kunde_strasse',
        'kunde_plz',
        'kunde_ort',
        'kunde_land',
        'kunde_steuernummer',
        'datum',
        'faelligkeitsdatum',
        'leistungsdatum',
        'status',
        'bezahldatum',
        'mahnungen_count',
        'last_mahnung_at',
        'mahnung_notizen',
        'waehrung',
        'nettobetrag',
        'steuerbetrag',
        'bruttobetrag',
        'steuersatz',
        'ust_id',
        'steuernummer',
        'bank_name',
        'iban',
        'bic',
        'notizen',
        'intern_ref',
        'pdf_path',
        'storno_von_id',
        'storno_pdf_path',
        'ist_storno',
    ];

    protected $casts = [
        'datum'             => 'date',
        'faelligkeitsdatum' => 'date',
        'leistungsdatum'    => 'date',
        'bezahldatum'       => 'date',
        'last_mahnung_at'   => 'datetime',
        'nettobetrag'       => 'decimal:2',
        'steuerbetrag'      => 'decimal:2',
        'bruttobetrag'      => 'decimal:2',
        'steuersatz'        => 'decimal:2',
        'ist_storno'        => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->rechnungsnummer)) {
                $year = now()->format('Y');
                $prefix = $model->ist_storno ? 'SRE' : 'RE';

                $maxNum = static::where('rechnungsnummer', 'like', $prefix . '-' . $year . '-%')
                    ->selectRaw("MAX(CAST(SUBSTRING(rechnungsnummer, " . (strlen($prefix) + 7) . ") AS UNSIGNED)) as max_num")
                    ->value('max_num') ?? 0;

                $model->rechnungsnummer = $prefix . '-' . $year . '-' . str_pad($maxNum + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Beziehungen ──────────────────────────────────────────

    public function auftrag(): BelongsTo
    {
        return $this->belongsTo(RechnungAuftrag::class, 'rechnung_auftrag_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(RechnungPosition::class)->orderBy('position');
    }

    public function stornoVon(): BelongsTo
    {
        return $this->belongsTo(Rechnung::class, 'storno_von_id');
    }

    public function stornoBeleg(): HasMany
    {
        return $this->hasMany(Rechnung::class, 'storno_von_id');
    }

    // ── Status-Helpers ───────────────────────────────────────

    public function isDraft(): bool        { return $this->status === self::STATUS_DRAFT; }
    public function isBestaetigt(): bool   { return $this->status === self::STATUS_BESTAETIGT; }
    public function isVersendet(): bool    { return $this->status === self::STATUS_VERSENDET; }
    public function isBezahlt(): bool      { return $this->status === self::STATUS_BEZAHLT; }
    public function isUeberfaellig(): bool { return $this->status === self::STATUS_UEBERFAELLIG; }
    public function isStorniert(): bool    { return $this->status === self::STATUS_STORNIERT; }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT]);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_VERSENDET
            && $this->faelligkeitsdatum->isPast();
    }

    public function markAsBezahlt(?string $datum = null): void
    {
        $this->update([
            'status'      => self::STATUS_BEZAHLT,
            'bezahldatum' => $datum ?? now()->toDateString(),
        ]);
    }

    public function sendMahnung(?string $notizen = null): void
    {
        $this->update([
            'status'           => self::STATUS_UEBERFAELLIG,
            'mahnungen_count'  => $this->mahnungen_count + 1,
            'last_mahnung_at'  => now(),
            'mahnung_notizen'  => $notizen ?? $this->mahnung_notizen,
        ]);
    }

    public function createStornoBeleg(): self
    {
        $storno = new self();
        $storno->ist_storno = true;
        $storno->storno_von_id = $this->id;
        $storno->status = self::STATUS_STORNIERT;
        $storno->datum = now()->toDateString();
        $storno->faelligkeitsdatum = now()->toDateString();
        $storno->leistungsdatum = $this->leistungsdatum;

        // Kundendaten übernehmen
        $storno->kunde_name = $this->kunde_name;
        $storno->kunde_firma = $this->kunde_firma;
        $storno->kunde_email = $this->kunde_email;
        $storno->kunde_strasse = $this->kunde_strasse;
        $storno->kunde_plz = $this->kunde_plz;
        $storno->kunde_ort = $this->kunde_ort;
        $storno->kunde_land = $this->kunde_land;
        $storno->kunde_steuernummer = $this->kunde_steuernummer;

        // Beträge negieren
        $storno->nettobetrag = -$this->nettobetrag;
        $storno->steuerbetrag = -$this->steuerbetrag;
        $storno->bruttobetrag = -$this->bruttobetrag;
        $storno->steuersatz = $this->steuersatz;
        $storno->waehrung = $this->waehrung;

        // Bankdaten übernehmen
        $storno->bank_name = $this->bank_name;
        $storno->iban = $this->iban;
        $storno->bic = $this->bic;
        $storno->ust_id = $this->ust_id;
        $storno->steuernummer = $this->steuernummer;

        $storno->notizen = "Storno zu {$this->rechnungsnummer}";
        $storno->intern_ref = $this->intern_ref;

        // Rechnungsnummer wird automatisch generiert (Booted)
        $storno->save();

        // Positionen kopieren (negiert)
        foreach ($this->positions as $pos) {
            $storno->positions()->create([
                'position'    => $pos->position,
                'beschreibung' => $pos->beschreibung,
                'menge'       => $pos->menge,
                'einheit'     => $pos->einheit,
                'einzelpreis' => $pos->einzelpreis,
                'nettobetrag' => -$pos->nettobetrag,
                'steuersatz'  => $pos->steuersatz,
                'rabatt'      => $pos->rabatt,
                'notizen'     => $pos->notizen,
            ]);
        }

        return $storno;
    }

    public function daysOverdue(): ?int
    {
        if (!$this->isOverdue() && !$this->isUeberfaellig()) {
            return null;
        }

        return (int) $this->faelligkeitsdatum->diffInDays(now());
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT        => 'Entwurf',
            self::STATUS_BESTAETIGT   => 'Bestätigt',
            self::STATUS_VERSENDET    => 'Versendet',
            self::STATUS_BEZAHLT      => 'Bezahlt',
            self::STATUS_UEBERFAELLIG => 'Überfällig',
            self::STATUS_STORNIERT    => 'Storniert',
            default => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT        => 'status-pending',
            self::STATUS_BESTAETIGT   => 'status-warn',
            self::STATUS_VERSENDET    => 'status-ok',
            self::STATUS_BEZAHLT      => 'status-ok',
            self::STATUS_UEBERFAELLIG => 'status-error',
            self::STATUS_STORNIERT    => 'status-error',
            default => 'status-pending',
        };
    }

    // ── Summen berechnen ─────────────────────────────────────

    public function recalculate(): void
    {
        $this->nettobetrag  = $this->positions->sum('nettobetrag');
        $this->steuerbetrag = $this->positions->sum(fn($p) => $p->nettobetrag * $p->steuersatz / 100);
        $this->bruttobetrag = $this->nettobetrag + $this->steuerbetrag;
        $this->save();
    }

    public function steuerAufschluesselung(): array
    {
        $gruppen = [];

        foreach ($this->positions as $pos) {
            $satz = (float) $pos->steuersatz;

            if (!isset($gruppen[$satz])) {
                $gruppen[$satz] = [
                    'satz'   => $satz,
                    'netto'  => 0.0,
                    'steuer' => 0.0,
                ];
            }

            $gruppen[$satz]['netto']  += (float) $pos->nettobetrag;
            $gruppen[$satz]['steuer'] += round((float) $pos->nettobetrag * $satz / 100, 2);
        }

        ksort($gruppen);

        return array_values($gruppen);
    }

    // ── Formatierung ─────────────────────────────────────────

    public function getBetragFormattedAttribute(): string
    {
        return number_format($this->bruttobetrag, 2, ',', '.') . ' €';
    }
}
