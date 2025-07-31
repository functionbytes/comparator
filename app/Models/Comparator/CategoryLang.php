<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryLang extends Model
{
    protected $table = 'categories_lang';

    protected $fillable = [
        'category_id',
        'lang_id',
    ];

}
