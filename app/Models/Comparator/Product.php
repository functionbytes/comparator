<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $table = 'comparator_products';

    protected $fillable = [
        'uid',
        'reference',
        'description',
        'category_id',
        'provider_id',
        'current_price',
        'current_price_no_tax',
        'cost_price',
        'new_cost_price',
        'margin_amount',
        'margin_percentage',
        'new_margin_amount',
        'new_margin_percentage',
        'status',
        'fixed_price',
        'has_tag',
        'visible_web_with_shipping',
        'is_external',
        'match_count',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'provider_id' => 'integer',
        'current_price' => 'decimal:2',
        'current_price_no_tax' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'new_cost_price' => 'decimal:2',
        'margin_amount' => 'decimal:2',
        'margin_percentage' => 'decimal:2',
        'new_margin_amount' => 'decimal:2',
        'new_margin_percentage' => 'decimal:2',
        'fixed_price' => 'boolean',
        'has_tag' => 'boolean',
        'visible_web_with_shipping' => 'boolean',
        'is_external' => 'boolean',
        'match_count' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo('App\Models\Category', 'category_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo('App\Models\Provider', 'provider_id');
    }


    public function langs(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Lang', 'product_lang', 'product_id', 'lang_id')
            ->withPivot('title', 'characteristics', 'url', 'price', 'stock', 'comparator', 'label')
            ->withTimestamps();
    }


}
