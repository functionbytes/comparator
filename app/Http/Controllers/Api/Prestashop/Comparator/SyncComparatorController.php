<?php

namespace App\Http\Controllers\Api\Prestashop\Comparator;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Jobs\Comparator\ProcessComparatorData;
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
use Illuminate\Support\Facades\Http;
use MongoDB\BSON\UTCDateTime;
use Throwable; // correcto
use App\Models\Provider;

use App\Models\Competitor;
use Illuminate\Support\Str;

use App\Models\ProductReferenceLang;


class SyncComparatorController extends Controller
{

    // === Estilos "legacy" (como en Excel) ===
    private const STYLE_CABECERA                    = 'CABECERA';
    private const STYLE_NUMERO                      = 'NUMERO';
    private const STYLE_TEXTO                       = 'TEXTO';
    private const STYLE_NUMERO_PROPUESTO            = 'NUMERO_PROPUESTO';
    private const STYLE_NUMERO_SUGERIDO             = 'NUMERO_SUGERIDO';
    private const STYLE_NUMERO_OLD                  = 'NUMERO_OLD';
    private const STYLE_NUMERO_MIN_PRICE            = 'NUMERO_MIN_PRICE';
    private const STYLE_NUMERO_SIN_STOCK            = 'NUMERO_SIN_STOCK';
    private const STYLE_NUMERO_ALERTA               = 'NUMERO_ALERTA';
    private const STYLE_NUMERO_OCULTO               = 'NUMERO_OCULTO';
    private const STYLE_NUMERO_MIN_PRICE_SIN_STOCK  = 'NUMERO_MIN_PRICE_SIN_STOCK';
    private const STYLE_NUMERO_PERC                 = 'NUMERO_PERC';
    private const STYLE_NUMERO_PERC_OLD             = 'NUMERO_PERC_OLD';
    private const STYLE_NUMERO_PERC_NEW             = 'NUMERO_PERC_NEW';
    private const STYLE_NUMERO_MODIFICABLE          = 'NUMERO_MODIFICABLE';

    // === Constantes legacy traídas al Controller ===
    private const ETIQUETAS_CONTROL_STOCK = 'NAVIDAD22, OUTLET, PROMO_ARMAS_OCTUBRE_2011, ESPECIAL_ROPA_GOLF-NOV_11, LIQUIDACION_ESQUI_ENERO_12, LIQUIDACION_BERETTA_2020, LB21, LIQUIDACION GOLF EN TIENDA, CONTROL_STOCK_WEB, CSW, NAV23, 48H';
    private const ETIQUETAS_CONTROL_STOCK_0 = 'STOCK1';
    private const ETIQUETA_OCULTO_WEB = 'OCWEB';

    /**
     * Ejecuta tu pipeline y guarda en Mongo.
     *
     * Uso:
     *   php artisan results:store ES
     */
    protected $signature = 'results:store {countryIso : ISO del país, p.ej. ES}';

    protected $description = 'Procesa resultados y los almacena en MongoDB (processed_items).';


    public function syncAll()
    {
        $configs_csv = [
            [
                'exportId'   => '1487953412216',
                'user'       => 'a-alvarez.com',
                'pass'       => 'Rt0v7aFL',
                'countryIso' => 'ES',
                'export'     => 'export'
            ],
            [
                'exportId'   => '1520926326905',
                'user'       => 'a-alvarez_pt',
                'pass'       => 'C211ODao',
                'countryIso' => 'PT',
                'export'     => 'export'
            ],
            [
                'exportId'   => '1716831087872',
                'user'       => 'a-alvarez_fr',
                'pass'       => 'ztnN5xvk',
                'countryIso' => 'FR',
                'export'     => 'export'
            ],
            [
                'exportId'   => '1738002380108',
                'user'       => 'a-alvarez_de',
                'pass'       => 'ZJfeyNOi',
                'countryIso' => 'DE',
                'export'     => 'Default_Export'
            ],
            [
                'exportId'   => '1745997094056',
                'user'       => 'a-alvarez_it',
                'pass'       => 'lWhvhMl8',
                'countryIso' => 'IT',
                'export'     => 'export'
            ],
        ];

        $configs_Custom = [[
            'exportId'   => '3127521780550338074',
            'baseFileName'       => 'Completo',
            'apiKey'       => 'hmX8fzl2586SsCBmkJAnw41G2XXo7poQ2KKqiXtk',
            'countryIso' => 'ES',
        ]];

        $results = [];

        foreach ($configs_csv as $config) {
            $results[] = $this->descargarMinderestExportCsv(
                $config['exportId'],
                $config['export'],
                $config['countryIso'],
                $config['user'],
                $config['pass'],
            );
        }

        return response()->json($results);
    }

    /**
     * Descarga un CSV de Minderest y lo guarda en storage/app/{Y-m-d}/minderest_{countryIso}_{His}.csv
     *
     * @param string $exportId
     * @param string $export
     * @param string $countryIso
     * @param string $user
     * @param string $pass
     * @return array{status:string,message:string,path?:string}
     */
    function descargarMinderestExportCsv(
        string $exportId,
        string $export,
        string $countryIso,
        string $user,
        string $pass
    ): array {
        $disk = 'public'; // <- usar el disk público
        $url  = "https://www.minderest.com/export/{$exportId}/{$export}.csv";

        $now        = Carbon::now();
        $folderName = $now->format('Y-m-d');
        $timeStamp  = $now->format('His');
        $fileName   = "minderest_{$countryIso}_{$timeStamp}.csv";
        $fullPath   = "{$folderName}/{$fileName}";

        // Permitir 0777 (anula umask del proceso)
        $oldUmask = umask(0);

        if (!Storage::disk($disk)->exists($folderName)) {
            Storage::disk($disk)->makeDirectory($folderName);
            @chmod(Storage::disk($disk)->path($folderName), 0777);
        }

        $sinkPath = Storage::disk($disk)->path($fullPath);

        try {
            $response = Http::withBasicAuth($user, $pass)
                ->timeout(120)
                ->retry(2, 1000)
                ->withOptions(['sink' => $sinkPath])
                ->get($url);

            if ($response->successful()) {
                if (is_file($sinkPath) && filesize($sinkPath) > 0) {
                    // visibilidad pública (Flysystem) + permisos del SO
                    @Storage::disk($disk)->setVisibility($fullPath, 'public');
                    @chmod($sinkPath, 0777);

                    $publicUrl = Storage::disk($disk)->url($fullPath);

                    umask($oldUmask);
                    return ['status' => 'ok', 'message' => "CSV descargado correctamente ({$countryIso}).", 'countryiso' => $countryIso, 'path' => $fullPath,];
                }

                if (is_file($sinkPath)) {
                    @unlink($sinkPath);
                }
                umask($oldUmask);
                return ['status' => 'error', 'message' => "El archivo para {$countryIso} está vacío."];
            }

            if (is_file($sinkPath) && filesize($sinkPath) === 0) {
                @unlink($sinkPath);
            }
            umask($oldUmask);
            return ['status' => 'error', 'message' => "Error {$response->status()} al descargar CSV para {$countryIso}."];
        } catch (\Throwable $e) {
            if (is_file($sinkPath) && filesize($sinkPath) === 0) {
                @unlink($sinkPath);
            }
            umask($oldUmask);
            return ['status' => 'error', 'message' => "Excepción en {$countryIso}: " . $e->getMessage()];
        }
    }
    /**
     * Descarga un CSV desde el endpoint "custom-export" de Minderest y lo guarda en storage/app/{Y-m-d}/minderest_custom_{countryIso}_{His}.csv
     *
     * @param string $exportId
     * @param string $baseFileName
     * @param string $countryIso
     * @param string $apiKey
     * @return array{status:string,message:string,path?:string,body?:string}
     */
    function descargarMinderestCustomExportCsv(
        string $exportId,
        string $baseFileName,
        string $countryIso,
        string $apiKey
    ): array {
        $url           = "https://app.minderest.com/custom-export/{$exportId}/{$baseFileName}.csv";
        $now           = Carbon::now(); // Zona horaria según config/app.timezone, p.e. Europe/Madrid

        // 2. Carpeta y nombre de fichero dinámicos
        $folderName    = $now->format('Y-m-d');                  // p.e. "2025-07-24"
        $timeStamp     = $now->format('His');                    // p.e. "153045" (15:30:45)
        $fileName      = "minderest_{$countryIso}_{$timeStamp}.csv"; // p.e. "minderest_ES_153045.csv"
        $fullPath      = "{$folderName}/{$fileName}";            // p.e. "2025-07-24/minderest_ES_153045.csv"

        // 3. Asegurar que existe la carpeta
        if (! Storage::exists($folderName)) {
            Storage::makeDirectory($folderName);
        }

        // 4. Petición con cabecera x‑api‑key
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
        ])->timeout(60)  // opcional: ajusta el timeout
            ->get($url);

        // 5. Comprobar que la descarga fue exitosa
        if ($response->successful()) {
            // Guardar el cuerpo en disco
            Storage::put($fullPath, $response->body());

            return response()->json([
                'status'  => 'ok',
                'message' => "CSV descargado correctamente.",
                'path'    => $fullPath,
            ]);
        }

        // 6. Error
        return response()->json([
            'status'  => 'error',
            'message' => "Error al descargar CSV: HTTP {$response->status()}",
            'body'    => $response->body(),
        ], 500);
    }

    public function jobs()
    {
        ProcessComparatorData::dispatch(); // opcional: cola dedicada
        return response()->json(['status' => 'queued']);
    }

    public function generarResultadosComparadorDesdeCsv(string $countryIso)
    {
        @ini_set('max_execution_time', '600');
        @ini_set('memory_limit', '2048M');

        $countryIso = strtoupper(trim($countryIso));
        if ($countryIso === '') {
            Log::error('countryIso es obligatorio.');
            return response()->json(['status' => 'error'], 500);
        }

        try {
            // 1) Construir resultados (tu lógica interna)
            $results = $this->buildResults($countryIso);

            if (!is_array($results)) {
                Log::error('La función buildResults() no devolvió un array.');
                return response()->json(['status' => 'error'], 500);
            }

            Log::info('Resultados construidos: ' . number_format(count($results)));

            // 2) Conexión Mongo
            /** @var \MongoDB\Database $db */
            $db = DB::connection('mongodb')->getMongoDB();
            $collection = $db->selectCollection('processed_items');

            // (Opcional pero recomendado) Migración de índices ya resuelta previamente...
            // Si ya la tienes estable, puedes dejar sólo los createIndex que uses.

            // 3) Persistencia masiva
            $now = new UTCDateTime((int) now('UTC')->valueOf());

            $ops = [];
            $countUpserts = 0;
            $countInserts = 0;

            foreach ($results as $row) {
                if (!is_array($row)) continue;

                // sport = category_id (obligatorio)
                $sport = $row['category_id'] ?? null;
                if ($sport === null) continue;

                // lang = countryIso
                $lang = $countryIso;

                // Sanitizamos el payload COMPLETO a UTF-8 válido (recursivo)
                $sanitizedPayload = $this->sanitizeMongoDocument($row);

                // Extraemos y saneamos el sourceId (si existe)
                $rawSourceId = $row['Referencia'] ?? null;
                $sourceId = null;
                if (!is_null($rawSourceId)) {
                    $sourceId = trim($this->toUtf8String((string) $rawSourceId));
                    if ($sourceId === '') $sourceId = null;
                }

                // Documento base (ya con payload sanitizado)
                $doc = [
                    'sport'            => $sport,
                    'lang'             => $lang,
                    'date'             => $now,
                    'payload'          => $sanitizedPayload,
                    'cambio_de_precio' => 0, // nuevo campo por defecto
                ];

                if ($sourceId !== null) {
                    $doc['sourceId'] = $sourceId;

                    // upsert por clave (sport, lang, sourceId)
                    $ops[] = [
                        'updateOne' => [
                            ['sport' => $sport, 'lang' => $lang, 'sourceId' => $sourceId],
                            ['$set' => $doc],
                            ['upsert' => true],
                        ],
                    ];
                    $countUpserts++;
                } else {
                    // NO incluir sourceId para que quede fuera del índice único parcial/sparse
                    $ops[] = ['insertOne' => [$doc]];
                    $countInserts++;
                }

                // Ejecuta por lotes para controlar memoria/tiempo
                if (count($ops) >= 1000) {
                    try {
                        $collection->bulkWrite($ops, ['ordered' => false]);
                    } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
                        Log::error('Error en bulkWrite (lote intermedio): ' . $e->getMessage());
                    }
                    $ops = [];
                }
            }

            if (!empty($ops)) {
                $collection->bulkWrite($ops, ['ordered' => false]);
            }

            Log::info("Upserts (con sourceId): {$countUpserts} | Inserts (sin sourceId): {$countInserts}");
            Log::info('Proceso completado.');

            return ['status' => 'ok'];
        } catch (Throwable $e) {
            Log::error('Error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Convierte string a UTF-8 válido, normaliza a NFC y elimina control chars.
     */
    private function toUtf8String(string $str): string
    {
        // Si ya es UTF-8 válido, seguimos
        if (!mb_detect_encoding($str, 'UTF-8', true)) {
            // Detecta mejor origen; por lo general CSVs vienen Windows-1252/ISO-8859-1
            $from = mb_detect_encoding($str, ['Windows-1252', 'ISO-8859-1', 'ISO-8859-15', 'UTF-8'], true) ?: 'Windows-1252';
            $str = mb_convert_encoding($str, 'UTF-8', $from);
        }

        // Quita caracteres de control (excepto tab, LF, CR si quieres mantenerlos)
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str) ?? $str;

        // Normaliza a NFC si intl está disponible
        if (function_exists('normalizer_normalize')) {
            $norm = normalizer_normalize($str, \Normalizer::FORM_C);
            if ($norm !== false) {
                $str = $norm;
            }
        }

        // Salvaguarda final: elimina bytes inválidos
        $out = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
        if ($out !== false) {
            $str = $out;
        }

        return $str;
    }

    /**
     * Asegura que TODAS las claves/valores del documento son válidos para Mongo:
     * - strings en UTF-8
     * - sin control chars
     * - claves sin '.' ni comenzando con '$'
     */
    private function sanitizeMongoDocument($data)
    {
        if (is_array($data)) {
            $out = [];
            foreach ($data as $k => $v) {
                $nk = is_string($k) ? $this->sanitizeMongoKey($k) : $k;
                $out[$nk] = $this->sanitizeMongoDocument($v);
            }
            return $out;
        }

        if (is_string($data)) {
            return $this->toUtf8String($data);
        }

        if ($data instanceof \Stringable) {
            return $this->toUtf8String((string) $data);
        }

        // Números, bool, null, etc. se devuelven tal cual
        return $data;
    }

    /**
     * Asegura claves válidas: UTF-8, sin '.' y sin empezar por '$'
     */
    private function sanitizeMongoKey(string $key): string
    {
        $key = $this->toUtf8String($key);
        // Reemplaza '.' por '·' (elige el que prefieras)
        $key = str_replace('.', '·', $key);
        // Evita que empiece por '$'
        if (isset($key[0]) && $key[0] === '$') {
            $key = '﹩' . substr($key, 1); // U+FE69 Small Dollar Sign
        }
        return $key;
    }





    private function buildResults(string $countryIso): array
    {
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '2048M');
        $countryIso = strtoupper(trim($countryIso));

        // —— cierres para explicación/normalización ——
        $nk = fn(string $k): string => $this->normalizeKey($k);
        $expInit  = fn(?array $exp = null): array => $exp ?? [];
        $expPush  = function (array &$exp, string $colKey, string $line): void {
            if (!isset($exp[$colKey])) $exp[$colKey] = [];
            $exp[$colKey][] = $line;
        };

        // IVA desde BD (fallback 0.21)
        $vat = $this->getVatByIso($countryIso);

        // lang_id para ProductLang / ProductReferenceLang
        $lang = Lang::whereRaw('UPPER(iso_code) = ?', [$countryIso])->first();
        $langId = $lang?->id ?? 1;

        // CSV más reciente + agrupado por referencia
        [$csvGroups] = $this->parseCsvLatest($countryIso);

        // Detectar columnas dinámicas disponibles en todo el CSV
        $cols = $this->collectCompetitorColumns($csvGroups, $countryIso);

        // Cargar productos por referencia
        $refs = array_keys($csvGroups);
        $dbProducts = ProductReference::whereIn('reference', $refs)->get()->keyBy('reference');

        // --- NUEVO: prefetchear providers por code (evita N+1) ---
        $providerCodes = $dbProducts
            ->pluck('codigo_proveedor')
            ->filter(fn($v) => $v !== null && $v !== '')
            ->unique()
            ->values();

        $providers = Provider::query()
            ->whereIn('code', $providerCodes)
            ->get(['code', 'title']);

        // Mapa exacto: code (tal cual) -> title
        $providersMapExact = $providers->pluck('title', 'code');

        // Mapa normalizado (fallback): rtrim+lower
        $providersMapNorm = [];
        foreach ($providers as $p) {
            $providersMapNorm[mb_strtolower(rtrim((string)$p->code))] = $p->title;
        }
        $normKey = static function (?string $s): string {
            return mb_strtolower(rtrim((string)$s));
        };

        $results = [];

        foreach ($csvGroups as $ref => $competitorsRows) {
            // Si no hay producto en BD
            if (!isset($dbProducts[$ref])) {
                continue;
            }

            /** @var ProductReference $prod */
            $prod = $dbProducts[$ref];

            // Filtros de visibilidad/estado/externas (legacy)
            $visibleWeb    = $this->isVisibleWeb($prod) ? 'SI' : 'NO';
            $estadoGestion = $this->estadoGestion($prod);
            $estadoFijo    = $this->estadoFijo($prod);

            // Nombre de producto (ProductLang) + características opcionales
            $productLang    = ProductLang::where('product_id', $prod->product_id)->where('lang_id', $langId)->first();
            $productRefLang = ProductReferenceLang::where('reference_id', $prod->id)->where('lang_id', $langId)->first();

            // Portes propios (si conPortes)
            $conPortes   = (bool)($productRefLang->portes ?? false);
            $titulo      = $productLang?->title ?? '';
            $char        = $productRefLang->characteristics ?? '';
            $nombreCompleto = trim($titulo . ($char ? " ($char)" : ''));

            // PVP producto y derivados base
            $pvpProducto = (float) $productRefLang->price;
            $precioSinIva   = $pvpProducto > 0 ? ($pvpProducto / (1 + $vat)) : 0.0;
            $costoProveedor = (float) ($prod->precio_costo_proveedor ?? 0.0);
            $rawTarifa = $prod->tarifa_proveedor;
            $costoProveedorNuevo = ($rawTarifa !== null && (float)$rawTarifa > 0)
                ? (float)$rawTarifa
                : null;

            $margenEuActual  = $precioSinIva - $costoProveedor;
            $margenPctActual = $precioSinIva > 0 ? ($margenEuActual / $precioSinIva) : 0.0;

            // —— recoger mínimos/efectivos de competidores (legacy rules) ——
            [$competitorsOut, $minAny, $minWithStock, $minNoStock] =
                $this->buildCompetitorsEffective($competitorsRows, $conPortes);

            // % diferencia como legacy
            $diffPctVsMpc = $this->diffPctFormula($pvpProducto, $minWithStock);

            // Sugerencia (clon legacy) — ahora con $vatFactor para trazabilidad correcta
            $vatFactor = 1 + $vat;
            $sug = $this->buildSuggestion(
                $pvpProducto,
                $costoProveedor,
                $minWithStock,
                $prod->product->category_id,  // ⚠️ verifica si realmente querías usar shopId
                $vatFactor
            );

            // // Externo (formato legacy)
            // $externo = (($prod->externo ?? 0) == 0) ? 'NO' : 'SI';
            // if ($externo === 'SI') {
            //     $externo .= ((int)($prod->externo_disponibilidad ?? 0) == 0) ? ' (No Disponible)' : ' (Disponible)';
            // }

            // —— contenedor de explicaciones por columna normalizada ——
            $exp = $expInit();

            // aliases normalizados de columnas que vamos explicando
            $kRef               = $nk('Referencia');
            $kNombre            = $nk('Nombre');
            $kPrecioConIva      = $nk('Precio (con IVA)');
            $kPrecioSinIva      = $nk('Precio (sin IVA)');
            $kCosto             = $nk('Precio costo');
            $kMargenEur         = $nk('Margen (€)');
            $kMargenPct         = $nk('Margen (%)');
            $kMejorConIva       = $nk('Mejor precio (con IVA)');
            $kDiffPct           = $nk('Diferencia (%)');

            $kSugPrecio         = $nk('Sugerencia Precio');
            $kSugMargenEur      = $nk('Sugerencia Margen(€)');
            $kSugMargenPct      = $nk('Sugerencia Margen(%)');

            $kNuevoPrecioIva    = $nk('Nuevo precio (con IVA)');
            $kNuevoPrecioNoIva  = $nk('Nuevo precio (sin IVA)');
            $kNuevoMargenEur    = $nk('Margen nuevo (€)');
            $kNuevoMargenPct    = $nk('Margen nuevo (%)');

            // === Proveedor (nombre desde tabla providers) ===
            $provCodeRaw = $prod->codigo_proveedor ?? '';

            $provName = $providersMapExact[$provCodeRaw] ?? ($providersMapNorm[$normKey($provCodeRaw)] ?? ''); // fallback si hay diferencias de espacios/case

            // —— Row base (sin normalizar) ——
            $row = [
                'category_id'            => $prod->product->category_id,
                'countryIso'             => $countryIso,
                'Referencia'             => (string) $ref,
                'Nombre'                 => $nombreCompleto,
                'Estado gestión'         => $estadoGestion,
                'Precio Fijo'            => $estadoFijo,
                'Etiqueta'               => $prod->tags,
                'Visible web mas portes' => $visibleWeb,
                // 'Externo'                => $externo,

                'Precio (con IVA)'       => round($pvpProducto, 2),

                // === Sugerencia (clon legacy) ===
                'Sugerencia Precio'      => $sug->haySugerencia ? round($sug->base, 2) : '',
                'Sugerencia Margen(€)'   => ($sug->haySugerencia && $sug->margin_val !== '') ? round($sug->margin_val, 2) : '',
                'Sugerencia Margen(%)'   => ($sug->haySugerencia && $sug->margin !== '') ? $this->pct($sug->margin) : '',

                'Mejor precio (con IVA)' => is_null($minWithStock) ? '' : round($minWithStock, 2),
                'Diferencia (%)'         => is_null($diffPctVsMpc) ? '' : $this->pct($diffPctVsMpc),

                // === Coste y márgen actual ===
                'Precio costo'           => round($costoProveedor, 2),
                'Precio (sin IVA)'       => round($precioSinIva, 2),
                'Margen (€)'             => round($margenEuActual, 2),
                'Margen (%)'             => $this->pct($margenPctActual),

                // === Nuevo coste/margen (si hay tarifa proveedor) ===
                'Precio costo nuevo'     => !is_null($costoProveedorNuevo) ? round($costoProveedorNuevo, 2) : '',
                'Margen nuevo (€)'       => !is_null($costoProveedorNuevo) ? round(($precioSinIva - $costoProveedorNuevo), 2) : '',
                'Margen nuevo (%)'       => !is_null($costoProveedorNuevo) ? $this->pct(
                    $precioSinIva > 0 ? (($precioSinIva - $costoProveedorNuevo) / $precioSinIva) : null
                ) : '',

                // === Proveedor y código ===
                // 'Proveedor por defecto'  => $prod->codigo_proveedor ?? '',
                'Proveedor por defecto'  => $provName,
            ];

            // URL de nuestro PVP (para el front, renderer con link)
            $row['precio_con_iva_url'] = $productRefLang->url ?? '';

            // —— Explicaciones base ——
            // Precio (con IVA) directo
            // $expPush($exp, $kPrecioConIva, sprintf('PVP (con IVA) del producto: %0.2f', round($pvpProducto, 2)));

            // Precio (sin IVA)
            $expPush($exp, $kPrecioSinIva, sprintf(
                'Precio (sin IVA) = %0.2f / (1 + %s) = %0.2f',
                $pvpProducto,
                number_format($vat, 2, ',', '.'),
                $precioSinIva
            ));

            // Coste proveedor
            $expPush($exp, $kCosto, sprintf('Coste proveedor actual = %0.2f', $costoProveedor));

            // Márgenes actuales
            $expPush($exp, $kMargenEur, sprintf(
                'Margen (€) = %0.2f - %0.2f = %0.2f',
                $precioSinIva,
                $costoProveedor,
                $margenEuActual
            ));
            $expPush($exp, $kMargenPct, sprintf(
                'Margen (%%) = (%0.2f - %0.2f) / %0.2f = %0.2f%%',
                $precioSinIva,
                $costoProveedor,
                $precioSinIva,
                $margenPctActual * 100
            ));

            // Mínimos de competidores (con stock / sin stock)
            if (!is_null($minWithStock)) {
                $expPush($exp, $kMejorConIva, sprintf(
                    'Mínimo competidor con stock = %0.2f',
                    $minWithStock
                ));
            } else {
                $expPush($exp, $kMejorConIva, 'No hay competidores con stock válidos en CSV.');
            }

            // Diferencia (%) con fórmula bifurcada
            if (!is_null($diffPctVsMpc) && !is_null($minWithStock)) {
                if ($pvpProducto < $minWithStock) {
                    $expPush($exp, $kDiffPct, sprintf(
                        'Diferencia (%%) = (%0.2f / %0.2f) - 1 = %0.2f%% (somos más baratos)',
                        $minWithStock,
                        $pvpProducto,
                        $diffPctVsMpc * 100
                    ));
                } else {
                    $expPush($exp, $kDiffPct, sprintf(
                        'Diferencia (%%) = -(%0.2f / %0.2f) + 1 = %0.2f%% (somos más caros)',
                        $pvpProducto,
                        $minWithStock,
                        $diffPctVsMpc * 100
                    ));
                }
            }

            // Sugerencia (explicaciones detalladas)
            $expPush(
                $exp,
                $kSugPrecio,
                $sug->haySugerencia
                    ? sprintf('Precio sugerido (con IVA) = %0.2f - ', $sug->base)
                    : 'Sin sugerencia'
            );

            // Traza detallada de decisiones (rama y pasos)
            if (!empty($sug->branch)) {
                $expPush($exp, $kSugPrecio, 'Rama: ' . $sug->branch);
            }
            if (is_array($sug->trace) && count($sug->trace)) {
                foreach ($sug->trace as $line) {
                    $expPush($exp, $kSugPrecio, $line);
                }
            }

            if ($sug->haySugerencia && $sug->margin !== '') {
                $expPush($exp, $kSugMargenPct, sprintf('Margen con sugerencia = %0.2f%%', $sug->margin * 100));
            }
            if ($sug->haySugerencia && $sug->margin_val !== '') {
                $expPush($exp, $kSugMargenEur, sprintf('Margen (€) con sugerencia = %0.2f', $sug->margin_val));
            }

            // Coste nuevo / márgenes nuevos (si hay tarifa)
            if (!is_null($costoProveedorNuevo)) {
                $expPush($exp, $nk('Precio costo nuevo'), sprintf('Tarifa proveedor aplicada = %0.2f', $costoProveedorNuevo));
                $expPush($exp, $kNuevoPrecioNoIva, sprintf(
                    'Nuevo precio (sin IVA) = %0.2f / (1 + %s) = %0.2f',
                    $pvpProducto,
                    number_format($vat, 2, ',', '.'),
                    $precioSinIva
                ));
                $expPush($exp, $kNuevoMargenEur, sprintf(
                    'Nuevo margen (€) = %0.2f - %0.2f = %0.2f',
                    $precioSinIva,
                    $costoProveedorNuevo,
                    $precioSinIva - $costoProveedorNuevo
                ));
                if ($precioSinIva > 0) {
                    $expPush($exp, $kNuevoMargenPct, sprintf(
                        'Nuevo margen (%%) = (%0.2f - %0.2f) / %0.2f = %0.2f%%',
                        $precioSinIva,
                        $costoProveedorNuevo,
                        $precioSinIva,
                        (($precioSinIva - $costoProveedorNuevo) / $precioSinIva) * 100
                    ));
                }
            }

            // Estilo para nuestro PVP (leído por el front)
            $row['precio_con_iva_style'] = $this->styleForOurPvp($pvpProducto, $minWithStock, $minNoStock);

            // 1) Genéricos: mismas columnas para TODAS las filas
            $row = array_merge(
                $row,
                $this->buildGenericCompetitorCells($competitorsRows, $cols['generic'], $minWithStock, $pvpProducto, $pvpProducto)
            );

            // 2) Amazon (si existe en el CSV global)
            if ($cols['has_amazon']) {
                $row = array_merge(
                    $row,
                    $this->buildAmazonCells($competitorsRows, $minWithStock, $pvpProducto, $pvpProducto)
                );
                // pequeñas notas generales
                $expPush($exp, $nk('amazon_con_portes'),     'Se considera price + shipping con stock > 0.');
                $expPush($exp, $nk('amazon_sin_portes'),     'Se considera price con stock > 0.');
            }

            // 3) Decathlon (si existe)
            if ($cols['has_decathlon']) {
                $row = array_merge(
                    $row,
                    $this->buildDecathlonCells($competitorsRows, $conPortes, $minWithStock, $pvpProducto, $pvpProducto)
                );
                $expPush($exp, $nk('decathlon'), 'Con Portes (ajustado por stock > 0).');
            }

            // 4) Google (si existe)
            if ($cols['has_google']) {
                $row = array_merge(
                    $row,
                    $this->buildGoogleCells($competitorsRows, $conPortes, $minWithStock, $pvpProducto, $pvpProducto)
                );
                $expPush($exp, $nk('google_con_portes'), 'Con Portes: depende de “conPortes” del producto (price o price+shipping).');
                $expPush($exp, $nk('google_sin_portes'), 'Sin Portes: depende de “conPortes” del producto (price - shipping o price).');
            }

            // ===== NUEVO: Competidores visibles (los que “salen” realmente) =====
            $visibleGenerics = 0;
            $visibleList     = []; // para traza

            foreach ($cols['generic'] as $k) {
                $v = $row[$k] ?? '';
                if ($v !== '' && is_numeric($v)) {
                    $visibleGenerics++;
                    $visibleList[] = sprintf('%s=%0.2f', $k, (float)$v);
                }
            }

            // Amazon cuenta como 1 si hay con_portes o sin_portes
            $amazonShown = ((($row['amazon_con_portes'] ?? '') !== '') || (($row['amazon_sin_portes'] ?? '') !== '')) ? 1 : 0;

            // Google cuenta como 1 si hay con_portes o sin_portes
            $googleShown = ((($row['google_con_portes'] ?? '') !== '') || (($row['google_sin_portes'] ?? '') !== '')) ? 1 : 0;

            // Decathlon cuenta como 1 si hay precio
            $decathlonShown = (($row['decathlon'] ?? '') !== '') ? 1 : 0;

            $competidoresVisibles = $visibleGenerics + $amazonShown + $googleShown + $decathlonShown;

            $row['Matchs'] = $competidoresVisibles;
            // 'Matchs'                 => count($competitorsOut),

            $normalized = $this->normalizeRowKeys($row);
            $normalized['_exp'] = $exp;      // ← mantenemos _exp tal cual
            $results[] = $normalized;
        }

        // Ordenado natural por referencia
        usort($results, fn($a, $b) => $this->ordenarPorReferencia((object)$a, (object)$b));

        return $results;
    }


    private function getVatByIso(string $iso): float
    {
        $lang = Lang::whereRaw('UPPER(iso_code) = ?', [strtoupper($iso)])->first();
        return $lang ? max(0, (int)$lang->iva) / 100 : 0.21;
    }

    /* ============================================================
     |                    Lectura CSV + Agrupación
     * ============================================================ */

    /**
     * @return array{0: array $groups, 1: string $dateDir, 2: string $relativePath}
     */
    private function parseCsvLatest(string $countryIso, string $disk = 'public'): array
    {
        $dirs = Storage::disk($disk)->directories('/');
        $latestDir = collect($dirs)
            ->map(fn($d) => ['path' => trim($d, '/'), 'name' => basename(trim($d, '/'))])
            ->filter(fn($it) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $it['name']))
            ->map(function ($it) {
                try {
                    $it['date'] = Carbon::createFromFormat('Y-m-d', $it['name'])->startOfDay();
                    return $it;
                } catch (\Throwable) {
                    return null;
                }
            })->filter()->sortByDesc('date')->first();

        if (!$latestDir) {
            abort(404, 'No hay carpetas con formato fecha (YYYY-MM-DD).');
        }

        $files = Storage::disk($disk)->files($latestDir['path']);
        $pattern = '/^minderest_' . preg_quote($countryIso, '/') . '_([0-9]+)\.csv$/i';

        $match = collect($files)
            ->map(function ($f) use ($pattern) {
                $name = basename($f);
                if (preg_match($pattern, $name, $m)) {
                    return ['path' => $f, 'timestamp' => (int)$m[1]];
                }
                return null;
            })->filter()->sortByDesc('timestamp')->first();

        if (!$match) {
            abort(404, "No se encontró CSV para $countryIso en {$latestDir['path']}");
        }

        $relativePath = $match['path'];
        if (!Storage::disk($disk)->exists($relativePath)) {
            abort(404, "Archivo no encontrado: $relativePath");
        }

        $fullPath = Storage::disk($disk)->path($relativePath);
        $handle   = fopen($fullPath, 'r');
        if ($handle === false) {
            abort(500, 'No se pudo abrir el archivo CSV.');
        }

        // Saltar cabecera
        fgetcsv($handle, 0, ';');

        $groups = [];
        // $nn = 0;
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $ref = trim($row[0] ?? '');
            if ($ref === '') continue;

            $groups[$ref][] = [
                'competitor_name' => trim($row[1] ?? ''),
                'seller_name'     => trim($row[2] ?? ''),
                'price'           => (float) str_replace(',', '.', $row[3] ?? 0),
                'stock'           => (int) ($row[4] ?? 0),
                'product_url'     => trim($row[5] ?? ''),
                'shipping_price'  => (float) str_replace(',', '.', $row[6] ?? 0),
                'updated_at'      => trim($row[7] ?? ''),
            ];
            // $nn++;
            // if ($nn == 350) {
            //     break;
            // }
        }
        fclose($handle);

        // De-dupe exacto por hash
        foreach ($groups as $ref => $rows) {
            $unique = [];
            $seen   = [];
            foreach ($rows as $comp) {
                $hash = md5(serialize($comp));
                if (!isset($seen[$hash])) {
                    $seen[$hash] = true;
                    $unique[] = $comp;
                }
            }
            $groups[$ref] = $unique;
        }

        return [$groups, $latestDir['name'], $relativePath];
    }

    /**
     * Calcula mínimos replicando 1:1 el legacy:
     * - Si stock == 0 -> actualiza SOLO el min de sin stock con price (NO act_price) y continúa.
     * - Si stock > 0 y price == 0 -> ignora para mínimos (pero puedes contarlo en Matchs si quieres).
     * - Si stock > 0 y price > 0 -> actualiza min con act_price (ajuste Amazon si !conPortes).
     *
     * Devuelve: [competitorsOut, minAnyWithStock, minWithStock, minNoStock]
     * Nota: en el legacy min_price y min_precio_competidor son equivalentes (ambos con stock).
     */
    private function buildCompetitorsEffective(array $rows, bool $conPortes): array
    {
        $out = [];
        $minWithStock = null;   // min_precio_competidor_con_stock
        $minNoStock   = null;   // min_precio_competidor_sin_stock
        $minAnyWith   = null;   // equivalente a min_price en legacy (también con stock)

        foreach ($rows as $r) {

            // Normaliza
            $price    = isset($r['price']) ? round((float)$r['price'], 2) : 0.0;
            $shipping = isset($r['shipping_price']) ? (float)$r['shipping_price'] : 0.0;
            $stock    = (int)($r['stock'] ?? 0);
            $name     = trim((string)($r['competitor_name'] ?? ''));

            // Si NO hay stock: actualiza min sin stock con price "puro" y sigue (NO cuenta para min con stock)
            if ($stock === 0) {
                if ($price > 0) {
                    $minNoStock = is_null($minNoStock) ? $price : min($minNoStock, $price);
                }

                // Igualmente guardamos la fila "out" para ver en UI, pero no afecta min con stock
                $out[] = [
                    'competitor_name' => $name,
                    'seller_name'     => $r['seller_name'] ?? '',
                    'price'           => $price,
                    'shipping_price'  => $shipping,
                    'price_effective' => $price, // informativo
                    'stock'           => $stock,
                    'product_url'     => $r['product_url'] ?? '',
                    'updated_at'      => $r['updated_at'] ?? '',
                    // el formato lo podemos recalcular luego si lo necesitas
                ];
                continue;
            }

            // stock > 0
            // Si price == 0 en legacy NO participa de mínimos
            if ($price <= 0) {
                $out[] = [
                    'competitor_name' => $name,
                    'seller_name'     => $r['seller_name'] ?? '',
                    'price'           => $price,
                    'shipping_price'  => $shipping,
                    'price_effective' => $price, // 0
                    'stock'           => $stock,
                    'product_url'     => $r['product_url'] ?? '',
                    'updated_at'      => $r['updated_at'] ?? '',
                ];
                continue;
            }

            // act_price (solo Amazon suma shipping si NO conPortes)
            $act = $this->isAmazonName($name) && !$conPortes
                ? round($price + max(0, $shipping), 2)
                : $price;

            $out[] = [
                'competitor_name' => $name,
                'seller_name'     => $r['seller_name'] ?? '',
                'price'           => $price,
                'shipping_price'  => $shipping,
                'price_effective' => $act, // este es el que se compara para mínimos con stock
                'stock'           => $stock,
                'product_url'     => $r['product_url'] ?? '',
                'updated_at'      => $r['updated_at'] ?? '',
            ];

            // legacy: ambos mínimos con stock usan act_price
            $minWithStock = is_null($minWithStock) ? $act : min($minWithStock, $act);
            $minAnyWith   = is_null($minAnyWith)   ? $act : min($minAnyWith,   $act);
        }

        return [$out, $minAnyWith, $minWithStock, $minNoStock];
    }

    private function diffPctFormula(?float $pvp, ?float $mpc): ?float
    {
        if ($pvp === null || $mpc === null || $pvp <= 0 || $mpc <= 0) return null;
        if ($pvp < $mpc) return ($mpc / $pvp) - 1.0;
        return (-1.0 * ($pvp / $mpc)) + 1.0;
    }

    /* ============================================================
     |                   Sugerencias (legacy)
     * ============================================================ */

    /**
     * Clon 1:1 del legacy getDataLineaSugerencia, con:
     *  - IVA dinámico vía $vatFactor (si no viene, usa 1 + 21%)
     *  - Condición shop: ($shop == 4)
     *  - Redondeo a céntimos acabados en 9
     *  - Traza detallada de decisiones en ->trace[]
     */
    private function buildSuggestion(
        float $pvp_producto,
        float $coste_proveedor,
        ?float $min_precio_competidor,
        ?int $shop = null,
        ?float $vatFactor = null
    ): object {
        $trace = [];
        $push = function (string $msg) use (&$trace) {
            $trace[] = $msg;
        };

        // IVA
        $ivaFactor = (is_numeric($vatFactor) && $vatFactor > 0) ? $vatFactor : $this->vatFactorFromLang(null);

        // Valores base
        $baseSinIva = $pvp_producto / $ivaFactor;
        $costoIVA   = $ivaFactor * $coste_proveedor; // legado
        $nueva_base = round($pvp_producto, 2);
        $formato    = 'NUMERO';
        $margen_res = '';
        $margen_val = '';
        $haySugerencia = false;
        $branch = 'sin_min_competidor';

        if (!is_null($min_precio_competidor)) {
            $diferencia = round($pvp_producto, 2) - round($min_precio_competidor, 2);

            if ($diferencia < 0) {
                // Somos más baratos que el mínimo de la competencia
                $branch = 'somos_mas_baratos';
                $margen = ($baseSinIva > 0) ? (($baseSinIva - (float)$coste_proveedor) / $baseSinIva) * 100 : null;

                if ($margen !== null && $margen < 35) {
                    // Rama margen < 35
                    if ($pvp_producto < 100 && ($shop == 4)) {
                        // Caso especial tienda==4 y PVP<100
                        $branch .= ' | margen<35 | Depoerte = CAZA & pvp<100';

                        if (($min_precio_competidor <= $nueva_base) || (($min_precio_competidor - $nueva_base) <= 1)) {
                            if (($min_precio_competidor - floor($min_precio_competidor)) > 0.5) {
                                $nueva_base = floor(min($nueva_base, $min_precio_competidor)) + 0.49;
                                $push(sprintf(' => Ajuste por cercanía a competidor %0.2f', $nueva_base));
                            } else {
                                $nueva_base = floor(min($nueva_base, $min_precio_competidor)) - 0.05;
                                $push(sprintf(' => Ajuste por cercanía a competidor %0.2f', $nueva_base));
                            }
                        } else {
                            $nueva_base = floor($nueva_base) - 0.05;
                        }
                    } else {
                        // General: aumentar margen 10% y quedar por debajo del mejor rival
                        $branch .= ' | margen<35 | general';
                        $nueva_base = ($costoIVA * $pvp_producto) / max(0.000001, ($costoIVA - $pvp_producto / 10));
                        $push(sprintf(' => Rama general: Nueva base preliminar = (%0.2f (Proveedor + IVA) * %0.2f) / (%0.2f - %0.2f/10) = %0.2f', $costoIVA, $pvp_producto, $costoIVA, $pvp_producto, $nueva_base));
                        $nueva_base = floor(min($nueva_base, $min_precio_competidor)) - 0.01;
                        $push(sprintf(' => Bajada por debajo del competidor: %0.2f', $nueva_base));
                    }

                    $formato = 'NUMERO_PROPUESTO';
                    $haySugerencia = true;
                } else {
                    $push(' => Margen >= 35% → no se propone cambio.');
                    $nueva_base = round($pvp_producto, 2);
                }

                if (round($nueva_base, 2) != round($pvp_producto, 2)) {
                    $margen_res = 100 * (($nueva_base / $ivaFactor) - $coste_proveedor) / max(0.000001, ($nueva_base / $ivaFactor));
                    $push(sprintf(
                        ' => Margen con sugerencia = 100 * ((%0.2f/%0.4f) - %0.2f) / (%0.2f/%0.4f) = %0.2f%%',
                        $nueva_base,
                        $ivaFactor,
                        $coste_proveedor,
                        $nueva_base,
                        $ivaFactor,
                        $margen_res
                    ));
                }
            } else {
                // Somos más caros o iguales
                $branch = ($diferencia > 0) ? 'somos_mas_caros' : 'igual_al_mejor';
                $nueva_base = floor($min_precio_competidor) - 0.01;
                $push(sprintf(' => Nueva base = %0.2f - 0.01 = %0.2f', $min_precio_competidor, $nueva_base));

                $margen_res = 100 * (($nueva_base / $ivaFactor) - $coste_proveedor) / max(0.000001, ($nueva_base / $ivaFactor));
                $push(sprintf(' => Margen con sugerencia = %0.2f%%', $margen_res));

                if ($diferencia != 0) { // si es exactamente igual, no marcar
                    if ($margen_res >= 10) {
                        $formato = 'NUMERO_PROPUESTO';
                        $push(' => Margen >= 10%');
                    } else {
                        $formato = 'NUMERO_SUGERIDO';
                        $push(' => Margen < 10%');
                    }
                    $haySugerencia = true;
                }
            }
        }

        // Preparar márgenes finales (como en legacy)
        if ($margen_res !== '') {
            $margen     = $margen_res / 100; // fracción
            $margen_val = ($nueva_base / $ivaFactor) - $coste_proveedor;
        } else {
            $margen     = '';
            $margen_val = '';
        }

        // Si no hay cambio o no hay min competidor → sin sugerencia
        if (round($nueva_base, 2) == round($pvp_producto, 2) || empty($min_precio_competidor)) {
            $formato = 'NUMERO';
            $haySugerencia = false;
        }

        // Redondeo a céntimos terminados en 9 (si hace falta)
        $preRound = $nueva_base;
        $ultimo_digito = substr((string)$nueva_base, -1);
        if ($ultimo_digito !== '9') {
            $nueva_base = $this->redondearCentimosANueve($nueva_base);
        }

        return (object)[
            'base'          => round($nueva_base, 2),
            'formato'       => $formato,
            'margin'        => is_numeric($margen) ? round($margen, 4) : '',
            'margin_val'    => is_numeric($margen_val) ? round($margen_val, 2) : '',
            'haySugerencia' => $haySugerencia,
            'origin_data'   => [$pvp_producto, $coste_proveedor, $min_precio_competidor, $shop],
            // Debug extra
            'trace'         => $trace,
            'branch'        => $branch,
            'ivaFactor'     => $ivaFactor,
        ];
    }

    private function redondearCentimosANueve(float $precio): float
    {
        $ent = floor($precio);
        $cent = (int) round(($precio - $ent) * 100);
        $decena = intdiv($cent, 10);
        $nuevoCent = ($decena * 10) + 9;
        if ($nuevoCent > 99) {
            return round($ent + 0.99, 2);
        }
        return round($ent + ($nuevoCent / 100), 2);
    }

    /**
     * Equivalente a bl_Modelos::hideModelByTagSeason()
     * - 01 Abr .. 15 Ago  => ocultar temporada invierno
     * - 01 Oct .. 31 Dic y 01 Ene .. 15 Feb => ocultar temporada verano
     * Devuelve el tag a ocultar o '' si no aplica.
     */
    private function hideModelByTagSeason(): string
    {
        $nowYmd = Carbon::now()->format('Y-m-d');

        $from = fn(string $md) => Carbon::createFromFormat('Y-m-d', date("Y-{$md}"))->format('Y-m-d');

        if ($from('04-01') <= $nowYmd && $nowYmd <= $from('08-15')) {
            return 'TEMPORADA_INVIERNO';
        }
        if (($from('10-01') <= $nowYmd && $nowYmd <= $from('12-31'))
            || ($from('01-01') <= $nowYmd && $nowYmd < $from('02-16'))
        ) {
            return 'TEMPORADA_VERANO';
        }
        return '';
    }

    /** Normaliza y separa tags "A, B ,C" -> ['A','B','C'] en MAYÚSCULAS sin espacios. */
    private function parseTags(?string $csv): array
    {
        if (!$csv) return [];
        return array_values(array_filter(array_map(function ($t) {
            $t = trim($t);
            if ($t === '') return null;
            return mb_strtoupper($t, 'UTF-8');
        }, explode(',', $csv))));
    }

    /** Unión de etiquetas de control de stock (legacy). */
    private function totalEtiquetasControlStock(): array
    {
        return $this->parseTags(self::ETIQUETAS_CONTROL_STOCK . ',' . self::ETIQUETAS_CONTROL_STOCK_0);
    }

    /**
     * Port de isVisibleWeb() legacy a ProductReference.
     * Reglas:
     *  - Si el modelo tiene tag de temporada a ocultar => NO visible
     *  - Si lleva OCWEB => NO visible
     *  - Si modelo_activo y producto_activo false => NO visible (si no existen, asumimos true)
     *  - Si NO hay control de stock => visible
     *  - Si SÍ hay control de stock:
     *        * con (stock_web vacío o >0) y stock_gestion >0  => visible
     *        * si no tienes esos campos, usa $p->stock > 0
     */
    private function isVisibleWeb(ProductReference $p): bool
    {
        // 1) Tags del producto (normalizados)
        $tags = $this->parseTags($p->tags ?? '');

        // 2) Tag de temporada a ocultar
        $tagHide = $this->hideModelByTagSeason(); // '' si no aplica
        if ($tagHide !== '' && in_array(mb_strtoupper($tagHide, 'UTF-8'), $tags, true)) {
            return false;
        }

        // 3) OCULTO_WEB (legacy OCWEB)
        if (in_array(self::ETIQUETA_OCULTO_WEB, $tags, true)) {
            return false;
        }

        // 4) Flags de actividad (si no existen en BD, asumimos true)
        $modeloActivo   = array_key_exists('modelo_activo', $p->getAttributes())   ? (bool)$p->modelo_activo   : true;
        $productoActivo = array_key_exists('producto_activo', $p->getAttributes()) ? (bool)$p->producto_activo : true;
        if (!$modeloActivo || !$productoActivo) {
            return false;
        }

        // 5) ¿Hay control de stock?
        $etiquetasStockControl = $this->totalEtiquetasControlStock();
        $externoDisp = (int)($p->externo_disponibilidad ?? 1); // si no existe, asumimos disponible externo (1)
        $hayEtiquetaControl = count(array_intersect($tags, $etiquetasStockControl)) >= 1;
        $controlStock = ($externoDisp === 0) || $hayEtiquetaControl;

        // 6) Visibilidad final según stock
        if (!$controlStock) {
            return true;
        }

        // Si tienes los campos diferenciados como en legacy:
        $hasStockWeb     = array_key_exists('stock_web', $p->getAttributes());
        $hasStockGestion = array_key_exists('stock_gestion', $p->getAttributes());

        if ($hasStockWeb && $hasStockGestion) {
            $stockWeb = $p->stock_web;
            $stockG   = (int)$p->stock_gestion;
            $stockWebOk = ($stockWeb === '' || $stockWeb === null || (int)$stockWeb > 0);
            return $stockWebOk && $stockG > 0;
        }

        // Fallback: un único campo de stock
        $stock = (int)($p->stock ?? 0);
        return $stock > 0;
    }

    private function estadoGestion(ProductReference $p): string
    {
        $estado = (int)($p->estado_gestion ?? 1);
        if ($estado === 0) return 'Anulado';
        if ($estado === 1) return 'Activo';
        $vendible = ((int)($p->stock ?? 0)) > 0;
        return $vendible ? 'A extinguir' : 'A extinguir (sin stock)';
    }

    private function estadoFijo(ProductReference $p): string
    {
        return (str_contains((string)$p->tags, 'PRECIO_FIJO') ? 'SI' : 'NO');
    }

    /* ============================================================
     |                         Utilidades
     * ============================================================ */

    private static function ordenarPorReferencia(object $a, object $b): int
    {
        return strnatcmp((string)($a->Referencia ?? ''), (string)($b->Referencia ?? ''));
    }

    private function pct($fraction, int $dec = 2): string
    {
        if ($fraction === null || $fraction === '') return '';
        $v = (float)$fraction * 100;
        return number_format($v, $dec, ',', '.') . '%';
    }

    /**
     * Extrae algo parecido a un host aunque el input no sea una URL válida.
     */
    private function extractHostLike(string $s): string
    {
        $s = trim($s);
        $l = strtolower($s);

        // Intentar parse_url si tiene pinta de URL
        if (str_starts_with($l, 'http://') || str_starts_with($l, 'https://') || str_starts_with($l, 'www.')) {
            $host = parse_url($l, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                return $host;
            }
        }

        // Si no, tomar hasta la primera "/" como "host" si contiene un punto
        $first = strtok($l, '/');
        if (is_string($first) && str_contains($first, '.')) {
            return $first;
        }

        // Último recurso: devolver tal cual (minúsculas)
        return $l;
    }

    /**
     * Detecta si un nombre/host/url pertenece a Amazon retail (cualquier país/marketplace).
     * Cubre: amazon.es, amazon.com, amazon.co.uk, amazon.com.mx, marketplace.amazon.xx,
     * amazonmarket.de, etc. Excluye amazonaws.com (AWS).
     */
    private function isAmazonName(string $name): bool
    {
        $host = $this->extractHostLike($name);
        $host = preg_replace('/\s+/', '', $host) ?? '';
        if ($host === '' || str_contains($host, 'amazonaws')) return false;
        $patterns = [
            '/(^|[.\/])amazon(?:[.-][a-z0-9]{2,})+$/i',
            '/(^|[^a-z])marketplace\.amazon\.[a-z0-9.]+$/i',
            '/(^|[^a-z])amazonmarket([^a-z]|$)/i',
            '/(^|[^a-z])amazon([^a-z]|$)/i',
        ];
        foreach ($patterns as $re) if (preg_match($re, $host) === 1) return true;
        return false;
    }

    /** Detecta Google Shopping genérico (todas las variantes) */
    private function isGoogleName(string $name): bool
    {
        $s = strtolower(trim($name));
        // variantes "normalizadas" con underscore (p.ej. claves genéricas)
        if (preg_match('/^google_[a-z]{2,}_(shopping|shopping_market)$/', $s)) {
            return true;
        }

        // extrae algo parecido a host si viene url-ish
        $host = $this->extractHostLike($s); // ya la tienes para Amazon
        // si empieza por google.<tld> y la ruta menciona shopping/_market
        if (str_starts_with($host, 'google.')) {
            if (str_contains($s, '/shopping') || str_contains($s, 'shopping_market')) {
                return true;
            }
        }

        // fallback: contiene "google." y "shopping"
        return (str_contains($s, 'google.') && (str_contains($s, '/shopping') || str_contains($s, 'shopping_market')));
    }

    /** Detecta Decathlon (host o clave normalizada) */
    private function isDecathlonName(string $name): bool
    {
        $s = strtolower(trim($name));
        if (preg_match('/^decathlon(_[a-z]{2})?$/', $s)) return true; // claves normalizadas
        $host = $this->extractHostLike($s);
        return (bool) preg_match('/(^|[.\/])decathlon([._-][a-z]{2})?/i', $host);
    }

    /**
     * Recorre TODO el CSV y decide qué columnas dinámicas habrá:
     *  - 'generic' = lista (ordenada) de competidores "normales"
     *  - flags para Amazon / Google / Decathlon
     */
    private function collectCompetitorColumns(array $csvGroups, $countryIso): array
    {
        $generic = [];
        $hasAmazon = false;
        $hasGoogle = false;
        $hasDecathlon = false;

        foreach ($csvGroups as $ref => $rows) {
            foreach ($rows as $r) {
                $raw = trim((string)($r['competitor_name'] ?? ''));
                if ($raw === '') continue;

                if ($this->isAmazonName($raw)) {
                    $hasAmazon = true;
                    continue;
                }
                if ($this->isGoogleName($raw)) {
                    $hasGoogle = true;
                    continue;
                }
                if ($this->isDecathlonName($raw)) {
                    $hasDecathlon = true;
                    continue;
                }

                // clave genérica basada en dominio / normalización propia
                $key = $this->genericCompetitorKey($raw);
                if ($key !== '') {
                    $generic[$key] = true;
                }
            }
        }

        $genericList = array_keys($generic);
        sort($genericList, SORT_NATURAL | SORT_FLAG_CASE);

        // 👇 Persistimos en BD (idempotente gracias al unique y firstOrCreate)
        $this->upsertGenericCompetitors($genericList, (string) $countryIso);

        return [
            'generic'       => $genericList,
            'has_amazon'    => $hasAmazon,
            'has_google'    => $hasGoogle,
            'has_decathlon' => $hasDecathlon,
        ];
    }


    /**
     * Genera columnas para competidores genéricos:
     * - <key>                         => precio min
     * - <key> VENDEDOR                => texto vendedor
     * - <key> VENDEDOR URL            => url si “tiene portes” (>0) (como definiste)
     * - <key> STYLE                   => special_number (para color)
     */
    private function buildGenericCompetitorCells(
        array $rows,
        array $genericCols,
        ?float $minWithStock = null,
        ?float $minPriceGlobal = null,
        ?float $ourPvp = null
    ): array {
        $out = [];
        // inicializa
        foreach ($genericCols as $k) {
            $out[$k] = '';
            $out[$k . '_vendedor_url'] = '';
            $out[$k . '_style'] = 0;
            // si algún día traes *_vendedor (texto), inicializa aquí también
            // $out[$k . '_vendedor'] = '';
        }

        foreach ($rows as $r) {
            $name = trim((string)($r['competitor_name'] ?? ''));
            if ($name === '') continue;

            // nunca meter reservados aquí
            if ($this->isAmazonName($name) || $this->isGoogleName($name) || $this->isDecathlonName($name)) {
                continue;
            }


            $baseKey = $this->genericCompetitorKey($name);
            if ($baseKey === '' || !in_array($baseKey, $genericCols, true)) {
                continue;
            }

            $price    = isset($r['price']) ? round((float)$r['price'], 2) : 0.0;
            $shipping = isset($r['shipping_price']) ? (float)$r['shipping_price'] : 0.0;
            $stock    = (int)($r['stock'] ?? 0);
            // dump($name,$stock,$price);
            // if ($stock <= 0 || $price <= 0) continue;

            // Para genéricos usamos el precio tal cual (como legacy)
            if ($out[$baseKey] === '' || $price < (float)$out[$baseKey]) {
                $out[$baseKey] = $price;
                $out[$baseKey . '_vendedor_url'] = (string)($r['product_url'] ?? '');
                $tmp = $r;
                $tmp['price'] = $price;
                $out[$baseKey . '_style'] = $this->buildCompetitorStyleCode($tmp, $minWithStock, $minPriceGlobal, $ourPvp);
            }

            // si en el futuro quieres guardar también el texto vendedor:
            // $sellerTxt = (string)($r['seller_name'] ?? '');
            // if ($sellerTxt !== '') { $out[$baseKey . '_vendedor'] = $sellerTxt; }
        }

        return $out;
    }

    /**
     * Amazon: 4 columnas (si existen en el CSV).
     * - con_portes  = min(price + shipping), stock>0, price>0
     * - sin_portes  = min(price),            stock>0, price>0
     * - vendedor/url/style del ganador
     * Si ambos coinciden (mismo precio o misma URL), dejamos solo con_portes.
     */
    private function buildAmazonCells(
        array $rows,
        ?float $minWithStock = null,
        ?float $minPriceGlobal = null,
        ?float $ourPvp = null,
        ?array &$exp = null         // 👈 nuevo (opcional por referencia)
    ): array {
        $exp = $this->expInit($exp);
        $bestCP = null;
        $bestCPseller = '';
        $bestCPurl = '';
        $bestCProw = null;
        $bestSP = null;
        $bestSPseller = '';
        $bestSPurl = '';
        $bestSProw = null;

        foreach ($rows as $r) {
            $name = trim((string)($r['competitor_name'] ?? ''));
            if (!$this->isAmazonName($name)) continue;

            $price    = isset($r['price']) ? round((float)$r['price'], 2) : 0.0;
            $shipping = isset($r['shipping_price']) ? (float)$r['shipping_price'] : 0.0;
            $stock    = (int)($r['stock'] ?? 0);
            if ($stock <= 0 || $price <= 0) continue;

            // Con portes = price + shipping
            $cp = round($price + max(0, $shipping), 2);
            if ($bestCP === null || $cp < $bestCP) {
                $bestCP       = $cp;
                $bestCPseller = (string)($r['seller_name'] ?? '');
                $bestCPurl    = (string)($r['product_url'] ?? '');
                $bestCProw    = $r;
                $bestCProw['price'] = $cp; // para el style code medimos el CP ya sumado
            }

            // al decidir bestCP:
            if ($bestCP !== null) {
                $k = $this->nk('amazon_con_portes');
                $this->expPush($exp, $k, sprintf(
                    'Mín con stock (con portes) = price(%0.2f) + shipping(%0.2f) = %0.2f',
                    $bestCProw['price'] - max(0, $bestCProw['shipping_price'] ?? 0),
                    max(0, $bestCProw['shipping_price'] ?? 0),
                    $bestCP
                ));
                if (!is_null($ourPvp)) {
                    $this->expPush($exp, $k, sprintf('Comparado con nuestro PVP (%0.2f)', $ourPvp));
                }
            }

            // Sin portes = price
            $sp = $price;
            if ($bestSP === null || $sp < $bestSP) {
                $bestSP       = $sp;
                $bestSPseller = (string)($r['seller_name'] ?? '');
                $bestSPurl    = (string)($r['product_url'] ?? '');
                $bestSProw    = $r;
                $bestSProw['price'] = $sp;
            }
        }

        // 🔧 DEDUPE: si “con” y “sin” son iguales (precio o URL), vaciamos “sin_portes”
        if ($bestCP !== null && $bestSP !== null) {
            $samePrice = abs($bestCP - $bestSP) < 0.001;
            $sameUrl   = $bestCPurl !== '' && $bestCPurl === $bestSPurl;
            if ($samePrice || $sameUrl) {
                $bestSP = null;
                $bestSPseller = '';
                $bestSPurl = '';
                $bestSProw = null;
            }
        }

        return [
            'amazon_con_portes'          => $bestCP === null ? '' : round($bestCP, 2),
            'amazon_con_portes_vendedor' => $bestCP === null ? '' : ($bestCPseller !== '' ? $bestCPseller : 'amazon'),
            'amazon_con_portes_url'      => $bestCP === null ? '' : $bestCPurl,
            'amazon_con_portes_style'    => $bestCProw === null ? 0 : $this->buildCompetitorStyleCode($bestCProw, $minWithStock, $minPriceGlobal, $ourPvp),

            'amazon_sin_portes'          => $bestSP === null ? '' : round($bestSP, 2),
            'amazon_sin_portes_vendedor' => $bestSP === null ? '' : ($bestSPseller !== '' ? $bestSPseller : 'amazon'),
            'amazon_sin_portes_url'      => $bestSP === null ? '' : $bestSPurl,
            'amazon_sin_portes_style'    => $bestSProw === null ? 0 : $this->buildCompetitorStyleCode($bestSProw, $minWithStock, $minPriceGlobal, $ourPvp),
        ];
    }

    /**
     * Google: 4 columnas (si existen en el CSV global).
     *  - Con Portes: si $conPortes=true => price; si false => price+shipping
     *  - Sin Portes: si $conPortes=true => price - shipping; si false => price
     *  (stock>0 y price>0)
     *  🔧 DEDUPE: si “con” y “sin” coinciden (precio o URL), se deja solo “con_portes”.
     */
    private function buildGoogleCells(
        array $rows,
        bool $conPortes,
        ?float $minWithStock = null,
        ?float $minPriceGlobal = null,
        ?float $ourPvp = null
    ): array {
        $bestCP = null;
        $bestCPseller = '';
        $bestCPurl = '';
        $bestCProw = null;
        $bestSP = null;
        $bestSPseller = '';
        $bestSPurl = '';
        $bestSProw = null;

        foreach ($rows as $r) {
            $name = trim((string)($r['competitor_name'] ?? ''));
            if (!$this->isGoogleName($name)) continue;

            $price    = isset($r['price']) ? round((float)$r['price'], 2) : 0.0;
            $shipping = isset($r['shipping_price']) ? (float)$r['shipping_price'] : 0.0;
            $stock    = (int)($r['stock'] ?? 0);
            if ($stock <= 0 || $price <= 0) continue;

            $cp = $conPortes ? $price : round($price + max(0, $shipping), 2);
            $sp = $conPortes ? round(max(0, $price - max(0, $shipping)), 2) : $price;

            if ($bestCP === null || $cp < $bestCP) {
                $bestCP       = $cp;
                $bestCPseller = (string)($r['seller_name'] ?? '');
                $bestCPurl    = (string)($r['product_url'] ?? '');
                $bestCProw    = $r;
                $bestCProw['price'] = $cp; // para estilo
            }
            if ($bestSP === null || $sp < $bestSP) {
                $bestSP       = $sp;
                $bestSPseller = (string)($r['seller_name'] ?? '');
                $bestSPurl    = (string)($r['product_url'] ?? '');
                $bestSProw    = $r;
                $bestSProw['price'] = $sp;
            }
        }

        // 🔧 DEDUPE: si coinciden por precio o URL => ocultamos "sin_portes"
        if ($bestCP !== null && $bestSP !== null) {
            $samePrice = abs($bestCP - $bestSP) < 0.001;
            $sameUrl   = $bestCPurl !== '' && $bestCPurl === $bestSPurl;
            if ($samePrice || $sameUrl) {
                $bestSP = null;
                $bestSPseller = '';
                $bestSPurl = '';
                $bestSProw = null;
            }
        }

        return [
            'google_con_portes'          => $bestCP === null ? '' : round($bestCP, 2),
            'google_con_portes_vendedor' => $bestCP === null ? '' : ($bestCPseller !== '' ? $bestCPseller : 'google'),
            'google_con_portes_url'      => $bestCP === null ? '' : $bestCPurl,
            'google_con_portes_style'    => $bestCProw === null ? 0 : $this->buildCompetitorStyleCode($bestCProw, $minWithStock, $minPriceGlobal, $ourPvp),

            'google_sin_portes'          => $bestSP === null ? '' : round($bestSP, 2),
            'google_sin_portes_vendedor' => $bestSP === null ? '' : ($bestSPseller !== '' ? $bestSPseller : 'google'),
            'google_sin_portes_url'      => $bestSP === null ? '' : $bestSPurl,
            'google_sin_portes_style'    => $bestSProw === null ? 0 : $this->buildCompetitorStyleCode($bestSProw, $minWithStock, $minPriceGlobal, $ourPvp),
        ];
    }

    /**
     * Decathlon: 2 columnas (si existe en el CSV).
     *  - Solo “Con Portes”.
     *  - Si ves duplicados en el resultado final, suele ser porque también
     *    se está generando como "genérico". Asegúrate de excluir nombres
     *    que cumplan isDecathlonName() del builder genérico.
     */
    private function buildDecathlonCells(
        array $rows,
        bool $conPortes,
        ?float $minWithStock = null,
        ?float $minPriceGlobal = null,
        ?float $ourPvp = null
    ): array {
        $best = null;
        $seller = '';
        $url = '';
        $bestRow = null;

        foreach ($rows as $r) {
            $name = trim((string)($r['competitor_name'] ?? ''));
            if (!$this->isDecathlonName($name)) continue;

            $price    = isset($r['price']) ? round((float)$r['price'], 2) : 0.0;
            $shipping = isset($r['shipping_price']) ? (float)$r['shipping_price'] : 0.0;
            $stock    = (int)($r['stock'] ?? 0);
            if ($stock <= 0 || $price <= 0) continue;

            $cp = $conPortes ? $price : round($price + max(0, $shipping), 2);
            if ($best === null || $cp < $best) {
                $best   = $cp;
                $seller = (string)($r['seller_name'] ?? '');
                $url    = (string)($r['product_url'] ?? '');
                $bestRow = $r;
                $bestRow['price'] = $cp;
            }
        }

        return [
            'decathlon'          => $best === null ? '' : round($best, 2),
            'decathlon_vendedor' => $best === null ? '' : ($seller !== '' ? $seller : 'decathlon'),
            'decathlon_url'      => $best === null ? '' : $url,
            'decathlon_style'    => $bestRow === null ? 0 : $this->buildCompetitorStyleCode($bestRow, $minWithStock, $minPriceGlobal, $ourPvp),
        ];
    }

    // Convierte "Margen (%)" -> "margen_pct", "Precio (con IVA)" -> "precio_con_iva",
    // "blackrecon.com" -> "blackrecon_com", "armeriamateo.com/es/" -> "armeriamateo_com_es"
    private function normalizeKey(string $key): string
    {
        // 1) transliterar tildes: "gestión" -> "gestion"
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key);

        // 2) to lower
        $ascii = strtolower($ascii);

        // 3) reemplazos semánticos comunes
        $ascii = strtr($ascii, [
            '€' => ' eur ',
            '%' => ' pct ',
            '&' => ' and ',
            '(' => ' ',
            ')' => ' ',
            '/' => ' ',
            '\\' => ' ',
            '-' => ' ',
            '.' => ' ', // dominios a subrayado luego
            "\n" => ' ',
            "\t" => ' ',
        ]);

        // 4) cualquier cosa que no sea [a-z0-9_] => espacio
        $ascii = preg_replace('/[^a-z0-9_ ]+/', ' ', $ascii);

        // 5) espacios a underscores
        $ascii = preg_replace('/[ ]+/', '_', trim($ascii));

        // 6) colapsar múltiples underscores y recortar
        $ascii = preg_replace('/_+/', '_', $ascii);
        $ascii = trim($ascii, '_');

        return $ascii;
    }

    // Normaliza todas las claves del array fila
    private function normalizeRowKeys(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $nk = $this->normalizeKey((string)$k);
            // Si por accidente dos claves distintas colisionan, la última gana.
            $out[$nk] = $v;
        }
        return $out;
    }

    /**
     * Devuelve el nombre base para columnas de competidores genéricos.
     * - Extrae host si viene URL o algo similar
     * - Quita 'www.'
     * - Quita TLDs y deja el SLD (second-level domain), p.ej. "arminse" de "arminse.es"
     * - Normaliza a minúsculas y [a-z0-9_]
     * - Si es Amazon/Google/Decathlon => devuelve '' (no es genérico)
     */
    private function genericCompetitorKey(string $raw): string
    {
        $s = trim(mb_strtolower($raw, 'UTF-8'));
        if ($s === '') return '';

        // excluir reservados
        if ($this->isAmazonName($s) || $this->isGoogleName($s) || $this->isDecathlonName($s)) {
            return '';
        }

        // intenta extraer host "parecido"
        $host = $this->extractHostLike($s);
        if ($host === '') {
            // si no parece dominio, usa el token crudo
            $base = preg_replace('/[^a-z0-9]+/i', '_', $s);
            $base = trim($base, '_');
            return $base;
        }

        // quita www.
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        // toma el SLD: ejemplo sub.dominio.co.uk -> dominio
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            $sld = $parts[count($parts) - 2]; // penúltimo
            $base = $sld;
        } else {
            $base = $parts[0];
        }

        // normaliza a [a-z0-9_]
        $base = preg_replace('/[^a-z0-9]+/i', '_', $base);
        $base = trim($base, '_');

        // seguridad extra: evita reservados por nombre base
        if ($base === '' || preg_match('/^(amazon|google|decathlon)(_|$)/', $base)) {
            return '';
        }

        return $base;
    }

    /**
     * Port del legacy createFormatForCompetitor() para generar el "special_number".
     * $row: fila del CSV (competidor ganador de esa columna)
     * $minCompetitor: mejor precio de la competencia (con stock) en la referencia (con/sin portes según reglas ya aplicadas al cálculo)
     * $minPrice: mínimo general que se compara con nuestro PVP (para diferenciar verde/rojo)
     * $alvarezPrice: nuestro PVP con IVA (para “competidor más barato que nosotros”)
     *
     * Devuelve int "special_number" que el front traducirá a clase/color.
     */
    private function buildCompetitorStyleCode(array $row, ?float $minCompetitor, ?float $minPrice, ?float $alvarezPrice = null): int
    {
        $code = 0;

        $price   = isset($row['price']) ? round((float)$row['price'], 2) : 0.0;
        $stock   = (int)($row['stock'] ?? 0);
        $ship    = isset($row['shipping_price']) ? (float)$row['shipping_price'] : 0.0;
        $updated = (string)($row['updated_at'] ?? '');

        // 1) Sin stock => +1 (tachado)
        if ($stock != 1) {
            $code += 1;
        }

        // 2) Mejor precio de la competencia => +2 y además:
        //    verde si minCompetitor == minPrice (competidor mejor que nosotros), rojo si no (competidor peor)
        if (!is_null($minCompetitor) && round($price, 2) == round($minCompetitor, 2)) {
            $code += 2;
            if (!is_null($minPrice)) {
                if (round($minCompetitor, 2) == round($minPrice, 2)) {
                    // mejor competidor coincide con min general => verde
                    $code += 4;
                } else {
                    // si no coincide, rojo (competidor peor)
                    $code += 8;
                }
            }
        } else {
            // 3) Competidor más barato que nosotros (aunque no sea el mejor) => +20 (verde suave)
            if (!is_null($alvarezPrice) && $price > 0 && round($price, 2) < round($alvarezPrice, 2)) {
                $code += 20;
            }
        }

        // 4) Si stock>0 y NO hay nuestro precio (alvarezPrice null), marcar como “blue” de legacy (>=22)
        if ($code == 0 && $stock > 0 && is_null($alvarezPrice)) {
            $code += 22;
        }

        // 5) Si actualización >24h y competidor tiene stock y hay nuestro precio => +24
        if ($updated !== '' && $stock > 0 && !is_null($alvarezPrice)) {
            // legacy usaba d/m/Y H:i:s; nuestros CSV llevan $row['updated_at'] como string
            // Intentamos parsear en ambos formatos
            $ts = null;
            // d/m/Y H:i:s
            $dt = \DateTime::createFromFormat('d/m/Y H:i:s', $updated);
            if ($dt instanceof \DateTime) {
                $ts = $dt->getTimestamp();
            } else {
                // fallback a strtotime
                $tmp = strtotime($updated);
                if ($tmp !== false) $ts = $tmp;
            }
            if (!is_null($ts)) {
                $diff = time() - $ts;
                if ($diff > 86400) {
                    $code += 24;
                }
            }
        }

        return $code;
    }

    /**
     * Decide el estilo simbólico para nuestro PVP con IVA.
     * - MIN_PRICE si somos el mínimo con stock
     * - MIN_PRICE_SIN_STOCK si solo hay más barato sin stock
     * - ALERTA si hay competidor con stock más barato
     * - NUMERO si no hay competencia
     */
    private function styleForOurPvp(?float $ourPvp, ?float $minCompetitorWithStock, ?float $minCompetitorNoStock): string
    {
        if (is_null($ourPvp)) return self::STYLE_NUMERO;

        if (is_null($minCompetitorWithStock)) {
            // no hay competidor con stock
            if (!is_null($minCompetitorNoStock) && $minCompetitorNoStock < $ourPvp) {
                return self::STYLE_NUMERO_MIN_PRICE_SIN_STOCK;
            }
            return self::STYLE_NUMERO;
        }

        // hay competidor con stock (minCompetitorWithStock)
        if (round($ourPvp, 2) <= round($minCompetitorWithStock, 2)) {
            return self::STYLE_NUMERO_MIN_PRICE;
        }
        return self::STYLE_NUMERO_ALERTA;
    }

    private function normalizeVatRate($vat): float
    {
        // Acepta 21 o 0.21 y devuelve SIEMPRE tasa decimal (0.21, 0.00, etc.)
        if ($vat === null) return 0.21;
        $v = (float) $vat;
        return $v > 1 ? $v / 100.0 : $v;
    }

    private function vatFactorFromLang(?\App\Models\Lang $lang = null): float
    {
        $rate = $this->normalizeVatRate($lang?->iva ?? 21); // 21 por defecto
        // factor = 1 + tasa; nunca 0
        return max(1.0 + $rate, 0.000001);
    }

    // Normaliza una etiqueta como hace normalizeRowKeys(), pero aislada para usar “en vivo”
    private function nk(string $k): string
    {
        return $this->normalizeKey($k);
    }

    // Inicializa un bucket de explicaciones (array<string,array<string>>)
    private function expInit(?array $exp = null): array
    {
        return $exp ?? [];
    }

    // Añade una línea de explicación a la clave normalizada $colKey
    private function expPush(array &$exp, string $colKey, string $line): void
    {
        if (!isset($exp[$colKey])) $exp[$colKey] = [];
        $exp[$colKey][] = $line;
    }

    private function upsertGenericCompetitors(array $genericList, string $countryIso): void
    {
        $iso = strtoupper(trim($countryIso));
        if ($iso === '') return;

        foreach ($genericList as $title) {
            $title = trim((string) $title);
            if ($title === '') continue;

            // Evita duplicados por (title, iso_code) gracias al unique de BD
            Competitor::firstOrCreate(
                ['title' => $title, 'iso_code' => $iso],
                ['available' => 1] // uid se autogenera en el modelo
            );
        }
    }
}
