<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instrument extends Model
{
    protected $fillable = [
        'ticker',
        'company_name',
        'cik',
        'simfin_id',
        'exchange',
    ];

    public function indexes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Index::class, 'index_instrument')
            ->withPivot('added_manually')
            ->withTimestamps();
    }

    public function portfolios(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Portfolio::class, 'portfolio_instrument')
            ->withPivot(['amount_invested', 'shares'])
            ->withTimestamps();
    }
}
