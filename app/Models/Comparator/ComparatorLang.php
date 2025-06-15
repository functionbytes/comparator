<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComparatorLang extends Model
{
    protected $table = 'comparators_lang';

    protected $fillable = [
        'comparator_id',
        'lang_id',
        'key',
    ];
}
