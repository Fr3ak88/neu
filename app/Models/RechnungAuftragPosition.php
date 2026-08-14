<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RechnungAuftragPosition extends Model
{
    protected $table = 'rechnung_auftrag_positions';

    protected $fillable = [
        'rechnung_auftrag_id',
        'position',
        'beschreibung',
        'menge',
        'einheit',
        'einzelpreis',
        'steuersatz',
        'rabatt',
        'notizen',
    ];

    protected $casts = [
        'menge'       => 'decimal:2',
        'einzelpreis' => 'decimal:2',
        'steuersatz'  => 'decimal:2',
        'rabatt'      => 'decimal:2',
    ];

    public function auftrag(): BelongsTo
    {
        return $this->belongsTo(RechnungAuftrag::class, 'rechnung_auftrag_id');
    }

    public function getNettobetragAttribute(): float
    {
        $basis = $this->menge * $this->einzelpreis;
        return round($basis * (1 - $this->rabatt / 100), 2);
    }
}
