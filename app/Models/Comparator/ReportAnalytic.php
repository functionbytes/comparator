<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAnalytic extends Model
{
    protected $table = 'comparator_report_analytics';

    protected $fillable = [
        'report_id',
        'category_id',
        'total_products_analyzed',
        'new_products_found',
        'products_with_price_changes',
        'avg_price_variation',
        'products_below_threshold',
        'products_above_threshold',
        'processing_time_seconds',
    ];

    protected $casts = [
        'total_products_analyzed' => 'integer',
        'new_products_found' => 'integer',
        'products_with_price_changes' => 'integer',
        'products_below_threshold' => 'integer',
        'products_above_threshold' => 'integer',
        'processing_time_seconds' => 'integer',
        'avg_price_variation' => 'decimal:2',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\Report', 'report_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo('App\Models\Category', 'category_id');
    }
}
