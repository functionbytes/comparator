<?php

namespace App\Models\Prestashop\Product;

use App\Models\Prestashop\Combination\Import as PrestashopCombinationImport;
use App\Models\Prestashop\Combination\Unique as PrestashopCombinationUnique;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class ProductAttribute extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_product_attribute';
    protected $primaryKey = 'id_product';

    public $timestamps = false;

    protected $fillable = [
        'id_product_attribute',
        'id_product',
        'reference',
        'supplier_reference',
        'location',
        'ean13',
        'isbn',
        'upc',
        'mpn',
        'wholesale_price',
        'price',
        'ecotax',
        'quantity',
        'weight',
        'unit_price_impact',
        'default_on',
        'minimal_quantity',
        'low_stock_threshold',
        'low_stock_alert',
        'available_date',
    ];

    public function stock()
    {
        return $this->hasOne(
            'App\Models\Prestashop\Stock',
            'id_product_attribute',
            'id_product_attribute'
        );
    }
    public function product()
    {
        return $this->belongsTo('App\Models\Prestashop\Product\Product', 'id_product');
    }

    public function lang()
    {
        return $this->hasOne('App\Models\Prestashop\Lang', 'id_lang', 'id_lang');
    }

    public function attributes()
    {
        return $this->belongsToMany(
            'App\Models\Prestashop\Attribute\Attribute',
            'ps_product_attribute_combination',
            'id_product_attribute',
            'id_attribute'
        );
    }

    public function isDefault()
    {
        return $this->default_on == 1;
    }

    public function prices()
    {
        return $this->hasMany(
            'App\Models\Prestashop\Specific\SpecificPrice',
            'id_product_attribute',
            'id_product_attribute'
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


    public function getUrlAttribute()
    {
        $base = rtrim(env('URL_PRESTASHOP', 'https://a-alvarez.com'), '/');
        $lang = $this->relationLoaded('lang') ? $this->lang : $this->lang()->first();

        $iso = optional($lang)->iso_code ?? 'es';
        $langSegment = ($iso !== 'es') ? "/$iso" : '';
        $url = "{$base}{$langSegment}/{$this->id_product}-{$this->product->link_rewrite}";

        // $url .= '?id_product_attribute=' . $this->id_product_attribute;

        return $url;
    }

    public function validationCombination(){

        $product =  $this->product;
        $combinations = $product->combinations;

        $type = count($combinations)>0 ? 'combination' : 'simple';

        switch ($type) {
            case 'combination':

                $data = $this->import;
                break;

            case 'simple':

                $data = $this->unique;
                break;

            default:
                break;
        }

        return $data->codigo_proveedor;
    }


    public function unique(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Combination\Unique' ,'id_product' ,'id_product_attribute');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Combination\Import' ,'id_product_attribute' ,'id_product_attribute');
    }

    /**
     * Devuelve el string de atributos para ESTE product_attribute en el idioma indicado.
     */
    public function atributosString(int $idLang): ?string
    {
        return $this->getConnection() // <<< usa la conexión del modelo (prestashop)
            ->table($this->getTable() . ' as pa')
            ->join('aalv_product_attribute_combination as pac', 'pac.id_product_attribute', '=', 'pa.id_product_attribute')
            ->join('aalv_attribute as a', 'a.id_attribute', '=', 'pac.id_attribute')
            ->join('aalv_attribute_lang as al', function ($j) use ($idLang) {
                $j->on('al.id_attribute', '=', 'a.id_attribute')
                ->where('al.id_lang', '=', $idLang);
            })
            ->join('aalv_attribute_group as ag', 'ag.id_attribute_group', '=', 'a.id_attribute_group')
            ->join('aalv_attribute_group_lang as agl', function ($j) use ($idLang) {
                $j->on('agl.id_attribute_group', '=', 'ag.id_attribute_group')
                ->where('agl.id_lang', '=', $idLang);
            })
            ->where('pa.id_product', $this->id_product)
            ->where('pa.id_product_attribute', $this->id_product_attribute)
            ->selectRaw("GROUP_CONCAT(CONCAT(agl.name, ': ', al.name) ORDER BY al.name SEPARATOR ' | ') AS atributos_string")
            ->value('atributos_string');
    }

    /**
     * Scope para traer el campo calculado en el SELECT.
     * Uso: ProductAttribute::withAtributosString($langId)->get();
     */
    public function scopeWithAtributosString(Builder $query, int $idLang): Builder
    {
        $conn = DB::connection($this->getConnectionName());

        $sub = $conn->table('aalv_product_attribute_combination as pac')
            ->join('aalv_attribute as a', 'a.id_attribute', '=', 'pac.id_attribute')
            ->join('aalv_attribute_lang as al', function ($j) use ($idLang) {
                $j->on('al.id_attribute', '=', 'a.id_attribute')
                ->where('al.id_lang', '=', $idLang);
            })
            ->join('aalv_attribute_group as ag', 'ag.id_attribute_group', '=', 'a.id_attribute_group')
            ->join('aalv_attribute_group_lang as agl', function ($j) use ($idLang) {
                $j->on('agl.id_attribute_group', '=', 'ag.id_attribute_group')
                ->where('agl.id_lang', '=', $idLang);
            })
            ->whereColumn('pac.id_product_attribute', $this->getTable().'.id_product_attribute')
            ->selectRaw("GROUP_CONCAT(CONCAT(agl.name, ': ', al.name) ORDER BY al.name SEPARATOR ' | ')");

        return $query->addSelect(['atributos_string' => $sub]);
    }


}
