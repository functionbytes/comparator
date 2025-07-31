<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Report extends Model
{
    protected $table = 'comparator_reports';

    protected $fillable = [
        'comparator_id',
        'lang_id',
        'type_id',
        'template_id',
        'schedule_id',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_products_evaluated',
        'total_competitors_found',
        'file_path',
        'error_message',
        'summary',
        'priority',
        'price_suggestions',
        'margin_analysis',
        'avg_competitor_price',
        'min_competitor_price',
        'max_competitor_price',
    ];

    protected $casts = [
        'summary' => 'json',
        'price_suggestions' => 'json',
        'margin_analysis' => 'json',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_products_evaluated' => 'integer',
        'total_competitors_found' => 'integer',
        'priority' => 'integer',
        'avg_competitor_price' => 'decimal:2',
        'min_competitor_price' => 'decimal:2',
        'max_competitor_price' => 'decimal:2',
    ];

    public function comparator(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator', 'comparator_id');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo('App\Models\Lang', 'lang_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\ReportType', 'type_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\ReportTemplate', 'template_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\ReportTemplateSchedule', 'schedule_id');
    }

    public function analytics(): HasOne
    {
        return $this->hasOne('App\Models\Comparator\ReportAnalytic', 'report_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany('App\Models\Comparator\ReportProductSnapshot', 'report_id');
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany('App\Models\Comparator\ReportPriceHistory', 'report_id');
    }
}
