<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'old_price',
        'new_price',
    ];

    /**
     * Define la relación con el producto.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\ComparatorProduct', 'product_id');
    }

}
