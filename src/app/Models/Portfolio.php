<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Portfolio extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'currency',
        'free_capital',
    ];

    protected function casts(): array
    {
        return [
            'free_capital' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function instruments(): BelongsToMany
    {
        return $this->belongsToMany(Instrument::class, 'portfolio_instrument')
            ->withPivot(['amount_invested', 'shares'])
            ->withTimestamps();
    }
}
