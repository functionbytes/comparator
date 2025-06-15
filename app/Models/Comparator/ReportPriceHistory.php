<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportPriceHistory extends Model
{
    protected $table = 'comparator_report_price_history';

    public const UPDATED_AT = null;

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

    protected $casts = [
        'shipping' => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'price_no_shipping' => 'decimal:2',
        'is_marketplace' => 'boolean',
        'captured_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Product', 'product_id');
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo('App\Models\Competitor', 'competitor_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo('App\Models\Seller', 'seller_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\Report', 'report_id');
    }
}
