<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioTransaction extends Model
{
    protected $fillable = [
        'portfolio_id',
        'instrument_id',
        'type',
        'transaction_date',
        'shares',
        'price_per_share',
        'amount',
        'currency',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'shares' => 'decimal:6',
            'price_per_share' => 'decimal:6',
            'amount' => 'decimal:2',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }
}
