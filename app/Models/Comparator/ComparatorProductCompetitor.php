<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComparatorProductCompetitor extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comparator_product_competitors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'competitor_id',
        'seller_id',
        'last_price',
        'last_shipping',
        'last_seen_at',
        'times_seen',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    /**
     * Get the product associated with this entry.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ComparatorProduct::class, 'product_id');
    }

    /**
     * Get the competitor associated with this entry.
     */
    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class, 'competitor_id');
    }

    /**
     * Get the seller associated with this entry.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
}
