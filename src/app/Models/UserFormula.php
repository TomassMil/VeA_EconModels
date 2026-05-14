<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFormula extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'formula',
        'top_n',
    ];

    protected function casts(): array
    {
        return [
            'top_n' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
