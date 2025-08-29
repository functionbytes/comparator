<?php

namespace App\Models\Prestashop\Specific;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SpecificPrice extends Model
{
    use HasFactory;

    protected $connection = 'prestashop';
    protected $table = 'aalv_specific_price';

    protected $primaryKey = 'id_specific_price';
    public $timestamps = false;

    protected $fillable = [
        'id_cart',
        'id_specific_price_rule',
        'id_product',
        'id_shop',
        'id_shop_group',
        'id_product_attribute',
        'id_currency',
        'id_country',
        'price',
        'from_quantity',
        'reduction',
        'reduction_type',
        'from',
        'to',
    ];

    protected $casts = [
        'price' => 'decimal:6',
        'reduction' => 'decimal:6',
        'from_quantity' => 'integer',
        'from' => 'datetime',
        'to' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Product\Product');
    }

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Product\ProductAttribute', 'id_product_attribute', 'id_product_attribute');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Group');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Country');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Currency');
    }

    /** Ordena por ventana más reciente: `to` DESC, luego `from` DESC */
    public function scopeOrderByWindow(Builder $q): Builder
    {
        // Usamos RAW para evitar conflictos con palabras reservadas (from/to)
        return $q->orderByRaw('`to` DESC, `from` DESC');
    }

    /** Ventana activa ya existente (lo dejo por claridad) */
    public function scopeActiveWindow(Builder $q): Builder
    {
        return $q->where(function ($query) {
            $query->where('from', '<=', now())
                ->orWhereNull('from')
                ->orWhere('from', '0000-00-00 00:00:00');
        })
            ->where(function ($query) {
                $query->where('to', '>=', now())
                    ->orWhereNull('to')
                    ->orWhere('to', '0000-00-00 00:00:00');
            });
    }

    /** JOIN por iso_code (lo que ya te pasé) */
    public function scopeForIso(Builder $q, string $iso): Builder
    {
        $sp = $q->getModel()->getTable(); // 'aalv_specific_price'

        if ($iso == 'es' || $iso == 'en') {
            return $q->leftJoin('aalv_country as ac', 'ac.id_country', '=', $sp . '.id_country')
                ->where(function ($w) use ($iso, $sp) {
                    $w->whereRaw('LOWER(ac.iso_code) = ?', [mb_strtolower($iso)])
                        ->orWhereNull('ac.id_country')        // mantiene filas sin match (LEFT JOIN)
                        ->orWhere($sp . '.id_country', 0);      // precios globales
                })
                ->select($sp . '.*');
        } else {
            return $q->leftJoin('aalv_country as ac', 'ac.id_country', '=', $sp . '.id_country')
                ->where(function ($w) use ($iso, $sp) {
                    $w->whereRaw('LOWER(ac.iso_code) = ?', [mb_strtolower($iso)]);
                })
                ->select($sp . '.*');
        }
    }
}
