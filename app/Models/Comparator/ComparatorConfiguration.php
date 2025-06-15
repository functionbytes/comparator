<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComparatorConfiguration extends Model
{
    protected $table = 'comparators_configurations';

    protected $fillable = [
        'uid',
        'title',
        'comparator_id',
        'lang_id',
        'type',
        'code',
    ];

    public function comparator(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator', 'comparator_id');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo('App\Models\Lang', 'lang_id');
    }
}
