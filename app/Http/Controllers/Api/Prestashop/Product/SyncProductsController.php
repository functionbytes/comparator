<?php

namespace App\Http\Controllers\Api\Prestashop\Product;

use App\Http\Controllers\Controller;
use App\Jobs\Prestashop\SynchronizationProducts;
use App\Models\Lang;
use App\Models\Manufacturer;
use App\Models\Prestashop\Product\Product as PrestashopProduct;
use App\Models\Prestashop\Manufacturer as PrestashopManufacturer;

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

        return
            PrestashopProduct::with(['langs'])
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


                            $comparatorProduct = Product::firstOrCreate([
                                'prestashop_id' => $psProduct->id_product,
                                'ean' => $psProduct->ean,
                                'upc' => $psProduct->upc,
                                'category_id' => $psProduct->base_parent_category->id_category,
                                'manufacturer_id' => $manufacturer,
                                'available' => 1,
                                'type' => count($combinations)>0 ? 'combination' : 'simple'
                            ]);

                            foreach ($langs as $lang) {

                                $psLang = $prestashopLangs->get($lang->id_lang);
                                $localLang = $localLangs->get($psLang->iso_code);

                                $langProduct = ProductLang::firstOrCreate([
                                    'product_id' => $comparatorProduct->id,
                                    'lang_id' => $localLang->id,
                                    'title' => $lang->name,
                                    'url' => $lang->url,
                                    'price' =>  0.0,
                                ]);

                                switch ($comparatorProduct->type) {
                                    case 'combination':

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
                                                'available' => $combination->stock?->quantity > 0,
                                                'attribute_id' => $combination->id_product_attribute,
                                                'url' => null,
                                            ], []);

                                            $langProduct->stock = $combination->stock?->quantity ?? 0;
                                            $langProduct->price = $finalPriceWithIVA;
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

                                        ProductReference::updateOrCreate([
                                            'reference' => $psProduct->reference,
                                            'combination_id' => null,
                                            'product_id' => $comparatorProduct->id,
                                            'lang_id' => $localLang->id,
                                            'available' => $psProduct->stock?->quantity > 0,
                                            'attribute_id' => null,
                                            'url' => null,
                                        ], []);

                                        $langProduct->stock = $psProduct->stock?->quantity ?? 0;
                                        $langProduct->price = $finalPriceWithIVA;
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

    }

    function xmlItemProducto($producto, $portes_referencia,$resultado_arrays_correctos, $aOptionsByType, $idLangPs) {

//        $modelo = $modeloDAO->get($producto->id_modelo);
//    `
//                $result_ps     = mysqli_fetch_array(mysqli_query($dbconPS, "select
//                                            apl.name
//                                        FROM
//                                            aalv_product_import api
//                                            left join aalv_product_lang apl on api.id_product = apl.id_product
//                                        WHERE
//                                            apl.id_lang = ".$idLangPs."
//                                            AND api.id_modelo = ".$producto->id_modelo));
//
//                if($result_ps[0] != ''){
//                    $modelo->nombre = $result_ps[0];
//                }
//
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
//                $texto_marca = '<brand><![CDATA['.trim($marca->nombre).']]></brand>';
//
//                $texto_ean = '<ean>'.implode(',',$producto->alternativeEans).'</ean>';
//
//                $texto_upc = '<upc>'.implode(',',$producto->alternativeUpcs).'</upc>';
//
//                $texto_tag = '<tag>'.trim($producto->etiqueta).'</tag>';
//
//                $array_ruta_categoria = array();
//
//                $texto_stock .= '<stock>true</stock>';
//
//                $texto_estado_gestion = '';
//                switch ($producto->estado_gestion) {
//                    case '0': $texto_estado_gestion='<internal_status>Anulado</internal_status>';break;
//                    case '1': $texto_estado_gestion='<internal_status>Activo</internal_status>';break;
//                    case '2': $texto_estado_gestion='<internal_status>A extinguir</internal_status>';break;
//                }
//
//                $texto_precio_costo_proveedor = '';
//
//                $texto_codigo_proveedor = '<codigo_proveedor>'.$producto->codigo_proveedor.'</codigo_proveedor>';
//
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
//                <id>';
//
//                $output .= $producto->referencia;
//
//                $prodPS = $productPSDAO->getProductByModelId($modelo->id, $app->currentLanguage());
//
//                $canonical = 'https://www.a-alvarez.com/' . $prodPS->link;
//                $ruta_imagen = 'https://www.a-alvarez.com/' . $prodPS->image;
//
//                $output .='</id>
//                <url><![CDATA['.$canonical.']]></url>
//                <name><![CDATA['.$modelo->nombre.']]></name>
//                <price>'.$precio.'</price>
//                <image><![CDATA['.$ruta_imagen.']]></image>
//                <category><![CDATA['.implode(' > ', $array_ruta_categoria).']]></category>
//                <shop><![CDATA['.((is_array($array_ruta_categoria) && !empty($array_ruta_categoria[0])) ? $array_ruta_categoria[0] : '').']]></shop>
//                '.$texto_marca.'
//                '.$texto_ean.'
//                '.$texto_upc.'
//                '.$texto_tag.'
//                '.$texto_stock.'
//                '.$texto_estado_gestion.'
//                '.$texto_codigo_proveedor.'
//                '.$texto_precio_costo_proveedor.'
//                '.$texto_unidades.'
//                '.$texto_precio_unitario.'
//                '.$texto_opciones.'
//            </product>
//        ';
//`
        //      return $output;
    }


    public function xml($lang = 'es')
    {
        $lang = Lang::iso($lang);

        $products = Product::with([
            'references' => fn($q) => $q->where('lang_id', $lang->id),
            'langs' => fn($q) => $q->where('lang_id', $lang->id),
        ])
            ->where('available', 1)
            ->take(5)
            ->get();


        $xml = new \SimpleXMLElement('<products/>');

        foreach ($products as $product) {
            // Solo la primera coincidencia, ya que solo hay un lang por ID
            $productLang = $product->langs->first();

            foreach ($product->references as $reference) {
                $productXml = $xml->addChild('product');
                $productXml->addChild('id', htmlspecialchars($reference->reference));

                if ($productLang) {
                    $productXml->addChild('name', htmlspecialchars($productLang->title));
                    $productXml->addChild('url', htmlspecialchars($productLang->url));
                    $productXml->addChild('price', number_format($productLang->price, 2, '.', ''));
                } else {
                    $productXml->addChild('name', '');
                    $productXml->addChild('url', '');
                    $productXml->addChild('price', '');
                }

                $productXml->addChild('shop', '');
                $productXml->addChild('brand', $product->manufacturer?->title);
                $productXml->addChild('ean', $product->ean);
                $productXml->addChild('tag', '');
                $productXml->addChild('stock', $productLang->stock > 0 ? 'true' : 'false');
                $productXml->addChild('internal_status', $reference->available ? 'Activo' : 'Inactivo');
                $productXml->addChild('codigo_proveedor', '');



            }

        }

        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/xml');


    }

    public function jobs()
    {
        dispatch(new SynchronizationProducts);

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
