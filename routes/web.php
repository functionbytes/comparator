<?php

use App\Http\Controllers\Api\Prestashop\Product\SyncProductsController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     dd('asd');
// });



Route::get('/', [SyncProductsController::class, 'sync']);
// Route::get('/', [SyncProductsController::class, 'xml']);
// Route::get('/', [SyncProductsController::class, 'excel']);
// Route::get('/', [SyncProductsController::class, 'sync_copi']);


// Route::get('/', [SyncProductsController::class, 'jobs']);
// Route::get('/xml/{lang}', [SyncProductsController::class, 'xml']);