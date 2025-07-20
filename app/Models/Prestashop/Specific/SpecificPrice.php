<?php

namespace App\Models\Prestashop\Specific;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecificPrice extends Model
{
    use HasFactory;

    protected $connection = 'prestashop';
    protected $table = 'aalv_specific_price';

    protected $primaryKey = 'id_specific_price';
    public $timestamps = false;

    protected $fillable = [
        'id_cart',
        'id_specific_price_rule',
        'id_product',
        'id_shop',
        'id_shop_group',
        'id_product_attribute',
        'id_currency',
        'id_country',
        'price',
        'from_quantity',
        'reduction',
        'reduction_type',
        'from',
        'to',
    ];

    protected $casts = [
        'price' => 'decimal:6',
        'reduction' => 'decimal:6',
        'from_quantity' => 'integer',
        'from' => 'datetime',
        'to' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Product\Product');
    }

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Product\ProductAttribute' ,'id_product_attribute' ,'id_product_attribute');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Group');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Country');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Currency');
    }
}
