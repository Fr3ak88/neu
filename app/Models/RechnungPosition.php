<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RechnungPosition extends Model
{
    protected $fillable = [
        'rechnung_id',
        'position',
        'beschreibung',
        'menge',
        'einheit',
        'einzelpreis',
        'nettobetrag',
        'steuersatz',
        'rabatt',
        'notizen',
    ];

    protected $casts = [
        'menge'       => 'decimal:2',
        'einzelpreis' => 'decimal:2',
        'nettobetrag' => 'decimal:2',
        'steuersatz'  => 'decimal:2',
        'rabatt'      => 'decimal:2',
    ];

    public function rechnung(): BelongsTo
    {
        return $this->belongsTo(Rechnung::class);
    }

    public function getZeilenbetragAttribute(): float
    {
        return $this->menge * $this->einzelpreis;
    }
}
