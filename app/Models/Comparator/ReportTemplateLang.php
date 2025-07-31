<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;

class ReportTemplateLang extends Model
{
    protected $table = 'comparator_report_templates_categories_langs';

    protected $fillable = [
        'template_id',
        'lang_id',
    ];

}
