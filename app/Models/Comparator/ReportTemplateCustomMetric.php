<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplateCustomMetric extends Model
{
    protected $table = 'comparator_report_templates_custom_metrics';

    protected $fillable = [
        'template_id',
        'title',
        'equation',
        'type',
        'config',
    ];

    protected $casts = [
        'config' => 'json',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\ReportTemplate', 'template_id');
    }

}
