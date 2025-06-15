<?php

namespace App\Models\Prestashop;

use Illuminate\Database\Eloquent\Model;
class Address extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_address';
    protected $primaryKey = 'id_address';
    public $timestamps = false;

    protected $fillable = [
        'id_address',
        'id_country',
        'id_state',
        'id_customer',
        'id_manufacturer',
        'id_supplier',
        'id_warehouse',
        'alias',
        'company',
        'lastname',
        'firstname',
        'address1',
        'address2',
        'postcode',
        'city',
        'other',
        'phone',
        'phone_mobile',
        'vat_number',
        'dni',
        'date_add',
        'date_upd',
        'active',
        'deleted',
    ];


    public function customer()
    {
        return $this->belongsTo('App\Models\Prestashop\Customer', 'id_customer', 'id_customer');
    }

    public function cartsAsDelivery()
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_address_delivery', 'id_address');
    }

    public function cartsAsInvoice()
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_address_invoice', 'id_address');
    }

}
