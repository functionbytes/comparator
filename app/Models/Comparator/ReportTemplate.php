<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplate extends Model
{
    protected $table = 'comparator_report_templates';
    public $timestamps = false;

    protected $fillable = [
        'comparator_id',
        'type_id',
        'title',
        'description',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_products_evaluated',
        'total_categories_evaluated',
        'total_competitors_found',
        'file_path',
        'error_message',
        'config',
        'filters',
        'export_formats',
        'retention_days',
        'compare_with_previous',
        'max_products',
        'include_out_of_stock',
        'available',
    ];

    protected $casts = [
        'config' => 'json',
        'filters' => 'json',
        'export_formats' => 'json',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_products_evaluated' => 'integer',
        'total_categories_evaluated' => 'integer',
        'total_competitors_found' => 'integer',
        'retention_days' => 'integer',
        'max_products' => 'integer',
        'compare_with_previous' => 'boolean',
        'include_out_of_stock' => 'boolean',
        'available' => 'integer',
    ];

    public function comparator(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator', 'comparator_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\ReportType', 'type_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany('App\Models\Comparator\ReportTemplateSchedule', 'template_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany('App\Models\Comparator\ReportNotification', 'template_id');
    }

    public function columnConfigs(): HasMany
    {
        return $this->hasMany('App\Models\Comparator\ReportTemplateColumnConfig', 'template_id');
    }

    public function colorRules(): HasMany
    {
        return $this->hasMany('App\Models\Comparator\ReportTemplateColorRule', 'template_id');
    }

    public function customMetrics(): HasMany
    {
        return $this->hasMany('App\Models\Comparator\ReportTemplateCustomMetric', 'template_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Category', 'comparator_report_template_categories', 'template_id', 'category_id');
    }

    public function competitors(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Competitor', 'comparator_report_template_competitors', 'template_id', 'competitor_id');
    }

    public function langs(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Lang', 'comparator_report_templates_categories_langs', 'template_id', 'lang_id');
    }

    public function providers(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Provider', 'comparator_report_templates_categories_providers', 'template_id', 'provider_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\ProductTag', 'comparator_report_templates_tags', 'template_id', 'tag_id');
    }
}
