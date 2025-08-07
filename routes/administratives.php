<?php



use App\Http\Controllers\Administratives\Orders\DocumentsController;
use App\Http\Controllers\Administratives\DashboardController;
use App\Http\Controllers\Administrative\ReportController;

Route::group(['prefix' => 'administrative'], function () {

    Route::group(['prefix' => 'reports'], function () {
        Route::get('/', [ReportController::class, 'index'])->name('administrative.reports');
        Route::get('/view', [ReportController::class, 'view'])->name('administrative.reports.view');
    });


});
