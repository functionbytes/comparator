<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCompetitor extends Model
{
    protected $table = 'comparator_product_competitors';

    protected $fillable = [
        'product_id',
        'competitor_id',
        'seller_id',
        'last_price',
        'last_shipping',
        'last_seen_at',
        'times_seen',
    ];

    protected $casts = [
        'last_price' => 'decimal:2',
        'last_shipping' => 'decimal:2',
        'last_seen_at' => 'datetime',
        'times_seen' => 'integer',
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

}
