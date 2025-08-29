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
use App\Models\ProductPortes;
use App\Models\ProductTag;
use Illuminate\Support\Str;              // <-- AÑADIR



use App\Http\Controllers\Controller;
use App\Jobs\SyncPrestashopProductsMaster;
use App\Models\Prestashop\Combination\All as PrestashopCombination;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

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

    public $type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $type)
    {
        // This job can be dispatched without arguments to sync all products.
        $this->type = $type;
    }

    public function handle()
    {
        Log::info('SyncPrestashopProducts: Job execution started.');
        $model = $this->type === 'import' ? PsCombImport::class : PsCombUnique::class;

        $model::management()
            ->orderBy($this->type === 'import' ? 'id_product_attribute' : 'id_product')
            ->chunk(100, function ($items) {

                Log::info($this->type . ' => Procesando lote de productos: ' . count($items));
                try {
                    foreach ($items as $psCombinationUnicos) {

                        $product = $psCombinationUnicos->id_product_attribute
                            ? $psCombinationUnicos->productAttribute?->product
                            : $psCombinationUnicos->product;

                        $id_modelo = $psCombinationUnicos->id_product_attribute
                            ? $psCombinationUnicos->productAttribute?->product->import->id_modelo
                            : $psCombinationUnicos->product->import->id_modelo;

                        Log::info('Procesando el productos: ' . $product->id_product);

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
                            [
                                'prestashop_id' => $psCombinationUnicos->getBaseProductId(),
                                'id_modelo'     => $id_modelo
                            ],
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

                        $stock = $psCombinationUnicos->id_product_attribute
                            ? $psCombinationUnicos->productAttribute?->stocks?->quantity
                            : $product->stocks?->quantity;

                        $pr = ProductReference::updateOrCreate(
                            [
                                'reference'  => $reference,
                                'product_id' => $comparatorProduct->id,
                            ],
                            [
                                'combination_id'         => $psCombinationUnicos->id_product_attribute ?? null,
                                'attribute_id'           => $psCombinationUnicos->id_product_attribute ?? null,
                                'stock'                  => $stock,
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

                        // Procesamos todos los idiomas del producto
                        foreach ($product->langs ?? [] as $lang) {

                            $localLangs = Lang::byIsoCodes([$lang->lang->iso_code])->get()->keyBy('iso_code');
                            $localLang = $localLangs->get($lang->lang->iso_code);

                            if (!$localLang) {
                                continue;
                            }

                            // Log::info('Procesando: ' . $localLang->id . ' - ' . $reference . ' - ' . $stock);

                            ProductLang::updateOrCreate(
                                [
                                    'product_id' => $comparatorProduct->id,
                                    'lang_id'    => $localLang->id,
                                ],
                                [
                                    'title' => $lang->name,
                                    'url'   => $lang->url,
                                    'img'   => $product?->getImageUrl($localLang->id),
                                    // 'stock' => $stock,
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
                            Log::info('Procesando: ' . $localLang->id . ' - ' . $reference . ' - ' . $stock . ' - ' . $finalPriceWithIVA);
                            $atributos = $psCombinationUnicos->id_product_attribute
                                ? $psCombinationUnicos->productAttribute?->atributosString($localLang->id)
                                : null;

                            $available = self::isBlocked(
                                $product?->id_product,
                                $localLang->id
                            );

                            $shippingImporte = ProductPortes::getImporte($reference, $lang->lang->iso_code) ?? 0;

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
                    }
                } catch (Throwable $e) {
                    Log::error('Error during product sync chunk: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            });
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
