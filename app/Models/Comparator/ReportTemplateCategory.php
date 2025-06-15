<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;

class ReportTemplateCategory extends Model
{
    protected $table = 'comparator_report_template_categories';

    protected $fillable = [
        'template_id',
        'category_id',
    ];

}
