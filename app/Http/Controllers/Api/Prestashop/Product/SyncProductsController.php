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

use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


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
use App\Models\ProductPortes;
use App\Models\ProductTag;
use Illuminate\Support\Str;              // <-- AÑADIR


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
        // Obtenemos y anotamos origen
        $products = PsCombImport::management()
            ->orderBy('id_product_attribute')
            ->whereIn("id_articulo", [100271475, 100271476, 100271474, 100271477, 100271473])
            ->limit(10)
            ->get();

        $services = PsCombUnique::management()
            ->whereIn("id_articulo", [100271475, 100271476, 100271474, 100271477, 100271473])
            ->orderBy('id_product')
            ->limit(10)
            ->get();

        $combinados = $products->merge($services)->sortBy('orden')->values();

        try {
            foreach ($combinados as $psCombinationUnicos) {

                $product = $psCombinationUnicos->id_product_attribute
                    ? $psCombinationUnicos->productAttribute?->product
                    : $psCombinationUnicos->product;

                $categoryId = $product?->baseParentCategory?->id_category;

                $psManufacturer = $product->manufacturer;

                if ($psManufacturer) {
                    $manufacturer = \App\Models\Manufacturer::updateOrCreate(
                        ['uid' => $psManufacturer->id_manufacturer],
                        ['title' => $psManufacturer->name],
                        ['available' => $psManufacturer->active]
                    );
                } else {
                    $manufacturer = null;
                }

                $comparatorProduct = Product::updateOrCreate(
                    ['prestashop_id' => $psCombinationUnicos->getBaseProductId()],
                    [
                        'category_id'     => $categoryId,
                        'manufacturer_id' => $manufacturer?->id,
                        'available'       => 1,
                        'type'            => $product?->type(),
                    ]
                );

                $reference = $psCombinationUnicos->id_product_attribute
                    ? $psCombinationUnicos->productAttribute?->reference
                    : $psCombinationUnicos->product->reference;

                $pr = ProductReference::updateOrCreate(
                    [
                        'reference'  => $reference,
                        'product_id' => $comparatorProduct->id,
                    ],
                    [
                        'combination_id'         => $psCombinationUnicos->id_product_attribute ?? null,
                        'attribute_id'           => $psCombinationUnicos->id_product_attribute ?? null,
                        'tags'                   => $psCombinationUnicos->etiqueta ?? null,
                        'id_articulo'            => $psCombinationUnicos->id_articulo ?? null,
                        'unidades_oferta'        => $psCombinationUnicos->unidades_oferta ?? null,
                        'estado_gestion'         => $psCombinationUnicos->estado_gestion ?? null,
                        'es_segunda_mano'        => $psCombinationUnicos->es_segunda_mano ?? 0,
                        'externo_disponibilidad' => $psCombinationUnicos->externo_disponibilidad ?? 0,
                        'codigo_proveedor'       => $psCombinationUnicos->codigo_proveedor ?? null,
                        'precio_costo_proveedor' => $psCombinationUnicos->precio_costo_proveedor ?? null,
                        'tarifa_proveedor'       => $psCombinationUnicos->tarifa_proveedor ?? null,
                        'es_arma'                => $psCombinationUnicos->es_arma ?? 0,
                        'es_arma_fogueo'         => $psCombinationUnicos->es_arma_fogueo ?? 0,
                        'es_cartucho'            => $psCombinationUnicos->es_cartucho ?? 0,
                        'ean'                    => $psCombinationUnicos->ean ?? 0,
                        'upc'                    => $psCombinationUnicos->upc ?? 0,
                    ]
                );

                // 2) Sincronizar etiquetas en product_tags
                try {
                    $tagIds = $this->syncProductTags($psCombinationUnicos->etiqueta ?? null);
                    Log::info('Tags sincronizadas: ' . implode(',', $tagIds));
                } catch (\Throwable $e) {
                    Log::warning('No se pudieron sincronizar tags: ' . $e->getMessage());
                }

                $stock = $psCombinationUnicos->id_product_attribute
                    ? $psCombinationUnicos->productAttribute?->stocks?->quantity
                    : $product->stocks?->quantity;

                // Procesamos todos los idiomas del producto
                foreach ($product?->langs ?? [] as $lang) {

                    $localLangs = Lang::byIsoCodes([$lang->lang->iso_code])->get()->keyBy('iso_code');
                    $localLang = $localLangs->get($lang->lang->iso_code);

                    if (!$localLang) {
                        continue;
                    }


                    ProductLang::updateOrCreate(
                        [
                            'product_id' => $comparatorProduct->id,
                            'lang_id'    => $localLang->id,
                        ],
                        [
                            'title' => $lang->name,
                            'url'   => $lang->url,
                            'img'   => $product?->getImageUrl($localLang->id),
                            'stock' => $stock,
                        ]
                    );

                    // Intento 1: precio por país (JOIN iso)
                    $specificPrice = $psCombinationUnicos->id_product_attribute
                        ? $psCombinationUnicos->productAttribute?->pricesForIso($lang->lang->iso_code)
                        ->activeWindow()
                        ->orderByWindow()
                        ->first()
                        : $product?->pricesForIso($lang->lang->iso_code)
                        ->activeWindow()
                        ->orderByWindow()
                        ->first();

                    // Fallback: precio global (id_country = 0)
                    if (!$specificPrice) {
                        $specificPrice = 0;
                    }

                    $finalPriceWithIVA = 0.0;

                    if ($specificPrice) {
                        $base = (float) $specificPrice->price;
                        $reduction = (float) ($specificPrice->reduction ?? 0);

                        if (($specificPrice->reduction_type ?? null) === 'percentage') {
                            $base *= (1 - $reduction);      // p.ej. 0.10 => 10%
                        } else {
                            $base -= $reduction;            // importe fijo
                        }

                        $finalPriceWithIVA = round(
                            $base * (1 + (float) $localLang->iva / 100),
                            2
                        );
                    }

                    $atributos = $psCombinationUnicos->id_product_attribute
                        ? $psCombinationUnicos->productAttribute?->atributosString($localLang->id)
                        : null;

                    $available = self::isBlocked(
                        $product?->id_product,
                        $localLang->id
                    );

                    $shippingImporte = ProductPortes::getImporte($reference, $lang->lang->iso_code);

                    ProductReferenceLang::updateOrCreate(
                        [
                            'reference_id' => $pr->id,
                            'lang_id'      => $localLang->id,
                        ],
                        [
                            'url'            => $lang->url,
                            'characteristics' => $atributos,
                            'price'          => $finalPriceWithIVA,
                            'portes'         => $shippingImporte,
                            'reduction'      => $specificPrice->reduction ?? 0,
                            'available'      => $available,
                            'img'            => $product?->getImageUrl($localLang->id),
                        ]
                    );
                }
                // die();
            }
        } catch (Throwable $e) {
            Log::error('Error during product sync chunk: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function excelToDisk(string $langIso = 'de', bool $porCategoria = false)
    {
        ini_set('max_execution_time', 1200);
        ini_set('memory_limit', '4096M');

        $filename = $porCategoria
            ? "products_by_category_{$langIso}.xlsx"
            : "products_{$langIso}.xlsx";

        $filepath = storage_path("app/exports/{$filename}");

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0777, true);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Quitar hoja inicial vacía

        $lang = \App\Models\Lang::iso($langIso);
        if (!$lang) {
            return response()->json(['error' => 'Idioma no encontrado.'], 400);
        }

        // Catálogos solo para títulos de hoja
        $categoryNames = [
            3 => 'Golf',
            4 => 'Caza',
            5 => 'Pesca',
            6 => 'Hipica',
            7 => 'Buceo',
            8 => 'Nautica',
            9 => 'Esqui',
            10 => 'Padel',
            11 => 'Aventura',
            99 => 'Sin categoria'
        ];

        // === Exclusiones por idioma (en DE/FR/IT se excluyen 8,9,10,11) ===
        $excludedByLang = [
            'fr' => [8, 9, 10, 11],
            'de' => [8, 9, 10, 11],
            'it' => [8, 9, 10, 11],
        ];
        $excludedCats = $excludedByLang[strtolower($langIso)] ?? [];

        // === Reglas de exclusión por TAG según idioma ===
        $langIsoLower = strtolower($langIso);
        $excludeNoexForLang = in_array($langIsoLower, ['fr', 'de', 'it'], true);

        $totalProductos = 0;

        if ($porCategoria) {
            // Obtenemos las categorías disponibles para el idioma, excluyendo las vetadas
            $categoryIds = \App\Models\Product::where('available', 1)
                ->whereHas('langs', fn($q) => $q->where('lang_id', $lang->id))
                ->when(!empty($excludedCats), fn($q) => $q->whereNotIn('category_id', $excludedCats))
                ->distinct()
                ->pluck('category_id');

            $createdAnySheet = false;

            foreach ($categoryIds as $categoryId) {
                $sheetData = [[
                    'modelo',
                    'id',
                    'url',
                    'name',
                    'price',
                    'image',
                    'shop',
                    'brand',
                    'ean',
                    'upc',
                    'tag',
                    'stock',
                    'internal_status',
                    'codigo_proveedor',
                    'category_id',
                    'has_stock'
                ]];

                $products = \App\Models\Product::with([
                    // Usamos el lang solicitado, no 1 fijo
                    'defaultLang' => fn($q) => $q->where('lang_id', $lang->id),
                    'references.langs',
                    'manufacturer',
                    'importtt'
                ])
                    ->where('available', 1)
                    ->where('category_id', $categoryId)
                    ->whereHas('langs', fn($q) => $q->where('lang_id', $lang->id))
                    ->when(!empty($excludedCats), fn($q) => $q->whereNotIn('category_id', $excludedCats))
                    ->get();

                foreach ($products as $product) {
                    // Acceso seguro al lang del producto
                    $productLang = $product->lang[$lang->id - 1] ?? null;
                    if (!$productLang) continue;

                    // Filtrar referencias válidas con exclusión por tags
                    $validRefs = $product->references->filter(function ($reference) use ($product, $lang, $excludeNoexForLang) {
                        $refLang = $reference->lang($lang->id) ?? null;
                        if (!$refLang) return false;

                        // Debe estar disponible (available === 0)
                        if ((int) ($refLang->available ?? 1) !== 0) return false;

                        // Parseo de tags a array uppercase
                        $rawTags = $reference->tags ?? '';
                        $tags = is_array($rawTags)
                            ? array_map(static fn($t) => strtoupper(trim((string)$t)), $rawTags)
                            : preg_split('/[,\s]+/', strtoupper((string)$rawTags), -1, PREG_SPLIT_NO_EMPTY);
                        $tags = $tags ?: [];

                        // Exclusiones de tags:
                        // - NOCOMP y OUTLET: SIEMPRE
                        // - NOEX: SOLO para FR, DE, IT
                        if (in_array('NOCOMP', $tags, true)) return false;
                        if (in_array('OUTLET', $tags, true)) return false;
                        if ($excludeNoexForLang && in_array('NOEX', $tags, true)) return false;

                        // 🔹 Nueva regla: si estado_gestion === 0 → nunca pasa
                        if ((int) $reference->estado_gestion === 0) return false;

                        // Reglas adicionales
                        $categoryId = $product->category_id ?? 99;
                        $price    = (float) ($refLang->price ?? 0);
                        $minPrice = ($categoryId == 5) ? 20 : 40;

                        $sinStock   = (int) ($reference->stock ?? 0) <= 0;
                        $excluyeMfr = in_array((int) $product->manufacturer_id, [419, 133], true);

                        if ((int)$reference->estado_gestion === 2 && $sinStock) return false;

                        return $price > $minPrice && !$excluyeMfr;
                    });

                    if ($validRefs->isEmpty()) continue;

                    // Agrupar por precio del lang iterado
                    $groups = $validRefs->groupBy(function ($r) use ($lang) {
                        $rl = $r->langs->firstWhere('lang_id', $lang->id) ?? $r->langs->first();
                        return number_format((float) ($rl->price ?? 0), 2, '.', '');
                    });

                    foreach ($groups as $refs) {
                        // 1) Referencias marcadas: una fila por cada default_minderest = 1
                        $marked   = $refs->filter(fn($r) => (int)($r->default_minderest ?? 0) === 1);
                        $unmarked = $refs->reject(fn($r) => (int)($r->default_minderest ?? 0) === 1);

                        foreach ($marked as $mref) {
                            $refLang = $mref->langs->firstWhere('lang_id', $lang->id) ?? $mref->langs->first();

                            $eanList = collect([$mref->ean])->filter()->unique()->implode(',');
                            $upcList = collect([$mref->upc])->filter()->unique()->implode(',');

                            $internalStatus = match ((string) $mref->estado_gestion) {
                                '0' => 'Anulado',
                                '1' => 'Activo',
                                '2' => 'A extinguir',
                                default => '',
                            };

                            $sheetData[] = [
                                $product->importtt->id_modelo ?? '',
                                $mref->reference,
                                $productLang->url ?? '',
                                $productLang->title ?? '',
                                $refLang->price ?? '',
                                $productLang->img ?? '',
                                '',
                                $product->manufacturer?->title ?? '',
                                $eanList ?: '',
                                $eanList ? '' : $upcList,
                                $mref->tags,
                                $productLang->stock,
                                $internalStatus,
                                $mref->codigo_proveedor,
                                $product->category_id,
                                $productLang->stock > 0 ? 'true' : 'false',
                            ];

                            $totalProductos++;
                        }

                        // 2) Referencias NO marcadas: una sola por grupo
                        if ($unmarked->isNotEmpty()) {
                            $firstRef = $unmarked->first();
                            $refLang = $firstRef->langs->firstWhere('lang_id', $lang->id) ?? $firstRef->langs->first();

                            $eanList = $unmarked->pluck('ean')->filter()->unique()->implode(',');
                            $upcList = $unmarked->pluck('upc')->filter()->unique()->implode(',');

                            $internalStatus = match ((string) $firstRef->estado_gestion) {
                                '0' => 'Anulado',
                                '1' => 'Activo',
                                '2' => 'A extinguir',
                                default => '',
                            };

                            $sheetData[] = [
                                $product->importtt->id_modelo ?? '',
                                $firstRef->reference,
                                $productLang->url ?? '',
                                $productLang->title ?? '',
                                $refLang->price ?? '',
                                $productLang->img ?? '',
                                '',
                                $product->manufacturer?->title ?? '',
                                $eanList ?: '',
                                $eanList ? '' : $upcList,
                                $firstRef->tags,
                                $productLang->stock,
                                $internalStatus,
                                $firstRef->codigo_proveedor,
                                $product->category_id,
                                $productLang->stock > 0 ? 'true' : 'false',
                            ];

                            $totalProductos++;
                        }
                    }
                }

                if (count($sheetData) > 1) {
                    $sheetTitle = $categoryNames[$categoryId] ?? "Categoria_{$categoryId}";
                    $sheetTitle = mb_substr($sheetTitle, 0, 31);

                    $sheet = $spreadsheet->createSheet();
                    $sheet->setTitle($sheetTitle);
                    $sheet->fromArray($sheetData, null, 'A1', true);

                    $createdAnySheet = true;
                }
            }

            // Si todas las categorías quedaron excluidas o sin datos, creamos una hoja dummy
            if ($porCategoria && !$createdAnySheet) {
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle('Sin datos');
                $sheet->setCellValue('A1', 'No se encontraron productos para exportar');
            }
        } else {
            $sheetData = [[
                'modelo',
                'id',
                'url',
                'name',
                'price',
                'image',
                'shop',
                'brand',
                'ean',
                'upc',
                'tag',
                'stock',
                'internal_status',
                'codigo_proveedor',
                'category_id',
                'has_stock'
            ]];

            $products = \App\Models\Product::with([
                'defaultLang' => fn($q) => $q->where('lang_id', $lang->id),
                'references.langs',
                'manufacturer',
                'importtt'
            ])
                ->where('available', 1)
                ->whereHas('langs', fn($q) => $q->where('lang_id', $lang->id))
                ->when(!empty($excludedCats), fn($q) => $q->whereNotIn('category_id', $excludedCats))
                ->get();

            foreach ($products as $product) {
                $productLang = $product->lang[$lang->id - 1] ?? null;
                if (!$productLang) continue;

                $validRefs = $product->references->filter(function ($reference) use ($product, $lang, $excludeNoexForLang) {
                    $refLang = $reference->lang($lang->id) ?? null;
                    if (!$refLang) return false;

                    if ((int) ($refLang->available ?? 1) !== 0) return false;

                    $rawTags = $reference->tags ?? '';
                    $tags = is_array($rawTags)
                        ? array_map(static fn($t) => strtoupper(trim((string)$t)), $rawTags)
                        : preg_split('/[,\s]+/', strtoupper((string)$rawTags), -1, PREG_SPLIT_NO_EMPTY);
                    $tags = $tags ?: [];

                    // Exclusión por tags (misma regla que arriba)
                    if (in_array('NOCOMP', $tags, true)) return false;
                    if (in_array('OUTLET', $tags, true)) return false;
                    if ($excludeNoexForLang && in_array('NOEX', $tags, true)) return false;

                    // 🔹 Nueva regla: si estado_gestion === 0 → nunca pasa
                    if ((int) $reference->estado_gestion === 0) return false;

                    $categoryId = $product->category_id ?? 99;
                    $price    = (float) ($refLang->price ?? 0);
                    $minPrice = ($categoryId == 5) ? 20 : 40;

                    $sinStock   = (int) ($reference->stock ?? 0) <= 0;
                    $excluyeMfr = in_array((int) $product->manufacturer_id, [419, 133], true);

                    if ((int)$reference->estado_gestion === 2 && $sinStock) return false;

                    return $price > $minPrice && !$excluyeMfr;
                });

                if ($validRefs->isEmpty()) continue;

                $groups = $validRefs->groupBy(function ($r) use ($lang) {
                    $rl = $r->langs->firstWhere('lang_id', $lang->id) ?? $r->langs->first();
                    return number_format((float) ($rl->price ?? 0), 2, '.', '');
                });

                foreach ($groups as $refs) {
                    $marked   = $refs->filter(fn($r) => (int)($r->default_minderest ?? 0) === 1);
                    $unmarked = $refs->reject(fn($r) => (int)($r->default_minderest ?? 0) === 1);

                    if ($marked->isNotEmpty()) {
                        foreach ($marked as $mref) {
                            $refLang = $mref->langs->firstWhere('lang_id', $lang->id) ?? $mref->langs->first();

                            $eanList = collect([$mref->ean])->filter()->unique()->implode(',');
                            $upcList = collect([$mref->upc])->filter()->unique()->implode(',');

                            $internalStatus = match ((string) $mref->estado_gestion) {
                                '0' => 'Anulado',
                                '1' => 'Activo',
                                '2' => 'A extinguir',
                                default => '',
                            };

                            $sheetData[] = [
                                $product->importtt->id_modelo ?? '',
                                $mref->reference,
                                $productLang->url ?? '',
                                $productLang->title ?? '',
                                $refLang->price ?? '',
                                $productLang->img ?? '',
                                '',
                                $product->manufacturer?->title ?? '',
                                $eanList ?: '',
                                $eanList ? '' : $upcList,
                                $mref->tags,
                                $productLang->stock,
                                $internalStatus,
                                $mref->codigo_proveedor,
                                $product->category_id,
                                $productLang->stock > 0 ? 'true' : 'false',
                            ];

                            $totalProductos++;
                        }
                    } elseif ($unmarked->isNotEmpty()) {
                        $firstRef = $unmarked->first();
                        $refLang = $firstRef->langs->firstWhere('lang_id', $lang->id) ?? $firstRef->langs->first();

                        $eanList = $unmarked->pluck('ean')->filter()->unique()->implode(',');
                        $upcList = $unmarked->pluck('upc')->filter()->unique()->implode(',');

                        $internalStatus = match ((string) $firstRef->estado_gestion) {
                            '0' => 'Anulado',
                            '1' => 'Activo',
                            '2' => 'A extinguir',
                            default => '',
                        };

                        $sheetData[] = [
                            $product->importtt->id_modelo ?? '',
                            $firstRef->reference,
                            $productLang->url ?? '',
                            $productLang->title ?? '',
                            $refLang->price ?? '',
                            $productLang->img ?? '',
                            '',
                            $product->manufacturer?->title ?? '',
                            $eanList ?: '',
                            $eanList ? '' : $upcList,
                            $firstRef->tags,
                            $productLang->stock,
                            $internalStatus,
                            $firstRef->codigo_proveedor,
                            $product->category_id,
                            $productLang->stock > 0 ? 'true' : 'false',
                        ];

                        $totalProductos++;
                    }
                }
            }

            if (count($sheetData) > 1) {
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle('Productos');
                $sheet->fromArray($sheetData, null, 'A1', true);
            } else {
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle('Sin datos');
                $sheet->setCellValue('A1', 'No se encontraron productos para exportar');
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);

        \Log::info("Archivo Excel generado: {$filename} con {$totalProductos} productos. Excluidas por idioma {$langIso}: " . implode(',', $excludedCats));

        return response()->download($filepath)->deleteFileAfterSend(true);
    }



    public function xmlToDisk(string $langIso = 'de')
    {
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '2048M');

        // --- Helpers ---
        $u = static function (?string $s): string {
            if ($s === null) return '';
            // Normaliza a UTF-8, elimina bytes inválidos y caracteres de control no permitidos en XML 1.0
            $s = (string) $s;
            // Convierte a UTF-8 ignorando bytes inválidos
            $s = @iconv('UTF-8', 'UTF-8//IGNORE', $s) ?: $s;
            // Si viniera en otra codificación latin1 por ejemplo, intenta una segunda pasada
            if (!mb_check_encoding($s, 'UTF-8')) {
                $s = @iconv(mb_detect_encoding($s, mb_detect_order(), true) ?: 'UTF-8', 'UTF-8//IGNORE', $s) ?: $s;
            }
            // Quita caracteres de control no permitidos en XML (excepto \t, \n, \r)
            $s = preg_replace('/[^\P{C}\t\n\r]/u', '', $s) ?? '';
            return $s;
        };

        $lang = \App\Models\Lang::iso($langIso);
        if (!$lang) {
            return response()->json(['error' => 'Idioma no encontrado.'], 400);
        }

        // Cargamos lo mismo que en Excel para mantener coherencia
        $products = \App\Models\Product::with([
            'defaultLang' => fn($q) => $q->where('lang_id', 1), // igual que tu Excel
            'references.langs',
            'manufacturer'
        ])
            ->where('available', 1)
            ->whereHas('langs', fn($q) => $q->where('lang_id', $lang->id))
            ->get();

        // --- Preparar escritor XML ---
        $dir = 'xml';
        $timestamp = Carbon::now(config('app.timezone'))->format('Ymd_His');
        $file = "products_{$lang->iso_code}_{$timestamp}.xml";
        $disk = Storage::disk('public');

        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $fullPath = $disk->path("{$dir}/{$file}");

        $xw = new \XMLWriter();
        if (!$xw->openURI($fullPath)) {
            return response()->json(['error' => 'No se pudo abrir el archivo para escritura.'], 500);
        }
        $xw->startDocument('1.0', 'UTF-8');
        $xw->setIndent(true);
        $xw->startElement('products');

        $total = 0;

        foreach ($products as $product) {
            // Acceso seguro al lang del producto
            $productLang = $product->lang[$lang->id - 1];
            if (!$productLang) continue;

            // Category null => 99 (igual que Excel)
            $categoryId = $product->category_id ?? 99;

            // Filtrado de referencias válidas
            $validRefs = $product->references->filter(function ($reference) use ($product, $categoryId, $lang) {
                // Lang de la referencia solicitado; fallback al primero por seguridad
                $refLang = $reference->langs->firstWhere('lang_id', $lang->id) ?? $reference->langs[0] ?? null;
                if (!$refLang) return false;

                // --- Exclusión absoluta por tag NOCOMP ---
                $rawTags = $reference->tags ?? '';
                if (is_array($rawTags)) {
                    $tags = array_map(static fn($t) => strtoupper(trim((string)$t)), $rawTags);
                } else {
                    $tags = preg_split('/[,\s]+/', strtoupper((string)$rawTags), -1, PREG_SPLIT_NO_EMPTY);
                }
                if (in_array('NOCOMP', $tags, true)) return false;
                // -----------------------------------------

                // Solo pasa si available === 0
                if ((int)($refLang->available ?? 1) !== 0) return false;

                $price    = (float)($refLang->price ?? 0);
                $minPrice = ($categoryId == 5) ? 20 : 40;

                $sinStock   = (int)($reference->stock ?? 0) <= 0;
                $excluyeMfr = in_array((int)$product->manufacturer_id, [419, 133], true);

                // Si estado_gestion == 2 y además no hay stock → descartar
                if ((int)$reference->estado_gestion === 2 && $sinStock) return false;

                // En estado_gestion == 1 no se chequea stock
                return $price > $minPrice && !$excluyeMfr;
            });

            if ($validRefs->isEmpty()) continue;

            // Agrupamos por precio (dos decimales) como en Excel, usando el lang actual
            $groups = $validRefs->groupBy(function ($r) use ($lang) {
                $rl = $r->langs->firstWhere('lang_id', $lang->id) ?? $r->langs->first();
                return number_format((float)($rl->price ?? 0), 2, '.', '');
            });

            foreach ($groups as $refs) {
                // Separar marcadas y no marcadas por default_minderest
                $marked   = $refs->filter(fn($r) => (int)($r->default_minderest ?? 0) === 1);
                $unmarked = $refs->reject(fn($r) => (int)($r->default_minderest ?? 0) === 1);

                // 1) Cada referencia marcada genera su propia fila
                foreach ($marked as $mref) {
                    $refLang = $mref->langs->firstWhere('lang_id', $lang->id) ?? $mref->langs->first();

                    // EAN/UPC solo de la referencia marcada
                    $eanList = collect([$mref->ean])->filter()->unique()->implode(',');
                    $upcList = collect([$mref->upc])->filter()->unique()->implode(',');

                    $codigoProveedor = collect([$mref->codigo_proveedor])->filter()->unique()->implode(',');

                    // --- Escribir producto ---
                    $xw->startElement('product');

                    $xw->writeElement('id', $u($mref->reference ?? ''));
                    $xw->writeElement('url', $u($productLang->url ?? ''));
                    $xw->writeElement('name', $u($productLang->title ?? ''));
                    $xw->writeElement('price', (string)($refLang->price ?? ''));
                    $xw->writeElement('image', $u($productLang->img ?? ''));
                    $xw->writeElement('brand', $u($product->manufacturer?->title ?? ''));

                    if ($eanList !== '') {
                        $xw->writeElement('ean', $u($eanList));
                    } elseif ($upcList !== '') {
                        $xw->writeElement('upc', $u($upcList));
                    }

                    $xw->writeElement('codigo_proveedor', $u($codigoProveedor));

                    $characteristics = $refLang->characteristics ?? '';
                    $xw->startElement('characteristics');
                    $xw->writeCData($u(is_string($characteristics) ? $characteristics : json_encode($characteristics, JSON_UNESCAPED_UNICODE)));
                    $xw->endElement(); // characteristics

                    $xw->endElement(); // product

                    $total++;
                }

                // 2) No marcadas: una sola fila consolidada (comportamiento previo)
                if ($unmarked->isNotEmpty()) {
                    $firstRef = $unmarked->first();
                    $refLang  = $firstRef->langs->firstWhere('lang_id', $lang->id) ?? $firstRef->langs->first();

                    // Consolidar EAN/UPC/codigo_proveedor del subgrupo no marcado
                    $eanList         = $unmarked->pluck('ean')->filter()->unique()->implode(',');
                    $upcList         = $unmarked->pluck('upc')->filter()->unique()->implode(',');
                    $codigoProveedor = $unmarked->pluck('codigo_proveedor')->filter()->unique()->implode(',');

                    // --- Escribir producto ---
                    $xw->startElement('product');

                    $xw->writeElement('id', $u($firstRef->reference ?? ''));
                    $xw->writeElement('url', $u($productLang->url ?? ''));
                    $xw->writeElement('name', $u($productLang->title ?? ''));
                    $xw->writeElement('price', (string)($refLang->price ?? ''));
                    $xw->writeElement('image', $u($productLang->img ?? ''));
                    $xw->writeElement('brand', $u($product->manufacturer?->title ?? ''));

                    if ($eanList !== '') {
                        $xw->writeElement('ean', $u($eanList));
                    } elseif ($upcList !== '') {
                        $xw->writeElement('upc', $u($upcList));
                    }

                    $xw->writeElement('codigo_proveedor', $u($codigoProveedor));

                    $characteristics = $refLang->characteristics ?? '';
                    $xw->startElement('characteristics');
                    $xw->writeCData($u(is_string($characteristics) ? $characteristics : json_encode($characteristics, JSON_UNESCAPED_UNICODE)));
                    $xw->endElement(); // characteristics

                    $xw->endElement(); // product

                    $total++;
                }
            }
        }


        $xw->endElement(); // products
        $xw->endDocument();
        $xw->flush();

        Log::info("Archivo XML generado: {$file} con {$total} productos.");

        return response()->json([
            'stored'   => true,
            'products' => $total,
            'disk'     => 'public',
            'relative' => "{$dir}/{$file}",
            'path'     => $fullPath, // ruta absoluta real en storage/app/public/xml/...
            'url'      => $disk->url("{$dir}/{$file}"), // si tienes symlink storage:link
        ]);
    }

    public function jobs()
    {
        foreach (['import', 'unique'] as $type) {
            dispatch(new SynchronizationProducts($type));
        }
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

    private function syncProductTags(?string $raw): array
    {
        if (!$raw) return [];

        // Separar por coma, punto y coma o barra vertical; limpiar espacios y vacíos.
        $tags = collect(preg_split('/[,\|;]+/', $raw))
            ->map(fn($t) => trim($t ?? ''))
            ->filter()                           // quita vacíos
            ->unique();                          // evita repetidos en el mismo registro

        if ($tags->isEmpty()) return [];

        // Generar slugs (clave única real) y preparar pares title/slug
        $now = now();
        $rows = $tags->map(function ($title) use ($now) {
            // Mantén el título tal cual (respetando mayúsculas) y crea un slug estable.
            // OJO: Str::slug baja a minúsculas; el título se guarda “bonito”.
            $slug = Str::slug($title, '-');

            // Si el slug queda vacío (por caracteres raros), usa una normalización simple
            if ($slug === '') {
                $slug = Str::of($title)->replaceMatches('/\s+/u', '-')->lower();
            }

            return [
                'title'       => $title,
                'slug'        => (string) $slug,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        });

        // Evitar colisiones por el unique de slug/title:
        // - Opción A: upsert basado en slug (recomendado)
        ProductTag::upsert($rows->all(), ['slug'], ['title', 'updated_at']);

        // Recuperar IDs (por si quieres relacionar más tarde)
        $slugs = $rows->pluck('slug')->all();
        $existing = ProductTag::whereIn('slug', $slugs)->pluck('id', 'slug');

        return $existing->values()->all(); // array de IDs
    }
}
