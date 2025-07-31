<?php


namespace App\Models\Prestashop;

use App\Models\Prestashop\Product\Product;
use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_manufacturer'; // Usamos tu prefijo
    protected $primaryKey = 'id_manufacturer';

    public $timestamps = false;

    public function scopeId($query ,$id)
    {
        return $query->where('id_manufacturer', $id)->first();
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'id_manufacturer', 'id_manufacturer');
    }

}
