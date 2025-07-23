<?php

namespace App\Jobs\Prestashop;

use Illuminate\Support\Facades\Storage;
use App\Models\Lang;
use App\Models\Manufacturer;
use App\Models\Prestashop\Lang as PrestashopLang;
use App\Models\Prestashop\Manufacturer as PrestashopManufacturer;
use App\Models\Prestashop\Product\Product as PrestashopProduct;
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
use App\Models\ProductReferenceManagement;
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

        PrestashopProduct::with(['langs', 'combinations'])
                ->orderBy('id_product')
                ->where('active', 1)
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
                        $allProductIds        = $prestashopProducts->pluck('id_product')->unique()->values();
                        $allCombinationIds    = $prestashopProducts->pluck('combinations.*.id_product_attribute')->flatten()->filter()->unique()->values()->toArray();

                        $uniqueMap  = PsCombUnique::available()->byProductIds($allProductIds->all())->get()->keyBy('id_product');
                        $importMap  = PsCombImport::available()->byProductIds($allCombinationIds)->get()->keyBy('id_product_attribute');


                        foreach ($prestashopProducts as $psProduct) {

                            $combinations = $psProduct->combinations;
                            $langs = $psProduct->langs;

                            if($psProduct->id_manufacturer != 0){
                                $psManufacturer = PrestashopManufacturer::id($psProduct->id_manufacturer);
                                $comparatorManufacturer = Manufacturer::firstOrCreate(
                                    ['title' => $psManufacturer->name],
                                    ['available' => 1]
                                );
                                $manufacturer = $comparatorManufacturer->id;
                            }else{
                                $manufacturer = null;
                            }

                            $categoryId = $psProduct->defaultCategory
                                            ? optional($psProduct->base_parent_category)->id_category
                                            : null;

                            $comparatorProduct = Product::updateOrCreate(
                                ['prestashop_id' => $psProduct->id_product], // solo la clave única/lookup
                                [
                                    'category_id'     => $categoryId,
                                    'manufacturer_id' => $manufacturer,
                                    'available'       => 1,
                                    'type'            => $combinations->isNotEmpty() ? 'combination' : 'simple'
                                ]
                            );

                            $type = $comparatorProduct->type;

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
                                    ]
                                );


                                switch ($type) {
                                    case 'combination':
                                        foreach ($combinations as $combination) {
                                            // dd($combination);
                                            $atributosString = $combination->atributosString($localLang->id);

                                            $finalPriceWithIVA = 0.0;
                                            $prices = $combination->prices;
                                            $specificPrice = $prices->firstWhere('from_quantity', 1);

                                            if ($specificPrice) {
                                                $finalPriceWithIVA = round(
                                                    ((float) $specificPrice->price - (float) $specificPrice->reduction)
                                                    * (1 + (float) $localLang->iva / 100),
                                                    2
                                                );
                                            }

                                            $pr = ProductReference::updateOrCreate(
                                                [
                                                    'reference'   => $combination->reference,
                                                    'product_id'  => $comparatorProduct->id,
                                                    'lang_id'     => $localLang->id,
                                                ],
                                                [
                                                    'combination_id' => $combination->id_product_attribute,
                                                    'available'      => $combination->stock?->quantity > 0,
                                                    'attribute_id'   => $combination->id_product_attribute,
                                                    'characteristics'=> $atributosString,
                                                    'price'          => $finalPriceWithIVA,
                                                    'url'            => null,
                                                ]
                                            );

                                            // Tags solo para el lang configurado
                                            if ($localLang->id == 1) {
                                                $src = $importMap->get($combination->id_product_attribute);
                                                $etiqueta = optional($importMap->get($combination->id_product_attribute))->etiqueta;
                                                ProductReferenceManagement::updateOrCreate(
                                                    ['product_reference_id' => $pr->id],
                                                    [
                                                        'tags'                   => $etiqueta,
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
                                            }

                                            $langProduct->stock = $combination->stock?->quantity ?? 0;
                                            $langProduct->available = $combination->stock?->quantity > 0;
                                            $langProduct->save();

                                        }

                                        break;

                                    case 'simple':

                                        $finalPriceWithIVA = 0.0;
                                        $specificPrice = $psProduct->prices->firstWhere('from_quantity', 1);

                                        if ($specificPrice) {
                                            $finalPriceWithIVA = round(
                                                ((float) $specificPrice->price - (float) $specificPrice->reduction)
                                                * (1 + (float) $localLang->iva / 100),
                                                2
                                            );
                                        }

                                        $pr = ProductReference::updateOrCreate(
                                            [
                                                'reference'  => $psProduct->reference,
                                                'product_id' => $comparatorProduct->id,
                                                'lang_id'    => $localLang->id,
                                            ],
                                            [
                                                'combination_id' => null,
                                                'available'      => $psProduct->stock?->quantity > 0,
                                                'attribute_id'   => null,
                                                'characteristics'=> null,
                                                'price'          => $finalPriceWithIVA,
                                                'url'            => null,
                                            ]
                                        );


                                        if ($localLang->id == 1) {
                                            $src = $uniqueMap->get($psProduct->id_product);
                                            $etiqueta = optional($uniqueMap->get($psProduct->id_product))->etiqueta;
                                            ProductReferenceManagement::updateOrCreate(
                                                    ['product_reference_id' => $pr->id],
                                                    [
                                                        'tags'                   => $etiqueta,
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
                                        }

                                        $comparatorProduct->stock = $psProduct->stock?->quantity ?? 0;
                                        $langProduct->available = $psProduct->stock?->quantity > 0;
                                        $langProduct->save();

                                        break;

                                    default:
                                        Log::warning("Tipo de producto desconocido para ID {$psProduct->id_product}");
                                        break;
                                }
                            }
                        }


                    } catch (Throwable $e) {
                        Log::error('Error during product sync chunk: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                });

            Log::info('SyncPrestashopProducts: Job finished successfully.');

    }

}
