<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComparatorProduct extends Model
{
    use HasFactory;

    protected $table = 'comparator_products';

    protected $fillable = [
        'name',
        'sku',
        'url'
    ];

    public function priceHistory(): HasMany
    {
        return $this->hasMany(ComparatorReportPriceHistory::class, 'product_id');
    }

    /**
     * Get the competitor associations for the product.
     */
    public function competitors(): HasMany
    {
        return $this->hasMany(ComparatorProductCompetitor::class, 'product_id');
    }

}
