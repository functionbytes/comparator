<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryLang extends Model
{
    protected $table = 'countries_lang';

    protected $fillable = [
        'country_id',
        'lang_id',
    ];

}
