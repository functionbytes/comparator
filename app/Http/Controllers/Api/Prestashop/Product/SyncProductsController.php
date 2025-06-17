<?php

namespace App\Http\Controllers\Api\Prestashop\Product;

use App\Http\Controllers\Controller;
use App\Jobs\Prestashop\SynchronizationProducts;
use App\Models\Lang;
use App\Models\Prestashop\Product\Product as PrestashopProduct;
use App\Models\Product;
use App\Models\ProductLang;
use App\Models\ProductPriceHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProductsController extends Controller
{
    public function testSync(): JsonResponse
    {
        try {
            $job = new SynchronizationProducts();
            $job->handle(); // Ejecutamos directamente la lógica del job

            return response()->json(['message' => 'Sincronización ejecutada correctamente.']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error durante la sincronización.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function sync()
    {


        return PrestashopProduct::with(['langs', 'prices'])
            ->where('active', 1)
            ->chunkById(200, function ($prestashopProducts) {


                $prestashopLangIds = [];

                foreach ($prestashopProducts as $product) {

                    $combinations = $product->combinations;


                    foreach ($product->langs as $lang) {
                        $prestashopLangIds[] = $lang->id_lang;


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


                            if (empty($psProduct->reference)) {
                                Log::warning("Skipping Prestashop product ID {$psProduct->id_product} (no reference)");
                                continue;
                            }

                            $comparatorProduct = Product::firstOrNew([
                                'prestashop_id' => $psProduct->id_product,
                                'ean' => $psProduct->ean,
                                'upc' => $psProduct->upc,
                                'category_id' => $psProduct->base_parent_category->id_category,
                                'available' => 1,
                                'type' => count($combinations)>0 ? 'combination' : 'simple'
                            ]);

                            if (!$comparatorProduct->exists) {
                                $comparatorProduct->save();
                            }

                            dd($comparatorProduct);

                            $newPrice = (float) $psProduct->price;

                            if ($comparatorProduct->exists &&
                                isset($comparatorProduct->current_price) &&
                                (float) $comparatorProduct->current_price !== $newPrice) {
                                ProductPriceHistory::create([
                                    'comparator_product_id' => $comparatorProduct->id,
                                    'old_price' => $comparatorProduct->current_price,
                                    'new_price' => $newPrice,
                                ]);
                            }

                            $langSyncData = [];

                            foreach ($psProduct->langs as $langEntry) {
                                $psLang = $prestashopLangs->get($langEntry->id_lang);
                                if (!$psLang) continue;

                                $localLang = $localLangs->get($psLang->iso_code);
                                if (!$localLang) continue;

                                $langSyncData[$langEntry->id_lang] = [
                                    'lang_id' => $localLang->id,
                                    'title' => $langEntry->name,
                                    'characteristics' => $langEntry->description,
                                    'url' => $langEntry->link_rewrite,
                                ];
                            }

                            if (!empty($langSyncData)) {
                                $comparatorProduct->langs()->syncWithoutDetaching($langSyncData);
                            }


                            dump($psProduct->prices);

                            foreach ($psProduct->prices as $price) {

                                // Si el precio tiene su propio id_lang, usarlo
                                $priceLangId = $price->id_lang ?? null;
                                $targetLangId = null;

                                if ($priceLangId && isset($langSyncData[$priceLangId])) {
                                    $targetLangId = $langSyncData[$priceLangId]['lang_id'];
                                } else {
                                    $firstLangData = reset($langSyncData);
                                    $targetLangId = $firstLangData ? $firstLangData['lang_id'] : null;
                                }
                                if ($targetLangId) {

                                    ProductLang::updateOrCreate(
                                        [
                                            'product_id' => $comparatorProduct->id,
                                            'prestashop_id' => $price->id_specific_price,
                                            'lang_id' => $targetLangId,
                                        ],
                                        [
                                            'from_quantity' => $price->from_quantity,
                                            'price' => $price->price,
                                            'reduction' => $price->reduction,
                                            'reduction_tax' => $price->reduction_tax,
                                            'reduction_type' => $price->reduction_type,
                                            'from' => $price->from,
                                            'to' => $price->to,
                                        ]
                                    );
                                }
                            }

                            dump($comparatorProduct);
                        }

                    }
                }
            });

    }

}
