<?php

namespace App\Models\Prestashop\Cart;

use Illuminate\Database\Eloquent\Model;

class  Cart extends Model
{

    protected $connection = 'prestashop';

    protected $table = "aalv_cart";
    protected $primaryKey = 'id_cart';
    public $timestamps = false;

    protected $fillable = [
        "id_cart" ,
        "id_shop_group" ,
        "id_shop",
        "id_carrier" ,
        "delivery_option" ,
        "id_lang" ,
        "id_address_delivery" ,
        "id_address_invoice" ,
        "id_currency" ,
        "id_customer" ,
        "id_guest" ,
        "secure_key" ,
        "recyclable" ,
        "gift" ,
        "gift_message" ,
        "mobile_theme" ,
        "allow_seperated_package" ,
        "date_add" ,
        "date_upd" ,
        "checkout_session_data" ,
        "need_invoice" ,
    ];

    public function shopGroup()
    {
        return $this->belongsTo('App\Models\Prestashop\Shop\ShopGroup', 'id_shop_group', 'id_shop_group');
    }

    public function shop()
    {
        return $this->belongsTo('App\Models\Prestashop\Shop', 'id_shop', 'id_shop');
    }

    public function carrier()
    {
        return $this->belongsTo('App\Models\Prestashop\Carrier', 'id_carrier', 'id_carrier');
    }

    public function lang()
    {
        return $this->belongsTo('App\Models\Prestashop\Lang', 'id_lang', 'id_lang');
    }

    public function addressDelivery()
    {
        return $this->belongsTo('App\Models\Prestashop\Address', 'id_address_delivery', 'id_address');
    }

    public function addressInvoice()
    {
        return $this->belongsTo('App\Models\Prestashop\Address', 'id_address_invoice', 'id_address');
    }

    public function currency()
    {
        return $this->belongsTo('App\Models\Prestashop\Currency', 'id_currency', 'id_currency');
    }

    public function customer()
    {
        return $this->belongsTo('App\Models\Prestashop\Customer', 'id_customer', 'id_customer');
    }

    public function guest()
    {
        return $this->belongsTo('App\Models\Prestashop\Guest', 'id_guest', 'id_guest');
    }

    public function products()
    {
        return $this->hasMany('App\Models\Prestashop\Cart\CartProduct', 'id_cart', 'id_cart');
    }

}

