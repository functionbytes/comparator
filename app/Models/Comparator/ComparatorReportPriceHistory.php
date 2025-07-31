<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComparatorReportPriceHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comparator_report_price_history';

    /**
     * The name of the "updated at" column.
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'competitor_id',
        'seller_id',
        'shipping',
        'total_price',
        'quantity',
        'price',
        'price_no_shipping',
        'is_marketplace',
        'marketplace_name',
        'shipping_type',
        'url',
        'report_id',
        'captured_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_marketplace' => 'boolean',
        'captured_at' => 'datetime',
    ];

    /**
     * Get the report that owns the price history record.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(ComparatorReport::class, 'report_id');
    }

    /**
     * Get the product associated with the price history.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ComparatorProduct::class, 'product_id');
    }

    /**
     * Get the competitor associated with the price history.
     */
    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class, 'competitor_id');
    }

    /**
     * Get the seller associated with the price history.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}
