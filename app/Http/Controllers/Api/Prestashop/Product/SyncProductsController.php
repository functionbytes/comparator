<?php

namespace App\Http\Controllers\Api\Prestashop\Product;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Jobs\Prestashop\SynchronizationProducts;
use App\Models\Lang;
use App\Models\Manufacturer;
use App\Models\Prestashop\Lang as PrestashopLang;
use App\Models\Prestashop\Manufacturer as PrestashopManufacturer;
use App\Models\Prestashop\Product\Product as PrestashopProduct;
use App\Models\ProductReferenceManagement;
use App\Models\Prestashop\Combination\Import as PsCombImport;
use App\Models\Prestashop\Combination\Unique as PsCombUnique;
use App\Models\Product;
use App\Models\ProductLang;
use App\Models\ProductReference;
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

        return
            PrestashopProduct::with(['import', 'langs', 'combinations'])
                    ->where('active', 1)
                    ->whereHas('import')              // solo productos que tengan import relacionado
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

                            $parentid = $psProduct->defaultCategory
                                            ? optional($psProduct->base_parent_category)->id_category
                                            : null;

                            $categoryId = $psProduct->defaultCategory :: null;

                            $comparatorProduct = Product::updateOrCreate(
                                ['prestashop_id' => $psProduct->id_product], // solo la clave única/lookup
                                [
                                    'category_id'     => $categoryId,
                                    'parentID'        => $parentid,
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

    }

    public function sync_copi()
    {

        return PrestashopProduct::with(['langs'])
                ->orderBy('id_product')
                ->where('active', 1)
                ->chunkById(1, function ($prestashopProducts) {

                    // 1) Log original
                    Log::info('Procesando lote de productos (antes de filtrar): ' . count($prestashopProducts));

                    // 2) Filtramos según la categoría y el precio (sin IVA)
                    $prestashopProducts = $prestashopProducts->filter(function ($psProduct) {


                        // Obtenemos el precio base (from_quantity = 1), según si tiene combinaciones o es simple
                        if ($psProduct->combinations->isNotEmpty()) {
                            // Para productos con combinaciones, tomamos el primer precio de la primera combinación
                            $firstCombination = $psProduct->combinations->first();
                            $specificPrice   = $firstCombination->prices->firstWhere('from_quantity', 1);

                        } else {
                            // Para productos simples, precio directo
                            $specificPrice = $psProduct->prices->firstWhere('from_quantity', 1);
                        }


                        // Si no hay precio definido, descartamos
                        if (! $specificPrice) {
                            return false;
                        }

                        // Calculamos precio neto (sin IVA) o con IVA si lo prefieres aquí
                        $netPrice = (float) $specificPrice->price - (float) $specificPrice->reduction;

                        // Sacamos la categoría principal (la que usas luego en el 'category_id' del comparador)
                        $categoryId = optional($psProduct->defaultCategory)->id_category;

                        // Umbral: si categoría 5 => >20€, resto => >40€
                        $threshold = ($categoryId === 5) ? 20 : 40;

                        return $netPrice > $threshold;
                    });

                    // 3) Log tras el filtrado
                    Log::info('Procesando lote de productos (tras filtrar): ' . count($prestashopProducts));


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
                                'category_id' => $psProduct->defaultCategory!=null ? $psProduct->base_parent_category->id_category : null,
                                'manufacturer_id' => $manufacturer,
                                'available' => 1,
                                'type' => count($combinations)>0 ? 'combination' : 'simple'
                            ]);

                            $type = $comparatorProduct->type;

                            foreach ($langs as $lang) {

                                $psLang = $prestashopLangs->get($lang->id_lang);
                                $localLang = $localLangs->get($psLang->iso_code);

                                $langProduct = ProductLang::firstOrCreate([
                                    'product_id' => $comparatorProduct->id,
                                    'lang_id' => $localLang->id,
                                    'title' => $lang->name,
                                    'url' => $lang->url,
                                    'img' => $psProduct->getImageUrl($localLang->id),
                                    'price' =>  0.0,
                                ]);


                                switch ($type) {
                                    case 'combination':
                                        foreach ($combinations as $combination) {

                                            $finalPriceWithIVA = 0.0;
                                            $prices = $combination->prices;
                                            $specificPrice = $prices->first();

                                            if (isset($specificPrice)) {
                                                // dd($specificPrice->price);
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

                                            // if($finalPriceWithIVA == '0.0'){
                                            //     dd($psProduct->id_product,$finalPriceWithIVA,$specificPrice,$combination);
                                            // }

                                            $comparatorProduct->stock = $psProduct->stock?->quantity ?? 0;
                                            $langProduct->price = $finalPriceWithIVA;
                                            $langProduct->available = $psProduct->stock?->quantity > 0;
                                            $langProduct->save();

                                        }

                                        break;

                                    case 'simple':

                                        $finalPriceWithIVA = 0.0;
                                        $specificPrice = $psProduct->prices->first();

                                        if (isset($specificPrice)) {
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

                                        $comparatorProduct->stock = $psProduct->stock?->quantity ?? 0;
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
                'langs'        => fn ($q) => $q->where('lang_id', $lang->id),
                'references' => fn ($q) => $q->where('lang_id', $lang->id)->with('management'),
                'manufacturer:id,title',
            ])
            ->chunk(100, function ($products) use (&$xml) {

                foreach ($products as $product) {


                    $productLang = $product->langs->first();
                    if (!$productLang) {
                        continue;
                    }

                    $validRefs = $product->references->filter(function ($reference) use ($product, $productLang) {
                        $price    = (float) $reference->price;
                        $minPrice = $product->category_id == 5 ? 20 : 40;

                        $sinStock = ($productLang->pivot->stock ?? 0) <= 0; // ajusta si el stock está en otro sitio

                        return $price > $minPrice
                            && $reference->management->estado_gestion != 0
                            && $reference->management->tags !== 'SEGUNDA MANO'
                            // descarta los que están en estado 2 y sin stock
                            && !($reference->management->estado_gestion == 2 && $sinStock);
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
                        $eanList = $refs->pluck('management.ean')->filter()->unique()->implode(',');
                        $upcList = $refs->pluck('management.upc')->filter()->unique()->implode(',');

                        $p = $xml->addChild('product');
                        // SOLO una reference como id
                        $p->addChild('id',        htmlspecialchars($firstRef->reference));
                        $p->addChild('url',       htmlspecialchars($productLang->pivot->url));
                        $p->addChild('name',      htmlspecialchars($productLang->pivot->title));
                        $p->addChild('price',     $price);
                        $p->addChild('image',     htmlspecialchars($productLang->pivot->img));
                        $p->addChild('shop',      '');
                        $p->addChild('brand',     htmlspecialchars($product->manufacturer?->title));

                        if ($eanList !== '') {
                            $p->addChild('ean', $eanList);
                        }elseif ($upcList !== '') {
                            $p->addChild('upc', $upcList);
                        }

                        $p->addChild('tag', $firstRef->management->tags);
                        $p->addChild('stock', $productLang->pivot->stock > 0 ? 'true' : 'false');

                        switch ($firstRef->management->estado_gestion) {
                            case '0': $p->addChild('internal_status', 'Anulado');     break;
                            case '1': $p->addChild('internal_status', 'Activo');      break;
                            case '2': $p->addChild('internal_status', 'A extinguir'); break;
                        }

                        $p->addChild('codigo_proveedor', $firstRef->management->codigo_proveedor);
                        $p->addChild('category', '');
                        // dump($p);
                    }
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

        return response()->streamDownload(function () use ($langIso) {

            $out = fopen('php://output', 'w');

            // BOM para que Excel detecte UTF-8
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados
            fputcsv($out, [
                'id','url','name','price','image','shop','brand','ean','upc',
                'tag','stock','internal_status','codigo_proveedor','category'
            ]);

            $lang = \App\Models\Lang::iso($langIso);
            $rowCount = 0;

            \App\Models\Product::where('available', 1)
                ->whereHas('langs', fn ($q) => $q->where('lang_id', $lang->id))
                ->with([
                    'langs'        => fn ($q) => $q->where('lang_id', $lang->id),
                    'references'   => fn ($q) => $q->where('lang_id', $lang->id)->with('management'),
                    'manufacturer:id,title',
                ])
                ->chunk(1000, function ($products) use (&$rowCount, $out) {

                    foreach ($products as $product) {
                        $productLang = $product->langs->first();
                        if (!$productLang) {
                            continue;
                        }

                        foreach ($product->references as $reference) {
                            $price    = (float) $reference->price;
                            $minPrice = $product->category_id == 5 ? 20 : 40;
                            if ($price <= $minPrice) {
                                continue;
                            }
                            if ($reference->management->estado_gestion == 0) {
                                continue;
                            }
                            if($reference->management->tags == 'SEGUNDA MANO'){
                                continue;
                            }
                            $sinStock = ($productLang->pivot->stock ?? 0) <= 0;
                            if ($reference->management->estado_gestion == 2 && $sinStock) {
                                continue;
                            }

                            $ean = $reference->management->ean;
                            $upc = $reference->management->upc;

                            $internalStatus = match ((string) $reference->management->estado_gestion) {
                                '0' => 'Anulado',
                                '1' => 'Activo',
                                '2' => 'A extinguir',
                                default => '',
                            };

                            fputcsv($out, [
                                $reference->reference,
                                $productLang->pivot->url,
                                $productLang->pivot->title,
                                number_format($reference->price, 2, '.', ''),
                                $productLang->pivot->img,
                                '',
                                optional($product->manufacturer)->title,
                                $ean ?: '',
                                $ean ? '' : $upc,
                                $reference->management->tags,
                                $productLang->pivot->stock > 0 ? 'true' : 'false',
                                $internalStatus,
                                $reference->management->codigo_proveedor,
                                '',
                            ]);
                            $rowCount++;
                        }
                    }
                });

            fclose($out);

        }, $filename, $headers);
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
