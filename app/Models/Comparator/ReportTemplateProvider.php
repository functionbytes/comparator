<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;

class ReportTemplateProvider extends Model
{
    protected $table = 'comparator_report_templates_categories_providers';

    protected $fillable = [
        'template_id',
        'provider_id',
    ];
}
