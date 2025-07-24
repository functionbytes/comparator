<?php

namespace App\Models\Prestashop\Product;

use Illuminate\Database\Eloquent\Model;

class ProductImport extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_product_import';

    public $timestamps = false;

    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'id_product',
        'id_modelo',
    ];

    public function product()
    {
        return $this->hasOne('App\Models\Prestashop\Product\Product', 'id_product', 'id_product');
    }

}
