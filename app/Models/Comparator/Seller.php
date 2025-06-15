<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seller extends Model
{
    protected $table = 'sellers';

    protected $fillable = [
        'uid',
        'competitor_id',
        'title',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo('App\Models\Competitor', 'competitor_id');
    }

}
