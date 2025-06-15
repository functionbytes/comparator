<?php

use App\Http\Controllers\Api\Prestashop\Product\SyncProductsController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'products'], function () {
   Route::get('/sync', [SyncProductsController::class, 'sync']);
});



Route::group(['prefix' => 'erp'], function () {
    Route::post('recuperarclienteerp', [ErpController::class, 'recuperarclienteerp']);
    Route::post('recuperaridclienteerp', [ErpController::class, 'recuperaridclienteerp']);
    Route::post('recuperarpedidoscliente', [ErpController::class, 'recuperarpedidoscliente']);
    Route::post('recuperarpedido', [ErpController::class, 'recuperarpedido']);
    Route::post('recuperarpedidoporid', [ErpController::class, 'recuperarpedidoporid']);
    Route::post('recuperarclienteerpAlsernet', [ErpController::class, 'recuperarclienteerpAlsernet']);
    Route::post('recuperardatosclienteerp', [ErpController::class, 'recuperardatosclienteerp']);
    Route::post('recuperardatosclienteerpporidweb', [ErpController::class, 'recuperardatosclienteerpporidweb']);
    Route::post('recuperardatosclienteerpporidgestion', [ErpController::class, 'recuperardatosclienteerpporidgestion']);
    Route::post('getIdiomaGestion', [ErpController::class, 'getIdiomaGestion']);
    Route::post('getPaisGestion', [ErpController::class, 'getPaisGestion']);
    Route::post('guardardatosclienteerp', [ErpController::class, 'guardardatosclienteerp']);
    Route::post('recuperarcatalogosclienteerp', [ErpController::class, 'recuperarcatalogosclienteerp']);
    Route::post('suscribircatalogosporeamilerp', [ErpController::class, 'suscribircatalogosporeamilerp']);
    Route::post('delsuscribircatalogosporeamilerp', [ErpController::class, 'delsuscribircatalogosporeamilerp']);
    Route::post('savelopd', [ErpController::class, 'savelopd']);
    Route::post('recuperarstockcentral', [ErpController::class, 'recuperarstockcentral']);
    Route::post('recuperaridarticulo', [ErpController::class, 'recuperaridarticulo']);
    Route::post('consultabono', [ErpController::class, 'consultabono']);
    Route::post('marcarbono', [ErpController::class, 'marcarbono']);
    Route::post('consultavalecompra', [ErpController::class, 'consultavalecompra']);
    Route::post('actualizarvalecompra', [ErpController::class, 'actualizarvalecompra']);
    Route::post('crearvalecompra', [ErpController::class, 'crearvalecompra']);
    Route::post('tienetarifaplana', [ErpController::class, 'tienetarifaplana']);
    Route::post('toGestion', [ErpController::class, 'toGestion']);
    Route::post('construirdatospedido', [ErpController::class, 'construirdatospedido']);
    Route::post('isMobilePhone', [ErpController::class, 'isMobilePhone']);
    Route::post('mandarpedido', [ErpController::class, 'mandarpedido']);
    Route::post('forma_pago', [ErpController::class, 'forma_pago']);
});
