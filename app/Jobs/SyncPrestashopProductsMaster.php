<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Jobs\SyncPrestashopProductChunk;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Prestashop\Product\Product as PrestashopProduct;

class SyncPrestashopProductsMaster implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        PrestashopProduct::where('active', 1)
            ->whereHas('import')
            ->orderBy('id_product')
            ->chunkById(50, function ($products) {
                $ids = $products->pluck('id_product')->toArray();
                SyncPrestashopProductChunk::dispatch($ids);
            });
    }
}
