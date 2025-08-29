<?php

namespace App\Jobs\Comparator;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateComparatorResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $countryIso;

    public $tries = 3;
    public $timeout = 1800; // ajusta según el procesamiento

    public function __construct(string $countryIso)
    {
        $this->countryIso = $countryIso;
    }

    public function handle(): void
    {
        // Llama a tu método del controlador con los datos necesarios
        app(\App\Http\Controllers\Api\Prestashop\Comparator\SyncComparatorController::class)
            ->generarResultadosComparadorDesdeCsv($this->countryIso); // <-- string
    }
}
