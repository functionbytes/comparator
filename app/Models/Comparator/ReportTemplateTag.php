<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;

class ReportTemplateTag extends Model
{
    protected $table = 'comparator_report_templates_tags';

    protected $fillable = [
        'template_id',
        'tag_id',
    ];
}
