<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;

class ReportTemplateCompetitor extends Model
{
    protected $table = 'comparator_report_template_competitors';

    protected $fillable = [
        'template_id',
        'competitor_id',
        'primary',
    ];

    protected $casts = [
        'primary' => 'boolean',
    ];

}
