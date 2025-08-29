<?php

namespace App\Jobs\Comparator;

use App\Models\Comparator\Comparator;
use App\Services\MinderestService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ProcessComparatorData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 600;
    public $backoff = [60, 120];

    public function __construct()
    {
        // $this->comparatorUid = $comparatorUid;
        // $this->iso = $iso;
    }

    public function handle(): void
    {
        // 1) Ejecuta syncAll() del mismo controlador
        $response = app(\App\Http\Controllers\Api\Prestashop\Comparator\SyncComparatorController::class)->syncAll();

        // 2) Normaliza a array
        $payload = $response instanceof JsonResponse ? $response->getData(true) : $response;
        if (!is_array($payload)) {
            Log::warning('SyncAllMinderestJob: payload inesperado', ['payload' => $payload]);
            return;
        }

        // 3) Itera items OK y despacha N jobs por item (N=cantidad o 1 por defecto)
        foreach ($payload as $row) {
            if (!is_array($row)) continue;

            $status     = strtolower($row['status'] ?? '');
            $countryIso = $row['countryiso'] ?? $row['countryIso'] ?? null;
            if ($status !== 'ok'  || empty($countryIso)) {
                continue;
            }

            $cantidad = (int)($row['cantidad'] ?? 1);
            if ($cantidad < 1) $cantidad = 1;
            for ($i = 1; $i <= $cantidad; $i++) {
                GenerateComparatorResultsJob::dispatch(
                    $countryIso
                );
            }
        }
    }
}
