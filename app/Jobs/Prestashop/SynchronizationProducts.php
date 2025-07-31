<?php

namespace App\Jobs\Prestashop;

use Illuminate\Support\Facades\Storage;
use App\Models\Lang;
use App\Models\Manufacturer;
use App\Models\Prestashop\Lang as PrestashopLang;
use App\Models\Prestashop\Manufacturer as PrestashopManufacturer;
use App\Models\Prestashop\Product\Product as PrestashopProduct;
use App\Models\Prestashop\Stock as PrestashopStock;
use App\Models\Product;
use App\Models\Product as ComparatorProduct;
use App\Models\ProductLang;
use App\Models\ProductPriceHistory;
use App\Models\ProductReference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ProductReferenceLang;
use App\Models\Prestashop\Combination\Import as PsCombImport;
use App\Models\Prestashop\Combination\Unique as PsCombUnique;
use Throwable;

class SynchronizationProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = 120; // Retry after 2 minutes

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        // This job can be dispatched without arguments to sync all products.
    }


    public function handle()
    {

            Log::info('SyncPrestashopProducts: Job execution started.');

        return
           PrestashopProduct::where('active', 1)
            ->whereHas('import')
            ->orderBy('id_product')
            ->chunkById(200, function ($prestashopProducts) {

                Log::info('Procesando lote de productos: ' . count($prestashopProducts));

                try {

                    $prestashopLangIds = [];
                    foreach ($prestashopProducts as $product) {
                        foreach ($product->langs as $lang) {
                            $prestashopLangIds[] = $lang->id_lang;
                        }
                    }

                    $prestashopLangIds = array_unique($prestashopLangIds);
                    $prestashopLangs = PrestashopLang::active()->byLangIds($prestashopLangIds)->get()->keyBy('id_lang');
                    $localLangs = Lang::byIsoCodes($prestashopLangs->pluck('iso_code'))->get()->keyBy('iso_code');

                    // -------- Prefetch etiquetas ----------
                    // $allProductIds        = $prestashopProducts->pluck('id_product')->unique()->values();
                    // $allCombinationIds    = $prestashopProducts->pluck('combinations.*.id_product_attribute')->flatten()->filter()->unique()->values()->toArray();

                    // $uniqueMap  = PsCombUnique::available()->byProductIds($allProductIds->all())->get()->keyBy('id_product');
                    //$importMap  = PsCombImport::available()->byProductIds($allCombinationIds)->get()->keyBy('id_product_attribute');
                    // $importMap  = PsCombImport::available()->byProductIds($allCombinationIds)->get()->select('id_product_attribute');
                    // dd($importMapp->take(10));
                    // dump($importMap);


                    foreach ($prestashopProducts as $psProduct) {
                        // if($psProduct->id_product == 456){
                            // dd($psProduct);
                        Log::info('Procesando el productos: ' . $psProduct->id_product);

                        $langs = $psProduct->langs;


                        if ($psProduct->id_manufacturer != 0) {
                            $psManufacturer = PrestashopManufacturer::id($psProduct->id_manufacturer);
                            $comparatorManufacturer = Manufacturer::firstOrCreate(
                                ['title' => $psManufacturer->name],
                                ['available' => 1]
                            );
                            $manufacturer = $comparatorManufacturer->id;
                        } else {
                            $manufacturer = null;
                        }

                        $parentid = $psProduct->defaultCategory
                            ? optional($psProduct->base_parent_category)->id_category
                            : null;

                        $categoryId = $psProduct->defaultCategory ? $psProduct->defaultCategory->id : null;


                        $comparatorProduct = Product::updateOrCreate(
                            ['prestashop_id' => $psProduct->id_product], // solo la clave única/lookup
                            [
                                'category_id'     => $categoryId,
                                'parentID'        => $parentid,
                                'manufacturer_id' => $manufacturer,
                                'available'       => 1,
                                'type'            => $psProduct->type()
                            ]
                        );

                        $type = $comparatorProduct->type;
                        // dd($type);

                        switch ($type) {
                            case 'combination':
                                $combinations = $psProduct->combinations;
                                // Log::info('count: ' . count($combinations));
                                // dd($combinations->first()->import);
                                // dump($combinations);
                                // foreach ($combinations as $combination) {
                                $combinations->chunk(100)->each(function ($itemcombinacion) use ( $comparatorProduct, $langs, $prestashopLangs, $localLangs, $psProduct) {
                                    //  dump($comparatorProduct,$langs,$prestashopLangs, $localLangs, $psProduct);
                                    foreach ($itemcombinacion as $combination) {
                                        Log::info('id_product_attribute: ' . $combination->id_product_attribute);
                                        $importMap = $combination->import;
                                        // dd($combination->import);
                                        // $src = $importMap->get($combination->id_product_attribute);
                                        // $etiqueta = optional($importMap->get($combination->id_product_attribute))->etiqueta;

                                        $pr = ProductReference::updateOrCreate(
                                            [
                                                'reference'  => $combination->reference,
                                                'product_id' => $comparatorProduct->id,
                                            ],
                                            [
                                                'combination_id'         => $combination->id_product_attribute  ?? null,
                                                'attribute_id'           => $combination->id_product_attribute  ?? null,
                                                'tags'                   => $importMap->etiqueta  ?? null,
                                                'id_articulo'            => $importMap->id_articulo ?? null,
                                                'unidades_oferta'        => $importMap->unidades_oferta ?? null,
                                                'estado_gestion'         => $importMap->estado_gestion ?? null,
                                                'es_segunda_mano'        => $importMap->es_segunda_mano ?? 0,
                                                'externo_disponibilidad' => $importMap->externo_disponibilidad ?? 0,
                                                'codigo_proveedor'       => $importMap->codigo_proveedor ?? null,
                                                'precio_costo_proveedor' => $importMap->precio_costo_proveedor ?? null,
                                                'tarifa_proveedor'       => $importMap->tarifa_proveedor ?? null,
                                                'es_arma'                => $importMap->es_arma ?? 0,
                                                'es_arma_fogueo'         => $importMap->es_arma_fogueo ?? 0,
                                                'es_cartucho'            => $importMap->es_cartucho ?? 0,
                                                'ean'                    => $importMap->ean ?? 0,
                                                'upc'                    => $importMap->upc ?? 0,
                                            ]
                                        );
                                        // dd($combination->validationStock());

                                        // $quantity = PrestashopStock::byProduct($comparatorProduct->prestashop_id,$combination->id_product_attribute);
                                        Log::info('Antes Lang: '.$langs);
                                        foreach ($langs as $lang) {
                                            Log::info('Despues de Lang: ' . $lang);
                                            $psLang = $prestashopLangs->get($lang->id_lang);

                                            $localLang = $localLangs->get($psLang->iso_code);

                                            $langProduct = ProductLang::updateOrCreate(
                                                [
                                                    'product_id' => $comparatorProduct->id,
                                                    'lang_id'    => $localLang->id,
                                                ],
                                                [
                                                    'title' => $lang->name,
                                                    'url'   => $lang->url,
                                                    'img'   => $psProduct->getImageUrl($localLang->id),
                                                    'stock' => $combination->validationStock(),
                                                ]
                                            );

                                            $finalPriceWithIVA = 0.0;
                                            $prices = $combination->prices;
                                            Log::info('count(prices): ' . count($prices));
                                            // dump(count($prices));
                                            $specificPrice = $prices->firstWhere('from_quantity', 1);

                                            if ($specificPrice) {
                                                $finalPriceWithIVA = round(
                                                    ((float) $specificPrice->price - (float) $specificPrice->reduction)
                                                        * (1 + (float) $localLang->iva / 100),
                                                    2
                                                );
                                            }

                                            ProductReferenceLang::updateOrCreate(
                                                [
                                                    'reference_id' => $pr->id,
                                                    'lang_id'    => $localLang->id,
                                                ],
                                                [
                                                    'url'   => $lang->url,
                                                    'characteristics' => $combination->atributosString($localLang->id),
                                                    'price' => $finalPriceWithIVA,
                                                    'reduction' => isset($specificPrice) ? $specificPrice->reduction : 0,
                                                    'available' => self::isBlocked($comparatorProduct->id, $localLang->id),
                                                    'img'   => $psProduct->getImageUrl($localLang->id),
                                                ]
                                            );

                                        }
                                    }
                                });

                                break;

                            case 'simple':

                                $src = $psProduct->unique;
                                // $src = $uniqueMap->get($psProduct->id_product);
                                // $etiqueta = optional($uniqueMap->get($psProduct->id_product))->etiqueta;

                                $pr = ProductReference::updateOrCreate(
                                    [
                                        'reference'  => $psProduct->reference,
                                        'product_id' => $comparatorProduct->id,
                                    ],
                                    [
                                        'combination_id' => NULL,
                                        'attribute_id'   => NULL,
                                        'tags'                   => $src->etiqueta,
                                        'id_articulo'            => $src->id_articulo ?? null,
                                        'unidades_oferta'        => $src->unidades_oferta ?? null,
                                        'estado_gestion'         => $src->estado_gestion ?? null,
                                        'es_segunda_mano'        => $src->es_segunda_mano ?? 0,
                                        'externo_disponibilidad' => $src->externo_disponibilidad ?? 0,
                                        'codigo_proveedor'       => $src->codigo_proveedor ?? null,
                                        'precio_costo_proveedor' => $src->precio_costo_proveedor ?? null,
                                        'tarifa_proveedor'       => $src->tarifa_proveedor ?? null,
                                        'es_arma'                => $src->es_arma ?? 0,
                                        'es_arma_fogueo'         => $src->es_arma_fogueo ?? 0,
                                        'es_cartucho'            => $src->es_cartucho ?? 0,
                                        'ean'                    => $src->ean ?? 0,
                                        'upc'                    => $src->upc ?? 0,
                                    ]
                                );

                                // $quantity = PrestashopStock::byProduct($comparatorProduct->prestashop_id,0);
                                // dd($psProduct->validationStock());
                                foreach ($langs as $lang) {

                                    $psLang = $prestashopLangs->get($lang->id_lang);

                                    $localLang = $localLangs->get($psLang->iso_code);

                                    $langProduct = ProductLang::updateOrCreate(
                                        [
                                            'product_id' => $comparatorProduct->id,
                                            'lang_id'    => $localLang->id,
                                        ],
                                        [
                                            'title' => $lang->name,
                                            'url'   => $lang->url,
                                            'img'   => $psProduct->getImageUrl($localLang->id),
                                            'stock' => $psProduct->validationStock(),
                                        ]
                                    );

                                    $finalPriceWithIVA = 0.0;
                                    $specificPrice = $psProduct->prices->firstWhere('from_quantity', 1);

                                    if ($specificPrice) {
                                        $finalPriceWithIVA = round(
                                            ((float) $specificPrice->price - (float) $specificPrice->reduction)
                                                * (1 + (float) $localLang->iva / 100),
                                            2
                                        );
                                    }

                                    ProductReferenceLang::updateOrCreate(
                                        [
                                            'reference_id' => $pr->id,
                                            'lang_id'    => $localLang->id,
                                        ],
                                        [
                                            'url'   => $lang->url,
                                            'characteristics' => NULL,
                                            'price' => $finalPriceWithIVA,
                                            'reduction' => isset($specificPrice) ? $specificPrice->reduction : 0,
                                            'available' => self::isBlocked($comparatorProduct->id, $localLang->id),
                                            'img'   => $psProduct->getImageUrl($localLang->id),
                                        ]
                                    );
                                }
                                break;

                            default:
                                Log::warning("Tipo de producto desconocido para ID {$psProduct->id_product}");
                                break;
                        }

                    // }
                    }
                } catch (Throwable $e) {
                    Log::error('Error during product sync chunk: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            });

            Log::info('SyncPrestashopProducts: Job finished successfully.');

    }

    public static function isBlocked($id_product, $id_lang)
    {
        // Mapeo de idioma a país
        $langToCountry = [
            1 => 6,   // Español => España
            2 => 17,  // Inglés => UK
            3 => 8,   // Francés => Francia
            4 => 15,  // Alemán => Alemania
            5 => 1,   // Italiano => Italia
            6 => 10,  // Portugués => Portugal
        ];

        $id_country = $langToCountry[$id_lang] ?? 6; // Default España

        // Chequeos de bloqueo
        if (
            self::bloqueoMarcasCategorias($id_product, $id_country, 1) || // Marca
            self::bloqueoMarcasCategorias($id_product, $id_country, 2) || // Categoría
            self::bloqueoFeature($id_product, $id_country) ||            // Características
            self::bloqueoEtiqueta($id_product, $id_country)              // Etiquetas
        ) {
            return true;
        }

        return false;
    }


    public static function bloqueoMarcasCategorias($id_product, $id_country, $tipo)
    {
        $conexion = DB::connection('prestashop'); // usa la conexión definida en .env

        if ($tipo == 1) {
            $id_manufacturer = $conexion->table('aalv_product')
                ->where('id_product', $id_product)
                ->value('id_manufacturer');

            $bloqueos = $conexion->table('aalv_bloqueos')
                ->where('id_tipo', 1)
                ->where('valor', $id_manufacturer)
                ->get();
        } else {
            $categories = $conexion->table('aalv_category_product')
                ->where('id_product', $id_product)
                ->pluck('id_category')
                ->toArray();

            if (!empty($categories)) {
                $bloqueos = $conexion->table('aalv_bloqueos')
                    ->where('id_tipo', 2)
                    ->whereIn('valor', $categories)
                    ->get();
            } else {
                $bloqueos = collect();
            }
        }

        foreach ($bloqueos as $bloqueo) {
            if ($bloqueo->id_country != 0) {
                if ($bloqueo->id_country == $id_country) {
                    return true;
                }
            } else {
                $excepciones = array_map('trim', explode(',', $bloqueo->excepcion));
                if (in_array($id_country, $excepciones)) {
                    return false;
                } else {
                    return true;
                }
            }
        }

        return false; // por defecto
    }

    public static function bloqueoFeature($id_product, $id_country)
    {
        $conexion = DB::connection('prestashop');

        // Paso 1: Obtener los valores de features del producto
        $features = $conexion->table('aalv_feature_product')
            ->where('id_product', $id_product)
            ->pluck('id_feature_value')
            ->toArray();

        if (empty($features)) {
            return false;
        }

        // Paso 2: Recorrer cada feature y buscar bloqueos relacionados
        foreach ($features as $featureValue) {
            $bloqueos = $conexion->table('aalv_bloqueos_tipo as abt')
                ->leftJoin('aalv_bloqueos as ab', 'ab.id_tipo', '=', 'abt.id')
                ->where('abt.codigo', '!=', 0)
                ->where('abt.codigo', $featureValue)
                ->select('ab.id_country', 'ab.valor', 'ab.excepcion')
                ->get();

            foreach ($bloqueos as $bloqueo) {
                if ($bloqueo->valor == 1) {
                    if ($bloqueo->id_country != 0) {
                        if ($bloqueo->id_country == $id_country) {
                            return true;
                        }
                    } else {
                        $excepciones = array_map('trim', explode(',', $bloqueo->excepcion));
                        if (in_array($id_country, $excepciones)) {
                            return false;
                        } else {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public static function bloqueoEtiqueta($id_product, $id_country)
    {
        $conexion = DB::connection('prestashop');

        // 1. Buscar etiquetas que no son números (valor NOT REGEXP '[0-9]')
        $etiquetas = $conexion->table('aalv_bloqueos')
            ->whereRaw("valor NOT REGEXP '[0-9]'")
            ->select('id_country', 'valor')
            ->get();

        foreach ($etiquetas as $etiqueta) {
            $valor = $etiqueta->valor;

            // 2. Buscar coincidencias en combinaciones
            $productosCombinados = $conexion->table('aalv_combinaciones_import as aci')
                ->leftJoin('aalv_product_attribute as apa', 'apa.id_product_attribute', '=', 'aci.id_product_attribute')
                ->where('apa.id_product', $id_product)
                ->where('aci.etiqueta', 'like', '%' . $valor . '%')
                ->select('apa.id_product');

            // 3. Buscar coincidencias en combinaciones únicas
            $productosUnicos = $conexion->table('aalv_combinacionunica_import')
                ->where('id_product', $id_product)
                ->where('etiqueta', 'like', '%' . $valor . '%')
                ->select('id_product');

            // 4. Unir ambas consultas con UNION
            $productos = $productosCombinados->union($productosUnicos)->get();

            if ($productos->count() > 0) {
                if ($etiqueta->id_country == $id_country) {
                    return true;
                }
            }
        }

        return false;
    }

}
