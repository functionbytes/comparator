<?php

namespace App\Models\Prestashop\Attribute;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $table = 'ps_attribute';
    protected $primaryKey = 'id_attribute';
    public $timestamps = false;

    protected $fillable = [
        'id_attribute_group',
        'color',
        'position'
    ];

    public function group()
    {
        return $this->belongsTo('App\Models\Prestashop\AttributeGroup', 'id_attribute_group');
    }

    public function productAttributes()
    {
        return $this->belongsToMany(
            'App\Models\Prestashop\Product\ProductAttribute',
            'ps_product_attribute_combination',
            'id_attribute',
            'id_product_attribute'
        );
    }

    public function translations()
    {
        return $this->hasMany('App\Models\Prestashop\Attribute\AttributeLang', 'id_attribute');
    }
}
