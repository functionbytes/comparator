<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competitor extends Model
{
    protected $table = 'competitors';

    protected $fillable = [
        'uid',
        'title',
        'available',
    ];

    protected $casts = [
        'available' => 'integer',
    ];

    public function sellers(): HasMany
    {
        return $this->hasMany('App\Models\Seller', 'competitor_id');
    }
}
