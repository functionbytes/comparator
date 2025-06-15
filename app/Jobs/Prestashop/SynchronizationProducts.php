<?php

namespace App\Jobs\Prestashop;

use App\Models\Prestashop\Product\Product as PrestashopProduct;
use App\Models\Product;
use App\Models\Product as ComparatorProduct;
use App\Models\ProductPriceHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SynchronizationProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = 120; // Retry after 2 minutes

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        // This job can be dispatched without arguments to sync all products.
    }


    public function handle()
    {
        try {
            Log::info('SyncPrestashopProducts: Job execution started.');

            PrestashopProduct::with(['lang', 'prices'])
                ->where('active', 1)
                ->chunkById(200, function ($prestashopProducts) {
                    foreach ($prestashopProducts as $psProduct) {

                        if (empty($psProduct->reference)) {
                            Log::warning("Skipping Prestashop product ID {$psProduct->id_product} (no reference)");
                            continue;
                        }

                        $comparatorProduct = Product::firstOrNew([
                            'reference' => $psProduct->reference,
                            'category_id' => $psProduct->id_category_default,
                            'available' => 1,
                        ]);

                        $newPrice = (float) $psProduct->price;

                        if ($comparatorProduct->exists && (float) $comparatorProduct->current_price !== $newPrice) {

                           ProductPriceHistory::create([
                                'comparator_product_id' => $comparatorProduct->id,
                                'old_price' => $comparatorProduct->current_price,
                                'new_price' => $newPrice,
                            ]);
                        }

                        dd($psProduct);
                        // Guardar traducciones
                        foreach ($psProduct->lang as $langEntry) {
                            $comparatorProduct->langs()->updateOrCreate(
                                ['lang_id' => $langEntry->id_lang],
                                [
                                    'title' => $langEntry->name,
                                    'description' => $langEntry->description,
                                    //'description_short' => $langEntry->description_short,
                                    'url' => $langEntry->link_rewrite,
                                ]
                            );
                        }

                        foreach ($psProduct->prices as $price) {
                            $comparatorProduct->langs()->updateOrCreate(
                                [
                                    'prestashop_id' => $price->id_specific_price,
                                ],
                                [
                                    'from_quantity'   => $price->from_quantity,
                                    'price'           => $price->price,
                                    'reduction'       => $price->reduction,
                                    'reduction_tax'   => $price->reduction_tax,
                                    'reduction_type'  => $price->reduction_type,
                                    'from'            => $price->from,
                                    'to'              => $price->to,
                                    'product_id'      => $comparatorProduct->id, // 🔒 clave foránea si no está en fillable
                                ]
                            );
                        }
                    }
                });

            Log::info('SyncPrestashopProducts: Job finished successfully.');
        } catch (Throwable $e) {
            Log::error("SyncPrestashopProducts: Error => " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->fail($e);
        }
    }

}
