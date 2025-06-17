<?php

namespace App\Http\Controllers\Api\Prestashop\Product;

use App\Http\Controllers\Controller;
use App\Jobs\Prestashop\SynchronizationProducts;
use App\Models\Lang;
use App\Models\Prestashop\Product\Product as PrestashopProduct;
use App\Models\Product;
use App\Models\ProductLang;
use App\Models\ProductPriceHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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


        return PrestashopProduct::with(['langs', 'prices'])
            ->where('active', 1)
            ->chunkById(200, function ($prestashopProducts) {


                $prestashopLangIds = [];

                foreach ($prestashopProducts as $product) {

                    $combinations = $product->combinations;


                    foreach ($product->langs as $lang) {
                        $prestashopLangIds[] = $lang->id_lang;


                        $prestashopLangIds = array_unique($prestashopLangIds);

                        $prestashopLangs = DB::connection('prestashop')
                            ->table('aalv_lang')
                            ->whereIn('id_lang', $prestashopLangIds)
                            ->get()
                            ->keyBy('id_lang');

                        $localLangs = Lang::whereIn('iso_code', $prestashopLangs->pluck('iso_code'))
                            ->get()
                            ->keyBy('iso_code');

                        foreach ($prestashopProducts as $psProduct) {


                            if (empty($psProduct->reference)) {
                                Log::warning("Skipping Prestashop product ID {$psProduct->id_product} (no reference)");
                                continue;
                            }

                            $comparatorProduct = Product::firstOrNew([
                                'prestashop_id' => $psProduct->id_product,
                                'ean' => $psProduct->ean,
                                'upc' => $psProduct->upc,
                                'category_id' => $psProduct->base_parent_category->id_category,
                                'available' => 1,
                                'type' => count($combinations)>0 ? 'combination' : 'simple'
                            ]);

                            if (!$comparatorProduct->exists) {
                                $comparatorProduct->save();
                            }


                            if($comparatorProduct->type == 'combination'){
                                $lang_product = DB::connection('prestashop')
                                            ->table('aalv_product_lang')
                                            ->where('id_product', $psProduct->id_product)
                                            ->where('id_lang', $prestashopLangs->pluck('id_lang')[0])
                                            ->first();

                                // $lang_product->name;

                                foreach ($combinations as $key => $value) {
                                    $id_country = 0;
                                    if($prestashopLangs->pluck('iso_code')[0] != 'es'){
                                        $id_country = DB::connection('prestashop')
                                            ->table('aalv_country')
                                            ->where('iso_code', $prestashopLangs->pluck('iso_code')[0])
                                            ->first();
                                    }

                                    $specificPrices = DB::connection('prestashop')
                                            ->table('aalv_specific_price')
                                            ->where('id_product', $value->id_product)
                                            ->where('id_product_attribute', $value->id_product_attribute)
                                            ->where('id_country', $id_country)
                                            ->where(function ($query) {
                                                $query->where('from', '<=', now())
                                                    ->orWhere('from', '0000-00-00 00:00:00');
                                            })
                                            ->where(function ($query) {
                                                $query->where('to', '>=', now())
                                                    ->orWhere('to', '0000-00-00 00:00:00');
                                            })
                                            ->first();

                                    $iva = DB::connection('mysql')
                                                ->table('langs')
                                                ->where('iso_code', $prestashopLangs->pluck('iso_code')[0])
                                                ->first();

                                    if ($specificPrices) {
                                        $finalPriceWithIVA = round(
                                            ((float) $specificPrices->price - (float) $specificPrices->reduction)
                                            * (1 + (float) $iva->iva / 100),
                                            2
                                        );
                                    } else {
                                        // Manejar el caso en que no hay precio específico
                                        $finalPriceWithIVA = 0; // o algún valor por defecto
                                    }


                                    // $value->reference;
                                    $country = '/';
                                    if($prestashopLangs->pluck('iso_code')[0] != 'es'){
                                        $country = '/'.$prestashopLangs->pluck('iso_code')[0].'/';
                                    }
                                    $url = 'https://a-alvarez.com'.$country.$psProduct->id_product.'-'.$lang_product->link_rewrite.'?id_product_attribute='.$value->id_product_attribute;

                                    $stock_product = DB::connection('prestashop')
                                            ->table('aalv_stock_available')
                                            ->where('id_product', $value->id_product)
                                            ->where('id_product_attribute', $value->id_product_attribute)
                                            ->first();
                                    // $stock_product->quantity;

                                    $datos_gestion = DB::connection('prestashop')
                                            ->table('aalv_combinaciones_import')
                                            ->where('id_product_attribute', $value->id_product_attribute)
                                            ->first();


                                    dd($datos_gestion);

                                    // Requisitos de Ana
                                    // Referencias PESCA: irán al comparador los productos con PRECIO superior a 20€
                                    // Referencias del RESTO de deportes (NO PESCA): irán al comparador los productos con PRECIO superior a 40€
                                    // "Segunda mano" NO SE INCLUYE EN EL COMPARADOR
                                    // Debemos aplicar el bloqueo de PS

                                }




                            }







                            // $newPrice = (float) $psProduct->price;

                            // if ($comparatorProduct->exists &&
                            //     isset($comparatorProduct->current_price) &&
                            //     (float) $comparatorProduct->current_price !== $newPrice) {
                            //     ProductPriceHistory::create([
                            //         'comparator_product_id' => $comparatorProduct->id,
                            //         'old_price' => $comparatorProduct->current_price,
                            //         'new_price' => $newPrice,
                            //     ]);
                            // }

                            // $langSyncData = [];

                            // foreach ($psProduct->langs as $langEntry) {
                            //     $psLang = $prestashopLangs->get($langEntry->id_lang);
                            //     if (!$psLang) continue;

                            //     $localLang = $localLangs->get($psLang->iso_code);
                            //     if (!$localLang) continue;

                            //     $langSyncData[$langEntry->id_lang] = [
                            //         'lang_id' => $localLang->id,
                            //         'title' => $langEntry->name,
                            //         'characteristics' => $langEntry->description,
                            //         'url' => $langEntry->link_rewrite,
                            //     ];
                            // }

                            // if (!empty($langSyncData)) {
                            //     $comparatorProduct->langs()->syncWithoutDetaching($langSyncData);
                            // }


                            // dump($psProduct->prices);

                            // foreach ($psProduct->prices as $price) {

                            //     // Si el precio tiene su propio id_lang, usarlo
                            //     $priceLangId = $price->id_lang ?? null;
                            //     $targetLangId = null;

                            //     if ($priceLangId && isset($langSyncData[$priceLangId])) {
                            //         $targetLangId = $langSyncData[$priceLangId]['lang_id'];
                            //     } else {
                            //         $firstLangData = reset($langSyncData);
                            //         $targetLangId = $firstLangData ? $firstLangData['lang_id'] : null;
                            //     }
                            //     if ($targetLangId) {

                            //         ProductLang::updateOrCreate(
                            //             [
                            //                 'product_id' => $comparatorProduct->id,
                            //                 'prestashop_id' => $price->id_specific_price,
                            //                 'lang_id' => $targetLangId,
                            //             ],
                            //             [
                            //                 'from_quantity' => $price->from_quantity,
                            //                 'price' => $price->price,
                            //                 'reduction' => $price->reduction,
                            //                 'reduction_tax' => $price->reduction_tax,
                            //                 'reduction_type' => $price->reduction_type,
                            //                 'from' => $price->from,
                            //                 'to' => $price->to,
                            //             ]
                            //         );
                            //     }
                            // }

                            // dump($comparatorProduct);
                        }

                    }
                }
            });

    }

    public static function isBlocked($id_product = null)
    {
        $context = Context::getContext();

        $id_country = 6; //default España
        if ($context->language->id == 1) $id_country = 6;
        if ($context->language->id == 2) $id_country = 17;
        if ($context->language->id == 3) $id_country = 8;
        if ($context->language->id == 4) $id_country = 15;
        if ($context->language->id == 5) $id_country = 1;
        if ($context->language->id == 6) $id_country = 10;

        if (is_object($context->cart) && !empty($context->cart->id_address_delivery)) {
            $address = new Address($context->cart->id_address_delivery);
            $id_country = $address->id_country;

        } /*elseif (!empty($context->country->th_country_selected)) {
            $id_country = $context->country->th_country_selected;

        } elseif (!empty($context->country->id)) {
            $id_country = $context->country->id;
        } else {
            $id_country = Configuration::get('PS_COUNTRY_DEFAULT');
        }*/

        // dump(Context::getContext());die();

        if (Product::bloqueoMarcasCategorias($id_product, $id_country, 1)) {
            return true;
        }
        if (Product::bloqueoMarcasCategorias($id_product, $id_country, 2)) {
            return true;
        }
        if (Product::bloqueoFeature($id_product, $id_country)) {
            return true;
        }
        if (Product::bloqueoEtiqueta($id_product, $id_country)) {
            return true;
        }
        return false;
    }

    public static function bloqueoMarcasCategorias($id_product, $id_country, $tipo)
    {
        if ($tipo == 1) {
            $buscar = DB::getInstance()->getValue("SELECT id_manufacturer FROM aalv_product WHERE id_product = " . $id_product);
            $buscar_bloqueo = Db::getInstance()->executeS("SELECT id_country,excepcion FROM aalv_bloqueos WHERE id_tipo = 1 AND valor = " . $buscar);
        } else {
            $buscar = DB::getInstance()->executeS("SELECT id_category FROM aalv_category_product WHERE id_product = " . $id_product);
            $id_categories = array_map(function ($item) {
                return $item["id_category"];
            }, $buscar);
            $buscar = implode(",", $id_categories);
            if (!empty($buscar)) {
                $buscar_bloqueo = Db::getInstance()->executeS(
                    "SELECT id_country, excepcion FROM aalv_bloqueos WHERE id_tipo = 2 AND valor IN (" . $buscar . ")"
                );
            } else {
                $buscar_bloqueo = []; // o null, según lo que necesites
            }
        }
        foreach ($buscar_bloqueo as $value) {
            if ($value['id_country'] != 0) {
                if ($value['id_country'] == $id_country) {
                    return true;
                }
            } else if ($value['id_country'] == 0) {
                $excepcion = explode(",", $value['excepcion']);
                $excepcion = array_map('trim', $excepcion);
                if (in_array($id_country, $excepcion)) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }

    public static function bloqueoFeature($id_product, $id_country)
    {
        $buscar_feature = DB::getInstance()->executeS("SELECT id_feature_value FROM aalv_feature_product afp WHERE id_product = " . $id_product);
        foreach ($buscar_feature as $value) {
            $buscar = DB::getInstance()->executeS("SELECT ab.id_country,ab.valor,ab.excepcion FROM aalv_bloqueos_tipo abt LEFT JOIN aalv_bloqueos ab ON ab.id_tipo = abt.id WHERE abt.codigo != 0 AND abt.codigo = " . $value['id_feature_value']);
            if (count($buscar) != 0) {
                foreach ($buscar as $val) {
                    if ($val['valor'] == 1) {
                        if ($val['id_country'] != 0) {
                            if ($val['id_country'] == $id_country) {
                                return true;
                            }
                        } else if ($val['id_country'] == 0) {
                            $excepcion = explode(",", $val['excepcion']);
                            $excepcion = array_map('trim', $excepcion);
                            if (in_array($id_country, $excepcion)) {
                                return false;
                            } else {
                                return true;
                            }
                        }
                    }
                }
            }
        }
    }

    public static function bloqueoEtiqueta($id_product, $id_country)
    {

        try {
            $buscamos_etiquetas = DB::getInstance()->executeS("SELECT id_country, valor FROM aalv_bloqueos WHERE valor NOT REGEXP '[0-9]'");
            foreach ($buscamos_etiquetas as $value) {
                $id_products = DB::getInstance()->executeS(" SELECT
                                                                    apa.id_product
                                                            FROM
                                                                aalv_combinaciones_import aci
                                                                LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                                                            WHERE
                                                                apa.id_product = " . $id_product . "
                                                                AND aci.etiqueta LIKE '%" . $value['valor'] . "%'
                                                            UNION
                                                            SELECT id_product FROM aalv_combinacionunica_import WHERE id_product = " . $id_product . " AND etiqueta LIKE '%" . $value['valor'] . "%'");
                if (count($id_products) > 0) {
                    if ($id_product) {
                        if ($value['id_country'] == $id_country) {
                            return true;
                        }
                    }
                }
            }
            return false;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error en bloqueoEtiqueta[' . $e->getMessage() . ']');
            return false;
        }
    }
}
