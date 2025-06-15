<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplateRule extends Model
{
    protected $table = 'comparator_report_templates_rules';
    public $incrementing = false;

    protected $fillable = [
        'template_id',
        'name',
        'apply_to',
        'condition_field',
        'condition_operator',
        'condition_value1',
        'condition_value2',
        'background_color',
        'text_color',
        'is_bold',
        'priority',
        'available',
    ];

    protected $casts = [
        'condition_value1' => 'decimal:2',
        'condition_value2' => 'decimal:2',
        'is_bold' => 'boolean',
        'priority' => 'integer',
        'available' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\ReportTemplate', 'template_id');
    }
}
