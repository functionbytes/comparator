<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplateColumnConfig extends Model
{
    protected $table = 'comparator_report_templates_columns_config';

    protected $fillable = [
        'template_id',
        'column_key',
        'column_label',
        'display_order',
        'column_type',
        'available',
        'formatting_rules',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'available' => 'integer',
        'formatting_rules' => 'json',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\ReportTemplate', 'template_id');
    }

}
