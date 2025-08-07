<?php

use App\Http\Controllers\Api\Prestashop\Product\SyncProductsController;
use App\Http\Controllers\Api\Prestashop\Comparator\SyncComparatorController;
use Illuminate\Support\Facades\Route;
use App\Models\ProductReference;
use App\Models\ProductReferenceLang;
use App\Models\ProductLang;

// Route::get('/', function () {
//     dd('asd');
// });

// Route::get('/', function () {

//     $relativePath = '2025-07-24/minderest_ES_151002.csv';

//     // 1) Verificar existencia
//     if (! Storage::disk('local')->exists($relativePath)) {
//         return response()->json(['error' => "Archivo no encontrado: $relativePath"], 404);
//     }

//     $fullPath = Storage::disk('local')->path($relativePath);
//     $handle   = fopen($fullPath, 'r');

//     if ($handle === false) {
//         return response()->json(['error' => 'No se pudo abrir el archivo.'], 500);
//     }

//     // 2) Saltar cabecera
//     fgetcsv($handle, 0, ';');

//     // 3) Agrupar CSV por referencia
//     $csvGroups = [];
//     $nn = 0;
//     while (($row = fgetcsv($handle, 0, ';')) !== false) {

//         $ref = trim($row[0]);

//         // Mi ID
//         // Nombre del competidor
//         // Nombre de seller
//         // Precio (seller - source)
//         // Stock (seller - source)
//         // URL de producto
//         // Precio de gastos de envío (por competidor) (seller - source)
//         // Actualización de producto

//         $csvGroups[$ref][] = [
//             'competitor_name' => $row[1] ?? '',
//             'seller_name'     => $row[2] ?? '',
//             'price'           => $row[3] ?? '',
//             'stock'           => $row[4] ?? '',
//             'product_url'     => $row[5] ?? '',
//             'shipping_price'  => $row[6] ?? '',
//             'updated_at'      => $row[7] ?? '',
//         ];
//         $nn++;
//         if ($nn == 100) {
//             break;
//         }
//     }
//     fclose($handle);

//     // 4) Dedupe dentro de cada grupo por hash de serialización
//     foreach ($csvGroups as $ref => $comps) {
//         $unique    = [];
//         $seenHashes = [];
//         foreach ($comps as $comp) {
//             // md5 de serialize garantiza igualdad exacta de contenidos
//             $hash = md5(serialize($comp));
//             if (! isset($seenHashes[$hash])) {
//                 $seenHashes[$hash] = true;
//                 $unique[] = $comp;
//             }
//         }
//         $csvGroups[$ref] = $unique;
//     }

//     // 5) Traer de BD todos los registros que interesan
//     $refs       = array_keys($csvGroups);
//     $dbProducts = ProductReference::whereIn('reference', $refs)
//         ->get()
//         ->keyBy('reference');


//     $results = [];

//     foreach ($csvGroups as $ref => $competitors) {
//         if (isset($dbProducts[$ref])) {
//             $prod = $dbProducts[$ref];

//             $productLang = ProductLang::where('product_id', $prod->product_id)
//                 ->where('lang_id', 1)
//                 ->first();

//             $productRefLang = ProductReferenceLang::where('reference_id', $prod->id)
//                 ->where('lang_id', 1)
//                 ->first();

//             $char = $prod->characteristics;
//             $char = ($char !== null && $char !== '') ? "($char)" : '';
//             // dd($prod);
//             $result[] = [
//                 'reference'       => $ref,
//                 'Matchs'            => count($competitors),
//                 'name' => $productLang->title . $char,
//                 'estado_gestion' => $prod->estado_gestion == 0,
//                 'preciofijo' => '',
//                 'etiqueta' => '',
//                 'visible_web_mas_portes' => '',
//                 'externo' => '',
//                 'product_id'      => $prod->product_id,
//                 'competitors'     => $competitors,
//             ];
//         } else {
//             $result[] = [
//                 'reference'   => $ref,
//                 'error'       => 'No encontrado (lang_id=1)',
//                 'competitors' => $competitors,
//             ];
//         }
//     }

//     // dd($result);


//     // Datos de ejemplo: [Nombre, Email, Edad]
//     // $data = [
//     //     ['John Doe',    'john@example.com',  35, 'Madrid'],
//     //     ['Jane Smith',  'jane@example.com',  28, 'Barcelona'],
//     //     ['Alice Jones', 'alice@example.com', 42, 'Valencia'],
//     //     ['Bob Martin',  'bob@example.com',  23, 'Sevilla'],
//     //     ['Eva Green',   'eva@example.com',   31, 'Bilbao'],
//     // ];


//     // Pasamos los datos a la vista 'tabla'
//     return view('tabla', compact('result'));
// });

 Route::get('/sync/comparator', [SyncComparatorController::class, 'sync']);
Route::get('/sync', [SyncProductsController::class, 'sync']);
// Route::get('/', [SyncProductsController::class, 'xml']);
// Route::get('/', [SyncProductsController::class, 'excel']);
// Route::get('/', [SyncProductsController::class, 'xmlToCsv']);
// Route::get('/', [SyncProductsController::class, 'excelToDisk']);
// Route::get('/', [SyncProductsController::class, 'xmlToDisk']);
// Route::get('/', [SyncProductsController::class, 'sync_copi']);


Route::get('/synct', [SyncProductsController::class, 'synct']);
Route::get('/jobs', [SyncProductsController::class, 'jobs']);
// Route::get('/xml/{lang}', [SyncProductsController::class, 'xml']);
