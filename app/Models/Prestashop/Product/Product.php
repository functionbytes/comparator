<?php

namespace App\Models\Prestashop\Product;

use App\Models\Prestashop\Category\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_product';
    protected $primaryKey = 'id_product';

    public $timestamps = false;

    protected $fillable = [
        'id_manufacturer',
        'id_supplier',
        'reference',
        'name',
        'price',
        'active',
        'date_add',
        'date_upd',
    ];

    public function newCollection(array $models = [])
    {
        return (new class($models) extends Collection {
            public function __construct($models)
            {
                parent::__construct($models);

                $this->load('lang');
            }
        });
    }

    public function newQuery($excludeDeleted = true)
    {
        return parent::newQuery($excludeDeleted)->with('lang');
    }
    public function scopeName($query, $name)
    {
        return $query->whereHas('lang', function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%');
        });
    }

    public function defaultCategory()
    {
        return $this->belongsTo('App\Models\Prestashop\Category\Category', 'id_category_default', 'id_category');
    }

    public function getBaseParentCategoryAttribute(): ?Category
    {
        $this->loadMissing('defaultCategory');

        $defaultCategory = $this->defaultCategory;

        if (!$defaultCategory || $defaultCategory->level_depth <= 2) {
            return $defaultCategory?->level_depth == 2 ? $defaultCategory : null;
        }

        return Category::with('lang')
            ->where('nleft', '<', $defaultCategory->nleft)
            ->where('nright', '>', $defaultCategory->nright)
            ->where('level_depth', 2)
            ->first();
    }

    public function stock()
    {
        return $this->hasOne(
            'App\Models\Prestashop\Stock',
            'id_product',
            'id_product'
        );
    }

    public function prices()
    {
        return $this->hasMany(
            'App\Models\Prestashop\Specific\SpecificPrice',
            'id_product',
            'id_product'
        )->where('id_country', 0)
            ->where(function ($query) {
            $query->where('from', '<=', now())
                ->orWhereNull('from')
                ->orWhere('from', '0000-00-00 00:00:00');
        })->where(function ($query) {
            $query->where('to', '>=', now())
                ->orWhereNull('to')
                ->orWhere('to', '0000-00-00 00:00:00');
        });
    }

    public function getNameAttribute()
    {
        return $this->lang ? $this->lang->name : null;
    }

    public function combinations()
    {
        return $this->hasMany('App\Models\Prestashop\Product\ProductAttribute', 'id_product', 'id_product');
    }

    public function shop()
    {
        return $this->belongsToMany('App\Models\Prestashop\Shop', 'ps_product_shop', 'id_product', 'id_shop');
    }


    public function images()
    {
        return $this->belongsToMany('App\Models\Prestashop\Image',  'id_product');
    }


    public function manufacturer()
    {
        return $this->belongsToMany('App\Models\Prestashop\Manufacturer',  'id_manufacturer');
    }

    public function lang()
    {
        return $this->hasOne('App\Models\Prestashop\Product\ProductLang', 'id_product', 'id_product')->where('id_lang', 1);
    }

    public function langs()
    {
        return $this->hasMany('App\Models\Prestashop\Product\ProductLang', 'id_product');
    }


}
