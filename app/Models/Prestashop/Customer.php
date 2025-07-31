<?php

namespace App\Models\Prestashop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{

    protected $connection = 'prestashop';
    protected $table = "aalv_specific_price";

    protected $fillable = [
        'id_product',
        'id_specific_price_rule',
        'id_cart',
        'id_product_attribute',
        'id_shop',
        'id_shop_group',
        'id_currency',
        'id_country',
        'id_group',
        'id_customer',
        'price',
        'from_quantity',
        'reduction',
        'reduction_tax',
        'reduction_type',
        'from',
        'to',
    ];

    protected $casts = [
        'price' => 'float',
        'reduction' => 'float',
        'reduction_tax' => 'boolean',
        'from_quantity' => 'int',
        'id_product' => 'int',
        'id_cart' => 'int',
        'id_product_attribute' => 'int',
        'id_shop' => 'int',
        'id_shop_group' => 'int',
        'id_currency' => 'int',
        'id_country' => 'int',
        'id_group' => 'int',
        'id_customer' => 'int',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Product', 'id_product');
    }

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\ProductAttribute', 'id_product_attribute');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Customer', 'id_customer');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Cart', 'id_cart');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Shop', 'id_shop');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Currency', 'id_currency');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Country', 'id_country');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Group', 'id_group');
    }

}
