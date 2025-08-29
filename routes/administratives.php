<?php



use App\Http\Controllers\Administratives\Orders\DocumentsController;
use App\Http\Controllers\Administratives\DashboardController;
use App\Http\Controllers\Administrative\ReportController;
use App\Http\Controllers\Api\Prestashop\Comparator\SyncComparatorController;
use App\Http\Controllers\ProvidersController;


Route::group(['prefix' => 'administrative'], function () {

    Route::get('/proveedores', [ProvidersController::class, 'index'])->name('providers.index');
    Route::post('/proveedores/validate', [ProvidersController::class, 'validateCsv'])->name('providers.validate'); // NUEVO
    Route::post('/proveedores/import', [ProvidersController::class, 'import'])->name('providers.import');

    Route::group(['prefix' => 'reports'], function () {
        Route::get('/', [ReportController::class, 'index'])->name('administrative.reports.index');
        Route::get('/view', [ReportController::class, 'view'])->name('administrative.reports.view');
        Route::get('/jobs', [SyncComparatorController::class, 'jobs']);
        // Route::get('/test', [SyncComparatorController::class, 'generarResultadosComparadorDesdeCsv']);
        Route::get('/test', [ReportController::class, 'test']);

    });


});
