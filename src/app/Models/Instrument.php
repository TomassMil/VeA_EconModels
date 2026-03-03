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
}
