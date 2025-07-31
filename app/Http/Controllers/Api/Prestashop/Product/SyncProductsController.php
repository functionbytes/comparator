<?php

namespace App\Http\Controllers\Api\Prestashop\Product;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Jobs\Prestashop\SynchronizationProducts;
use App\Jobs\SyncPrestashopProductsMaster;
use App\Models\Lang;
use App\Models\Manufacturer;
use App\Models\Prestashop\Lang as PrestashopLang;
use App\Models\Prestashop\Manufacturer as PrestashopManufacturer;
use App\Models\Prestashop\Product\Product as PrestashopProduct;
use App\Models\Prestashop\Combination\All as PrestashopCombination;


use App\Models\Prestashop\Stock as PrestashopStock;
// use App\Models\ProductReferenceManagement;
use App\Models\Prestashop\Combination\Import as PsCombImport;
use App\Models\Prestashop\Combination\Unique as PsCombUnique;
use App\Models\Product;
use App\Models\ProductLang;
use App\Models\ProductReference;
use App\Models\ProductReferenceLang;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;


class SyncProductsController extends Controller
{
    public function testSync(): JsonResponse
    {
        try {
            $job = new SynchronizationProducts();
            $job->handle(); // Ejecutamos directamente la lógica del job

            return response()->json(['message' => 'Sincronización ejecutada correctamente.']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error durante la sincronización.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function sync()
    {

        $id_lang = PrestashopLang::active()->get()->keyBy('id_lang');
        dd($id_lang->get(1));

        return
            PrestashopCombination::management()->orderBy('id_product_attribute')
            ->chunkById(1, function ($prestashopProducts) {

                dd($prestashopProducts);
                Log::info('Procesando lote de productos: ' . count($prestashopProducts));

                try {


                } catch (Throwable $e) {
                    Log::error('Error during product sync chunk: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                }






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

                                $combinations = $psProduct->combinationStock;



                                foreach ($combinations as $item) {

                                    $combination = $item->attribute;

                                    // Log::info('id_product_attribute: ' . $combination->id_product_attribute);
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
                                    // Log::info('Antes Lang: ' . $langs);
                                    foreach ($langs as $lang) {
                                        // Log::info('Despues de Lang: ' . $lang);
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
                                        // Log::info('count(prices): ' . count($prices));
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



                                // $combinations = $psProduct->combinations;

                                // // dd($combinations->first()->import);
                                // // dump($combinations);
                                // // foreach ($combinations as $combination) {
                                // $combinations->chunk(100)->each(function ($itemcombinacion) use ($comparatorProduct, $langs, $prestashopLangs, $localLangs, $psProduct) {
                                //     //  dump($comparatorProduct,$langs,$prestashopLangs, $localLangs, $psProduct);
                                //     foreach ($itemcombinacion as $combination) {
                                //         // Log::info('id_product_attribute: ' . $combination->id_product_attribute);
                                //         $importMap = $combination->import;
                                //         // dd($combination->import);
                                //         // $src = $importMap->get($combination->id_product_attribute);
                                //         // $etiqueta = optional($importMap->get($combination->id_product_attribute))->etiqueta;

                                //         $pr = ProductReference::updateOrCreate(
                                //             [
                                //                 'reference'  => $combination->reference,
                                //                 'product_id' => $comparatorProduct->id,
                                //             ],
                                //             [
                                //                 'combination_id'         => $combination->id_product_attribute  ?? null,
                                //                 'attribute_id'           => $combination->id_product_attribute  ?? null,
                                //                 'tags'                   => $importMap->etiqueta  ?? null,
                                //                 'id_articulo'            => $importMap->id_articulo ?? null,
                                //                 'unidades_oferta'        => $importMap->unidades_oferta ?? null,
                                //                 'estado_gestion'         => $importMap->estado_gestion ?? null,
                                //                 'es_segunda_mano'        => $importMap->es_segunda_mano ?? 0,
                                //                 'externo_disponibilidad' => $importMap->externo_disponibilidad ?? 0,
                                //                 'codigo_proveedor'       => $importMap->codigo_proveedor ?? null,
                                //                 'precio_costo_proveedor' => $importMap->precio_costo_proveedor ?? null,
                                //                 'tarifa_proveedor'       => $importMap->tarifa_proveedor ?? null,
                                //                 'es_arma'                => $importMap->es_arma ?? 0,
                                //                 'es_arma_fogueo'         => $importMap->es_arma_fogueo ?? 0,
                                //                 'es_cartucho'            => $importMap->es_cartucho ?? 0,
                                //                 'ean'                    => $importMap->ean ?? 0,
                                //                 'upc'                    => $importMap->upc ?? 0,
                                //             ]
                                //         );
                                //         // dd($combination->validationStock());

                                //         // $quantity = PrestashopStock::byProduct($comparatorProduct->prestashop_id,$combination->id_product_attribute);
                                //         Log::info('Antes Lang: ' . $langs);
                                //         foreach ($langs as $lang) {
                                //             Log::info('Despues de Lang: ' . $lang);
                                //             $psLang = $prestashopLangs->get($lang->id_lang);

                                //             $localLang = $localLangs->get($psLang->iso_code);

                                //             $langProduct = ProductLang::updateOrCreate(
                                //                 [
                                //                     'product_id' => $comparatorProduct->id,
                                //                     'lang_id'    => $localLang->id,
                                //                 ],
                                //                 [
                                //                     'title' => $lang->name,
                                //                     'url'   => $lang->url,
                                //                     'img'   => $psProduct->getImageUrl($localLang->id),
                                //                     'stock' => $combination->validationStock(),
                                //                 ]
                                //             );

                                //             $finalPriceWithIVA = 0.0;
                                //             $prices = $combination->prices;
                                //             Log::info('count(prices): ' . count($prices));
                                //             // dump(count($prices));
                                //             $specificPrice = $prices->firstWhere('from_quantity', 1);

                                //             if ($specificPrice) {
                                //                 $finalPriceWithIVA = round(
                                //                     ((float) $specificPrice->price - (float) $specificPrice->reduction)
                                //                         * (1 + (float) $localLang->iva / 100),
                                //                     2
                                //                 );
                                //             }

                                //             ProductReferenceLang::updateOrCreate(
                                //                 [
                                //                     'reference_id' => $pr->id,
                                //                     'lang_id'    => $localLang->id,
                                //                 ],
                                //                 [
                                //                     'url'   => $lang->url,
                                //                     'characteristics' => $combination->atributosString($localLang->id),
                                //                     'price' => $finalPriceWithIVA,
                                //                     'reduction' => isset($specificPrice) ? $specificPrice->reduction : 0,
                                //                     'available' => self::isBlocked($comparatorProduct->id, $localLang->id),
                                //                     'img'   => $psProduct->getImageUrl($localLang->id),
                                //                 ]
                                //             );
                                //         }
                                //     }
                                // });

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
    }


    public function syncs()
    {
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

                                $combinations = $psProduct->combinationStock;



                                foreach ($combinations as $item) {

                                    $combination = $item->attribute;

                                    // Log::info('id_product_attribute: ' . $combination->id_product_attribute);
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
                                    // Log::info('Antes Lang: ' . $langs);
                                    foreach ($langs as $lang) {
                                        // Log::info('Despues de Lang: ' . $lang);
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
                                        // Log::info('count(prices): ' . count($prices));
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



                                // $combinations = $psProduct->combinations;

                                // // dd($combinations->first()->import);
                                // // dump($combinations);
                                // // foreach ($combinations as $combination) {
                                // $combinations->chunk(100)->each(function ($itemcombinacion) use ($comparatorProduct, $langs, $prestashopLangs, $localLangs, $psProduct) {
                                //     //  dump($comparatorProduct,$langs,$prestashopLangs, $localLangs, $psProduct);
                                //     foreach ($itemcombinacion as $combination) {
                                //         // Log::info('id_product_attribute: ' . $combination->id_product_attribute);
                                //         $importMap = $combination->import;
                                //         // dd($combination->import);
                                //         // $src = $importMap->get($combination->id_product_attribute);
                                //         // $etiqueta = optional($importMap->get($combination->id_product_attribute))->etiqueta;

                                //         $pr = ProductReference::updateOrCreate(
                                //             [
                                //                 'reference'  => $combination->reference,
                                //                 'product_id' => $comparatorProduct->id,
                                //             ],
                                //             [
                                //                 'combination_id'         => $combination->id_product_attribute  ?? null,
                                //                 'attribute_id'           => $combination->id_product_attribute  ?? null,
                                //                 'tags'                   => $importMap->etiqueta  ?? null,
                                //                 'id_articulo'            => $importMap->id_articulo ?? null,
                                //                 'unidades_oferta'        => $importMap->unidades_oferta ?? null,
                                //                 'estado_gestion'         => $importMap->estado_gestion ?? null,
                                //                 'es_segunda_mano'        => $importMap->es_segunda_mano ?? 0,
                                //                 'externo_disponibilidad' => $importMap->externo_disponibilidad ?? 0,
                                //                 'codigo_proveedor'       => $importMap->codigo_proveedor ?? null,
                                //                 'precio_costo_proveedor' => $importMap->precio_costo_proveedor ?? null,
                                //                 'tarifa_proveedor'       => $importMap->tarifa_proveedor ?? null,
                                //                 'es_arma'                => $importMap->es_arma ?? 0,
                                //                 'es_arma_fogueo'         => $importMap->es_arma_fogueo ?? 0,
                                //                 'es_cartucho'            => $importMap->es_cartucho ?? 0,
                                //                 'ean'                    => $importMap->ean ?? 0,
                                //                 'upc'                    => $importMap->upc ?? 0,
                                //             ]
                                //         );
                                //         // dd($combination->validationStock());

                                //         // $quantity = PrestashopStock::byProduct($comparatorProduct->prestashop_id,$combination->id_product_attribute);
                                //         Log::info('Antes Lang: ' . $langs);
                                //         foreach ($langs as $lang) {
                                //             Log::info('Despues de Lang: ' . $lang);
                                //             $psLang = $prestashopLangs->get($lang->id_lang);

                                //             $localLang = $localLangs->get($psLang->iso_code);

                                //             $langProduct = ProductLang::updateOrCreate(
                                //                 [
                                //                     'product_id' => $comparatorProduct->id,
                                //                     'lang_id'    => $localLang->id,
                                //                 ],
                                //                 [
                                //                     'title' => $lang->name,
                                //                     'url'   => $lang->url,
                                //                     'img'   => $psProduct->getImageUrl($localLang->id),
                                //                     'stock' => $combination->validationStock(),
                                //                 ]
                                //             );

                                //             $finalPriceWithIVA = 0.0;
                                //             $prices = $combination->prices;
                                //             Log::info('count(prices): ' . count($prices));
                                //             // dump(count($prices));
                                //             $specificPrice = $prices->firstWhere('from_quantity', 1);

                                //             if ($specificPrice) {
                                //                 $finalPriceWithIVA = round(
                                //                     ((float) $specificPrice->price - (float) $specificPrice->reduction)
                                //                         * (1 + (float) $localLang->iva / 100),
                                //                     2
                                //                 );
                                //             }

                                //             ProductReferenceLang::updateOrCreate(
                                //                 [
                                //                     'reference_id' => $pr->id,
                                //                     'lang_id'    => $localLang->id,
                                //                 ],
                                //                 [
                                //                     'url'   => $lang->url,
                                //                     'characteristics' => $combination->atributosString($localLang->id),
                                //                     'price' => $finalPriceWithIVA,
                                //                     'reduction' => isset($specificPrice) ? $specificPrice->reduction : 0,
                                //                     'available' => self::isBlocked($comparatorProduct->id, $localLang->id),
                                //                     'img'   => $psProduct->getImageUrl($localLang->id),
                                //                 ]
                                //             );
                                //         }
                                //     }
                                // });

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
    }



    function xmlItemProducto($producto, $portes_referencia, $resultado_arrays_correctos, $aOptionsByType, $idLangPs)
    {

        //        // Array de caracteristicas
        //                $caracteristicas_xml = [
        //                    3 => 'flexibility',
        //                    11 => 'long',
        //                    12 => 'model',
        //                    20 => 'caliber',
        //                    27 => 'weight',
        //                    28 => 'diameter',
        //                    101 => 'set',
        //                    118 => 'increases',
        //                    100000461 => 'cane_model',
        //                    100000736 => 'coil',
        //                    100001193 => 'reticle',
        //                    100001535 => 'reel_size',
        //                    100001953 => 'shotgun_caliber'
        //                ];
        //
        //                $texto_opciones = '';
        //                if (isset($producto->opciones) && !empty($producto->opciones)) {
        //                    foreach ($producto->opciones as $key => $value) {
        //                        if(isset($caracteristicas_xml[$key])){
        //                            $ddatos = explode(':',$value);
        //                            $ddatos = array_map('trim', $ddatos);
        //                            $texto_opciones .= "<".$caracteristicas_xml[$key].">".htmlspecialchars($ddatos[1], ENT_XML1, 'UTF-8')."</".$caracteristicas_xml[$key].">\n";
        //                        }
        //                    }
        //                }
        //
        //                $precio = number_format(round($producto->tarifa->precio,2),2,',','');
        //                $categoria_principal = $modeloDAO->getCategoriaPrincipalByIdModelo($producto->id_modelo);
        //

        //

        //
        //                $array_ruta_categoria = array();

        //                /** Precio unitario **/
        //                $texto_unidades = '';
        //                $texto_precio_unitario = '';
        //                if ($producto->unidades_oferta > 1) {
        //                    $texto_unidades = '<unit>unidades</unit>';
        //                    $precio_unitario = number_format(round($producto->tarifa->precio/$producto->unidades_oferta,2),2,',','');
        //                    $texto_precio_unitario = '<price_unit>'.$precio_unitario.'</price_unit>';
        //                }
        //
        //        // Salida de los datos del XML
        //                $output = '
        //            <product>
        //

        //                '.$texto_unidades.'
        //                '.$texto_precio_unitario.'
        //                '.$texto_opciones.'
        //            </product>
        //        ';
        //`
        //      return $output;
    }

    public function xml(string $langIso = 'es')
    {
        // 1) Idioma
        $lang = Lang::iso($langIso);

        // 2) Raíz del XML
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><products/>');

        // 3) Consulta por lotes (idéntica a la anterior)
        Product::where('available', 1)
            ->whereHas('langs', function ($q) use ($lang) {
                $q->where('lang_id', $lang->id);
            })
            ->with([
                'langs' => fn($q) => $q->where('lang_id', $lang->id),
                'references.langs' => fn($q) => $q->where('lang_id', $lang->id),
                'manufacturer:id,title',
            ])
            ->chunk(100, function ($products) use (&$xml) {

                foreach ($products as $product) {


                    $productLang = $product->langs->first();
                    if (!$productLang) {
                        continue;
                    }

                    $validRefs = $product->references->filter(function ($reference) use ($product, $productLang) {
                        $price    = (float) $reference->langs[0]->price;
                        $minPrice = $product->category_id == 5 ? 20 : 40;

                        $sinStock = ($productLang->pivot->stock ?? 0) <= 0; // ajusta si el stock está en otro sitio

                        return $price > $minPrice
                            && $reference->langs[0]->available != 1
                            && $product->manufacturer_id != 419
                            && $reference->estado_gestion != 0
                            && $reference->es_cartucho != 1
                            && $reference->tags !== 'SEGUNDA MANO'
                            // descarta los que están en estado 2 y sin stock
                            && !($reference->estado_gestion == 2 && $sinStock);
                    });

                    if ($validRefs->isEmpty()) {
                        continue;
                    }

                    // 2) Agrupar por precio normalizado (mismo precio => mismo product en el XML)
                    $groups = $validRefs->groupBy(function ($r) {
                        return number_format((float) $r->price, 2, '.', '');
                    });

                    // 3) Un product por grupo de precio
                    foreach ($groups as $price => $refs) {

                        $firstRef = $refs->first(); // reference "representante"

                        // Concatenar EAN/UPC de todas las referencias del grupo (solo si hay más de una o por seguridad)
                        $eanList = $refs->pluck('ean')->filter()->unique()->implode(',');
                        $upcList = $refs->pluck('upc')->filter()->unique()->implode(',');

                        $p = $xml->addChild('product');
                        // SOLO una reference como id
                        $p->addChild('id',        htmlspecialchars($firstRef->reference));
                        $p->addChild('url',       htmlspecialchars($productLang->pivot->url));
                        $p->addChild('name',      htmlspecialchars($productLang->pivot->title));
                        $p->addChild('price',     $firstRef->langs[0]->price);
                        $p->addChild('image',     htmlspecialchars($productLang->pivot->img));
                        $p->addChild('shop',      '');
                        $p->addChild('brand',     htmlspecialchars($product->manufacturer?->title));

                        if ($eanList !== '') {
                            $p->addChild('ean', $eanList);
                        } elseif ($upcList !== '') {
                            $p->addChild('upc', $upcList);
                        }

                        $p->addChild('tag', $firstRef->tags);
                        $p->addChild('stock', $productLang->pivot->stock > 0 ? 'true' : 'false');

                        switch ($firstRef->estado_gestion) {
                            case '0':
                                $p->addChild('internal_status', 'Anulado');
                                break;
                            case '1':
                                $p->addChild('internal_status', 'Activo');
                                break;
                            case '2':
                                $p->addChild('internal_status', 'A extinguir');
                                break;
                        }

                        $p->addChild('codigo_proveedor', $firstRef->codigo_proveedor);
                        $p->addChild('category', '');
                    }
                    // dd($p);
                }
            });

        /* ----------------------------------------------------------
        | 4) Guardar archivo
        |    Ruta final: storage/app/xml/products_es.xml  (p. ej.)
        * ---------------------------------------------------------- */
        $dir  = 'xml';
        $timestamp = Carbon::now(config('app.timezone')) // o 'Europe/Madrid'
            ->format('Ymd_His');           // 20250723_154233
        $file      = "products_{$lang->iso_code}_{$timestamp}.xml";

        // Crea la carpeta si no existe
        if (!Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->makeDirectory($dir);
        }

        Storage::disk('local')->put("{$dir}/{$file}", $xml->asXML());

        /* ----------------------------------------------------------
        | 5) Devolver respuesta
        |    a) descarga directa   →  descomenta la línea download()
        |    b) confirmación JSON  →  return ['path' => ...]
        * ---------------------------------------------------------- */

        // a) Descargar el archivo inmediatamente
        // return response()->download(storage_path("app/{$dir}/{$file}"));

        // b) Confirmar ruta en JSON
        return response()->json([
            'stored' => true,
            'path'   => storage_path("app/{$dir}/{$file}"),
        ]);
    }

    public function excel(string $langIso = 'es')
    {
        $filename = "products_{$langIso}.csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store, no-cache',
        ];

        // return response()->streamDownload(function () use ($langIso) {

        //     $out = fopen('php://output', 'w');

        //     // BOM para que Excel detecte UTF-8
        //     fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        //     // Encabezados
        //     fputcsv($out, [
        //         'id',
        //         'url',
        //         'name',
        //         'price',
        //         'image',
        //         'shop',
        //         'brand',
        //         'ean',
        //         'upc',
        //         'tag',
        //         'stock',
        //         'internal_status',
        //         'codigo_proveedor',
        //         'category'
        //     ]);

        // $lang = \App\Models\Lang::iso($langIso);
        $lang = Lang::iso($langIso);

        $rowCount = 0;

        // \App\Models\Product::where('available', 1)
        Product::where('available', 1)
            ->whereHas('langs', fn($q) => $q->where('lang_id', $lang->id))
            // ->chunk(1000, function ($products) use (&$rowCount, $out) {
            ->chunk(1000, function ($products) {



                foreach ($products as $product) {

                    $productLang = $product->defaultLang;

                    // Filtrado de referencias válidas (como en xml)
                    $validRefs = $product->references->filter(function ($reference) use ($product, $productLang) {
                        dd($reference->langs);
                        $price = (float) $reference->langs[0]->price;
                        $minPrice = $product->category_id == 5 ? 20 : 40;
                        $sinStock = ($productLang->stock ?? 0) <= 0;

                        return $price > $minPrice
                            && $reference->langs[0]->available != 1
                            && $product->manufacturer_id != 419
                            && $reference->estado_gestion != 0
                            && $reference->es_cartucho != 1
                            && $reference->tags !== 'SEGUNDA MANO'
                            && !($reference->estado_gestion == 2 && $sinStock);
                    });

                    dump($product->id, $productLang, $validRefs);
                    if ($validRefs->isEmpty()) {
                        continue;
                    }

                    // Agrupar por precio normalizado
                    $groups = $validRefs->groupBy(function ($r) {
                        return number_format((float) $r->price, 2, '.', '');
                    });

                    // Procesar una fila por grupo de precio
                    foreach ($groups as $price => $refs) {

                        $firstRef = $refs->first();

                        $eanList = $refs->pluck('ean')->filter()->unique()->implode(',');
                        $upcList = $refs->pluck('upc')->filter()->unique()->implode(',');

                        $internalStatus = match ((string) $firstRef->estado_gestion) {
                            '0' => 'Anulado',
                            '1' => 'Activo',
                            '2' => 'A extinguir',
                            default => '',
                        };

                        // fputcsv($out, [
                        //     $firstRef->reference,
                        //     $productLang->url,
                        //     $productLang->title,
                        //     $firstRef->langs[0]->price,
                        //     $productLang->img,
                        //     '',
                        //     $product->manufacturer?->title,
                        //     $eanList ?: '',
                        //     $eanList ? '' : $upcList,
                        //     $firstRef->tags,
                        //     $productLang->stock > 0 ? 'true' : 'false',
                        //     $internalStatus,
                        //     $firstRef->codigo_proveedor,
                        //     '',
                        // ]);
                        // $rowCount++;
                    }
                }
            });

        //     fclose($out);
        // }, $filename, $headers);
    }


    public function jobs()
    {
        dispatch(new SynchronizationProducts);
        // dispatch(new SyncPrestashopProductsMaster);

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
