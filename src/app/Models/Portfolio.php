<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portfolio extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'currency',
        'free_capital',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'free_capital' => 'decimal:2',
            'is_system' => 'boolean',
        ];
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeForUser($query, ?int $userId)
    {
        return $query->where('user_id', $userId)->where('is_system', false);
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

    public function transactions(): HasMany
    {
        return $this->hasMany(PortfolioTransaction::class);
    }
}
