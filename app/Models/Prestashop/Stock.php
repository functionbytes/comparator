<?php

namespace App\Models\Prestashop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_stock_available';
    protected $primaryKey = 'id_stock_available';
    public $timestamps = false;

    protected $fillable = [
        'id_product',
        'id_product_attribute',
        'id_shop',
        'id_shop_group',
        'quantity',
        'physical_quantity',
        'reserved_quantity',
        'depends_on_stock',
        'out_of_stock',
    ];

    public function product()
    {
        return $this->belongsTo('App\Models\Prestashop\Product\Product', 'id_product');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Product\ProductAttribute', 'id_product_attribute');
    }

    // public function scopeByProduct($query, $product, $product_attribute)
    // {
    //     return $query->where('id_product', $product)->where('id_product_attribute', $product_attribute)->first()->quantity;
    // }
}
