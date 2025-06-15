<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComparatorReportAnalytics extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comparator_report_analytics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * Get the report that owns the analytics.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(ComparatorReport::class, 'report_id');
    }

    /**
     * Get the category that owns the analytics.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
