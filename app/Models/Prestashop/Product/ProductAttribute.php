<?php

namespace App\Models\Prestashop\Product;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_product_attribute';
    protected $primaryKey = 'id_product';

    public $timestamps = false;

    protected $fillable = [
        'id_product',
        'reference',
        'supplier_reference',
        'location',
        'ean13',
        'isbn',
        'upc',
        'mpn',
        'wholesale_price',
        'price',
        'ecotax',
        'quantity',
        'weight',
        'unit_price_impact',
        'default_on',
        'minimal_quantity',
        'low_stock_threshold',
        'low_stock_alert',
        'available_date',
    ];

    public function product()
    {
        return $this->belongsTo('App\Models\Prestashop\Product\Product', 'id_product');
    }

    public function attributes()
    {
        return $this->belongsToMany(
            'App\Models\Prestashop\Attribute\Attribute',
            'ps_product_attribute_combination',
            'id_product_attribute',
            'id_attribute'
        );
    }

    public function isDefault()
    {
        return $this->default_on == 1;
    }
}
