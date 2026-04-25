<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Index extends Model
{
    protected $table = 'indexes';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'filters',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'filters' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function instruments(): BelongsToMany
    {
        return $this->belongsToMany(Instrument::class, 'index_instrument')
            ->withPivot('added_manually')
            ->withTimestamps();
    }
}
