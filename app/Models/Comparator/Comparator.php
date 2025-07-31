<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comparator extends Model
{
    protected $table = 'comparators';

    protected $fillable = [
        'uid',
        'title',
        'api_key',
        'api_secret',
        'api_username',
        'api_config',
        'available',
    ];

    protected $casts = [
        'api_config' => 'json',
        'available' => 'integer',
    ];

    public function configurations(): HasMany
    {
        return $this->hasMany('App\Models\ComparatorConfiguration', 'comparator_id');
    }

    public function langs(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Lang', 'comparators_lang', 'comparator_id', 'lang_id');
    }
}
