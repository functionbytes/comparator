<?php

namespace App\Jobs\Comparator;

use App\Models\Comparator\Comparator;
use App\Services\MinderestService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessComparatorData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $comparatorUid;
    protected string $iso;

    public function __construct(string $comparatorUid, string $iso)
    {
        $this->comparatorUid = $comparatorUid;
        $this->iso = $iso;
    }

    public function handle(): void
    {
        // Cargar comparador con la configuración de idioma
        $comparator = Comparator::withApiKeyByLangIso($this->iso)
            ->where('uid', $this->comparatorUid)
            ->first();

        if (!$comparator) {
            Log::warning("Comparator not found: {$this->comparatorUid} ({$this->iso})");
            return;
        }

        // Obtener la API key desde la relación
        $apiKey = optional($comparator->comparatorLangs->first())->api_key;

        if (!$apiKey) {
            Log::warning("API key not found for comparator {$this->comparatorUid} ({$this->iso})");
            return;
        }

        $service = new MinderestService($apiKey);
        $data = $service->getData($comparator);

        $rawData = $data['csv'] ?? null;

        if (!$rawData) {
            Log::warning("No CSV data retrieved for comparator {$this->comparatorUid}");
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($rawData));

        $rows = collect($lines)->map(function ($line) {
            $parts = explode("\t", $line);
            return [
                'product_code' => $parts[0] ?? null,
                'marketplace'  => $parts[1] ?? null,
                'seller'       => $parts[2] ?? null,
                'price'        => isset($parts[3]) ? floatval(str_replace(',', '.', $parts[3])) : 0,
                'quantity'     => isset($parts[4]) ? (int) $parts[4] : 1,
                'url'          => $parts[5] ?? null,
                'shipping'     => isset($parts[6]) ? floatval(str_replace(',', '.', $parts[6])) : 0,
                'date'         => isset($parts[7]) && strlen(trim($parts[7])) > 0
                    ? Carbon::createFromFormat('y/m/d H:i', trim($parts[7]))->toDateTimeString()
                    : now()->toDateTimeString(),
            ];
        })->filter(fn($row) => !empty($row['product_code']));

        $grouped = $rows->groupBy('product_code')->map(function ($items, $code) {
            return [
                'product_code' => $code,
                'competitors' => $items->values()->toArray()
            ];
        });

        foreach ($grouped as $entry) {
            DB::connection('mongodb')
                ->collection('comparador_' . strtolower($this->iso))
                ->updateOne(
                    ['product_code' => $entry['product_code']],
                    ['$set' => $entry],
                    ['upsert' => true]
                );
        }

        Log::info("Inserted {$grouped->count()} products into comparador_{$this->iso}");
    }
}
