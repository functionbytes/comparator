<?php

namespace App\Http\Controllers\Api\Prestashop\Comparator;

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
use Illuminate\Support\Facades\Http;


class SyncComparatorController extends Controller
{


    public function sync()
    {

        $exportId = 3127521780550338074;
        $baseFileName = "Completo";
        $countryIso = 'ES';

        // 1. Datos básicos
        $apiKey        = "hmX8fzl2586SsCBmkJAnw41G2XXo7poQ2KKqiXtk"; // O donde tengas tu API Key
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

}
