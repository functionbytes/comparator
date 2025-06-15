<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $table = 'currencies';

    protected $fillable = [
        'uid',
        'title',
        'iso_code',
        'numeric_iso_code',
        'precision',
        'conversion_rate',
        'available',
    ];

}
