<?php

namespace App\Http\Controllers\Api\Prestashop\Product;

use App\Http\Controllers\Controller;
use App\Jobs\Prestashop\SynchronizationProducts;
use App\Models\Lang;
use App\Models\Prestashop\Product\Product as PrestashopProduct;
use App\Models\Product;
use App\Models\ProductLang;
use App\Models\ProductPriceHistory;
use App\Models\ProductReference;
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


        return PrestashopProduct::with(['langs'])
            ->where('active', 1)
            ->chunkById(200, function ($prestashopProducts) {

                $prestashopLangIds = [];
                foreach ($prestashopProducts as $product) {
                    foreach ($product->langs as $lang) {
                        $prestashopLangIds[] = $lang->id_lang;
                    }
                }

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

                        $combinations = $psProduct->combinations;
                        $langs = $psProduct->langs;

                        $comparatorProduct = Product::updateOrCreate([
                            'prestashop_id' => $psProduct->id_product,
                            'ean' => $psProduct->ean,
                            'upc' => $psProduct->upc,
                            'category_id' => $psProduct->base_parent_category->id_category,
                            'available' => 1,
                            'type' => count($combinations)>0 ? 'combination' : 'simple'
                        ]);

                        foreach ($langs as $lang) {

                                $psLang = $prestashopLangs->get($lang->id_lang);
                                $localLang = $localLangs->get($psLang->iso_code);

                                $langProduct = ProductLang::updateOrCreate([
                                    'product_id' => $comparatorProduct->id,
                                    'lang_id' => $localLang->id,
                                    'title' => $lang->name,
                                    'url' => $lang->url,
                                    'price' =>  0.0,
                                ]);


                                foreach ($combinations as $combination) {

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

                                    ProductReference::updateOrCreate([
                                        'reference' => $combination->reference,
                                        'combination_id' => $combination->id_product,
                                        'product_id' => $comparatorProduct->id,
                                        'lang_id' => $localLang->id,
                                        'available' => $combination->stock?->quantity > 0 ? true : false,
                                        'attribute_id' => $combination->id_product_attribute,
                                        'url' => null,
                                    ], [

                                    ]);

                                    $langProduct->stock =  $combination->stock?->quantity > 0 ?  $combination->stock?->quantity : 0;
                                    $langProduct->price = $finalPriceWithIVA;
                                    $langProduct->available = $combination->stock?->quantity > 0 ? true : false;
                                    $langProduct->save();

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
