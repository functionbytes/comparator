<?php

namespace App\Models\Prestashop\Specific;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecificPrice extends Model
{
    use HasFactory;

    protected $connection = 'prestashop';
    protected $table = 'aalv_product';

    protected $primaryKey = 'id_specific_price';
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'product_attribute_id',
        'shop_id',
        'currency_id',
        'country_id',
        'group_id',
        'user_id',
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
        return $this->belongsTo('App\Models\Prestashop\Product\ProductAttribute');
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
