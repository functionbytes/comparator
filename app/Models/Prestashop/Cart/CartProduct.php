<?php

namespace App\Models\Prestashop\Cart;

use Illuminate\Database\Eloquent\Model;

class CartProduct extends Model
{

    protected $connection = 'prestashop';
    protected $table = "aalv_cart_product";
    public $timestamps = false;

    protected $fillable = [
        "id_cart",
        "id_product",
        "id_address_delivery",
        "id_shop",
        "id_product_attribute",
        "id_customization",
        "quantity",
        "date_add",
    ];

    public function cart()
    {
        return $this->belongsTo('App\Models\Prestashop\Cart\ShopGroup', 'id_cart', 'id_cart');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Prestashop\Product\Product', 'id_product', 'id_product');
    }

    public function shop()
    {
        return $this->belongsTo('App\Models\Prestashop\Shop', 'id_shop', 'id_shop');
    }

}

