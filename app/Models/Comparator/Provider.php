<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $table = 'providers';

    protected $fillable = [
        'uid',
        'title',
        'available',
    ];

    protected $casts = [
        'available' => 'integer',
    ];

}
