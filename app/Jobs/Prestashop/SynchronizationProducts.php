<?php

namespace App\Jobs\Prestashop;

use App\Models\Lang;
use App\Models\Manufacturer;
use App\Models\Prestashop\Manufacturer as PrestashopManufacturer;
use App\Models\Prestashop\Product\Product as PrestashopProduct;
use App\Models\Product;
use App\Models\Product as ComparatorProduct;
use App\Models\ProductLang;
use App\Models\ProductPriceHistory;
use App\Models\ProductReference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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

            Log::info('SyncPrestashopProducts: Job execution started.');

        PrestashopProduct::with(['langs'])
            ->orderBy('id_product')
            ->where('active', 1)
            ->chunkById(200, function ($prestashopProducts) {

                Log::info('Procesando lote de productos: ' . count($prestashopProducts));

                try {

                    $prestashopLangIds = [];
                    foreach ($prestashopProducts as $product) {
                        foreach ($product->langs as $lang) {
                            $prestashopLangIds[] = $lang->id_lang;
                        }
                    }

                    $prestashopLangIds = array_unique($prestashopLangIds);

                    $prestashopLangs = DB::connection('prestashop')
                        ->table('aalv_lang')
                        ->whereIn('id_lang', $prestashopLangIds)
                        ->get()
                        ->keyBy('id_lang');

                    $localLangs = Lang::whereIn('iso_code', $prestashopLangs->pluck('iso_code'))
                        ->get()
                        ->keyBy('iso_code');

                    foreach ($prestashopProducts as $psProduct) {

                        $combinations = $psProduct->combinations;
                        $langs = $psProduct->langs;

                        if($psProduct->id_manufacturer != 0){
                            $psManufacturer = PrestashopManufacturer::id($psProduct->id_manufacturer);
                            $comparatorManufacturer = Manufacturer::firstOrCreate(
                                ['title' => $psManufacturer->name],
                                ['available' => 1]
                            );
                            $manufacturer = $comparatorManufacturer->id;
                        }else{
                            $manufacturer = null;
                        }

                        $comparatorProduct = Product::firstOrCreate([
                            'prestashop_id' => $psProduct->id_product,
                            'ean' => $psProduct->ean,
                            'upc' => $psProduct->upc,
                            'category_id' => $psProduct->base_parent_category->id_category,
                            'manufacturer_id' => $manufacturer,
                            'available' => 1,
                            'type' => count($combinations)>0 ? 'combination' : 'simple'
                        ]);

                        foreach ($langs as $lang) {

                            $psLang = $prestashopLangs->get($lang->id_lang);
                            $localLang = $localLangs->get($psLang->iso_code);

                            $langProduct = ProductLang::firstOrCreate([
                                'product_id' => $comparatorProduct->id,
                                'lang_id' => $localLang->id,
                                'title' => $lang->name,
                                'url' => $lang->url,
                                'price' =>  0.0,
                            ]);

                            switch ($comparatorProduct->type) {
                                case 'combination':

                                    dd('combination');
                                    foreach ($combinations as $combination) {

                                        $finalPriceWithIVA = 0.0;
                                        $prices = $combination->prices;
                                        $specificPrice = $prices->firstWhere('from_quantity', 1);

                                        if ($specificPrice) {
                                            $finalPriceWithIVA = round(
                                                ((float) $specificPrice->price - (float) $specificPrice->reduction)
                                                * (1 + (float) $localLang->iva / 100),
                                                2
                                            );
                                        }
                                        ProductReference::updateOrCreate([
                                            'reference' => $combination->reference,
                                            'combination_id' => $combination->id_product,
                                            'product_id' => $comparatorProduct->id,
                                            'lang_id' => $localLang->id,
                                            'available' => $combination->stock?->quantity > 0,
                                            'attribute_id' => $combination->id_product_attribute,
                                            'url' => null,
                                        ], []);

                                        $langProduct->stock = $combination->stock?->quantity ?? 0;
                                        $langProduct->price = $finalPriceWithIVA;
                                        $langProduct->available = $combination->stock?->quantity > 0;
                                        $langProduct->save();
                                    }

                                    break;

                                case 'simple':

                                    $finalPriceWithIVA = 0.0;
                                    $specificPrice = $psProduct->prices->firstWhere('from_quantity', 1);

                                    if ($specificPrice) {
                                        $finalPriceWithIVA = round(
                                            ((float) $specificPrice->price - (float) $specificPrice->reduction)
                                            * (1 + (float) $localLang->iva / 100),
                                            2
                                        );
                                    }

                                    ProductReference::updateOrCreate([
                                        'reference' => $psProduct->reference,
                                        'combination_id' => null,
                                        'product_id' => $comparatorProduct->id,
                                        'lang_id' => $localLang->id,
                                        'available' => $psProduct->stock?->quantity > 0,
                                        'attribute_id' => null,
                                        'url' => null,
                                    ], []);

                                    $langProduct->stock = $psProduct->stock?->quantity ?? 0;
                                    $langProduct->price = $finalPriceWithIVA;
                                    $langProduct->available = $psProduct->stock?->quantity > 0;
                                    $langProduct->save();

                                    break;

                                default:
                                    Log::warning("Tipo de producto desconocido para ID {$psProduct->id_product}");
                                    break;
                            }


                        }
                    }


                } catch (Throwable $e) {
                    Log::error('Error during product sync chunk: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            });

            Log::info('SyncPrestashopProducts: Job finished successfully.');

    }

}
