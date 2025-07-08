<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductReference extends Model
{
    protected $table = 'product_reference';

    protected $fillable = [
        'reference',
        'lang_id',
        'combination_id',
        'attribute_id',
        'product_id',
        'available',
        'label',
        'url',
    ];

    protected $casts = [
        'reference'       => 'string',
        'combination_id'  => 'integer',
        'attribute_id'    => 'integer',
        'product_id'      => 'integer',
        'label'           => 'string',
        'available'       => 'boolean',
        'deleted_at'      => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function setUrlAttribute($value)
    {
        if (!$value) {

            $productLang = ProductLang::where([
                'product_id' => $this->product->id,
                'lang_id' => $this->lang->id,
            ])->first();

            if ($productLang) {

                $this->attributes['url'] = $productLang->url;

                if (!empty($this->attribute_id)) {
                    $this->attributes['url'] .= '?id_product_attribute=' . $this->attribute_id;
                }
                return;
            }
        }

        $this->attributes['url'] = $value;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Product', 'product_id');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo('App\Models\Lang', 'lang_id');
    }

    public function productLang()
    {
        return $this->hasOne('App\Models\ProductLang', 'product_id', 'product_id')
            ->whereColumn('lang_id', 'lang_id');
    }

}
