<?php

use App\Models\Setting\Setting;
use App\Models\User;
use Carbon\Carbon;

if (!function_exists('setCoupon')) {
    function setCoupon($coupon){
        $theTime = time() + 86400 * 7;
        setcookie('coupon_code', $coupon->code, $theTime, '/');
    }
}

if (!function_exists('updateSettings')) {
    function updateSettings($data){
        foreach($data as $key => $val){
            $setting = Setting::where('key', $key);
            if( $setting->exists() ){
                $setting->first()->update(['value' => $val]);
            }
        }
    }
}

if (!function_exists('setting')) {
    function setting($key){
        return  Setting::where('key','=',$key)->first()->value ?? '' ;
    }
}

if (!function_exists('removeCoupon')) {
    function removeCoupon()
    {
        if (isset($_COOKIE["coupon_code"])) {
            setcookie("coupon_code", "", time() - 3600);
            unset($_COOKIE["coupon_code"]);
        }
    }
}

if (!function_exists('getCoupon')) {
    function getCoupon()
    {
        if (request()->hasHeader("coupon_code")) {
            return request()->header("coupon_code");
        }
        if (isset($_COOKIE["coupon_code"])) {
            return $_COOKIE["coupon_code"];
        }
        return '';
    }
}

if (!function_exists('getCouponDiscount')) {
    function getCouponDiscount($subtotal, $code = '')
    {
        $amount = 0;
        $coupon = Coupon::where('code', $code)->first();

        if ($coupon) {
            $date = strtotime(date('d-m-Y H:i:s'));
            # check if coupon is not expired
            if ($coupon->start_date <= $date && $coupon->end_date >= $date) {
                if ($coupon->type == 'flat') {
                    $amount = (float) $coupon->amount;
                } else {
                    $amount = ((float) $coupon->amount * $subtotal) / 100;
                    if ($amount < (float) $coupon->min_price) {
                        $amount = (float) $coupon->min_price;
                    }
                }
            } else {
                removeCoupon();
            }
        } else {
            removeCoupon();
        }

        return $amount;
    }
}


if (!function_exists('getBundlesDate')) {
    function getBundlesDate($subtotal, $code = '')
    {
        $amount = 0;
        $coupon = Coupon::where('code', $code)->first();

        if ($coupon) {
            $date = strtotime(date('d-m-Y H:i:s'));
            # check if coupon is not expired
            if ($coupon->start_date <= $date && $coupon->end_date >= $date) {
                if ($coupon->type == 'flat') {
                    $amount = (float) $coupon->amount;
                } else {
                    $amount = ((float) $coupon->amount * $subtotal) / 100;
                    if ($amount < (float) $coupon->min_price) {
                        $amount = (float) $coupon->min_price;
                    }
                }
            } else {
                removeCoupon();
            }
        } else {
            removeCoupon();
        }

        return $amount;
    }
}

if (!function_exists('validateCouponForCoursesAndBundles')) {

    function validateCouponForCoursesAndBundles($item, $type,  $coupon)
    {
        // dd($item, $type,  $coupon);
        if (isset($coupon->bundle_ids) && $type == "bundle") {
            $bundle_ids = explode(',', $coupon->bundle_ids) ;
            if (in_array($item, $bundle_ids)) {
                return true;
            }
        }
        if (isset($coupon->course_ids) && $type == "course") {

            $courses_ids = explode(',', $coupon->course_ids) ;
            if (in_array($item, $courses_ids)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('checkCouponValidityForCheckout')) {
    function checkCouponValidityForCheckout($type, $price, $item,$code)
    {
        $coupon = Coupon::where('code', $code)->first();

        if ($coupon) {

            $date = date('Y-m-d');

            $totalCouponUsage = CouponUsage::where('coupon_code', $coupon->code)->sum('usage_count');

            if ($totalCouponUsage == $coupon->total_usage_limit) {
                removeCoupon();
                return [
                    'status'    => false,
                    'message'   => 'Se ha alcanzado el límite de uso total del cupón.'
                ];
            }

            if(Auth::check()){
                $couponUsageByUser = CouponUsage::where('user_id', auth()->user()->id)->where('coupon_code', $coupon->code)->first();
                if (!is_null($couponUsageByUser)) {
                    if ($couponUsageByUser->usage_count ==  $coupon->customer_usage_limit) {
                        removeCoupon();
                        return [
                            'status'    => false,
                            'message'   => 'Has utilizado este cupón durante el máximo de tiempo.'
                        ];
                    }
                }
            }


            if ($coupon->start_date >= $date && $coupon->end_date >= $date) {
                $subTotal = (float) getSubTotal($price ,$coupon->code, false);
                if ($subTotal >= (float) $coupon->min_spend) {
                    # check if coupon is for categories or products
                    if (isset($coupon->bundle_ids) || isset($coupon->course_ids)) {

                        if (!validateCouponForCoursesAndBundles($item, $type, $coupon)) {
                            # coupon not valid for your cart items
                            removeCoupon();
                            return [
                                'status'    => false,
                                'message'   => 'El cupón no es válido para este articulo.'
                            ];
                        }

                        $discount = $coupon->amount;
                        $total = $price- $discount;
                        setCoupon($coupon);

                        return [
                            'status'    => true,
                            'discount'    => $discount,
                            'total'    => $total,
                            'subtotal'    => $subTotal,
                            'code'    => $code,
                            'message'   => 'Cupón aplicado exitosamente'
                        ];
                    }

                    return [
                        'status'    => true,
                        'message'   => 'El cupón no es válido para este articulo.'
                    ];
                } else {
                    # min amount not reached
                    removeCoupon();
                    return [
                        'status'    => false,
                        'message'   => 'No se ha alcanzado el importe mínimo del pedido para utilizar este cupón'
                    ];
                }
            } else {
                # expired
                removeCoupon();
                return [
                    'status'    => false,
                    'message'   => 'El cupón ha caducado'
                ];
            }
        } else {
            # coupon not found
            removeCoupon();

            return [
                'status'    => false,
                'message'   => 'El cupón no es válido'
            ];
        }
    }
}

if (!function_exists('formatPrice')) {

    function formatPrice($price, $truncate = false, $forceTruncate = false, $addSymbol = true, $numberFormat = true)

    {
        if (request()->hasHeader('Currency-Code')) {
            $price = floatval($price) / (floatval(env('DEFAULT_CURRENCY_RATE')) || 1);
            $price = floatval($price) * floatval(ApiCurrencyMiddleWare::currencyData()->rate);
        } else if (Session::has('currency_code') && Session::has('local_currency_rate')) {
            $price = floatval($price) / (floatval(env('DEFAULT_CURRENCY_RATE')) || 1);
            $price = floatval($price) * floatval(Session::get('local_currency_rate'));
        }

        if ($numberFormat) {
            if ($truncate) {
                if (getSetting('truncate_price') == 1 || $forceTruncate == true) {
                    if ($price < 1000000) {
                        $price = number_format($price, getSetting('no_of_decimals'));
                    } else if ($price < 1000000000) {
                        $price = number_format($price / 1000000, getSetting('no_of_decimals')) . 'M';
                    } else {
                        $price = number_format($price / 1000000000, getSetting('no_of_decimals')) . 'B';
                    }
                }
            } else {
                if (getSetting('no_of_decimals') > 0) {
                    $price = number_format($price, getSetting('no_of_decimals'));
                } else {
                    $price = number_format($price, getSetting('no_of_decimals'), '.', ',');
                }
            }
        }

        if ($addSymbol) {
            // currency symbol
            if (request()->hasHeader('Currency-Code')) {
                $symbol             =   ApiCurrencyMiddleWare::currencyData()->symbol;
                $symbolAlignment    =   ApiCurrencyMiddleWare::currencyData()->alignment;
            } else {
                $symbol             = Session::has('currency_symbol')           ? Session::get('currency_symbol')           : env('DEFAULT_CURRENCY_SYMBOL');
                $symbolAlignment    = Session::has('currency_symbol_alignment') ? Session::get('currency_symbol_alignment') : env('DEFAULT_CURRENCY_SYMBOL_ALIGNMENT');
            }
            if ($symbolAlignment == 0) {
                return $symbol . $price;
            } else if ($symbolAlignment == 1) {
                return $price . $symbol;
            } else if ($symbolAlignment == 2) {
                # space
                return $symbol . ' ' . $price;
            } else {
                # space
                return $price . ' ' .  $symbol;
            }
        }
        return $price;
    }
}

if (!function_exists('sellCountPercentage')) {
    function sellCountPercentage($product)
    {
        $sold = $product->total_sale_count;
        $target = (int) $product->sell_target;
        $salePercentage = ($sold * 100) / ($target > 0 ? $target : 1);
        return round($salePercentage);
    }
}

if (!function_exists('discountPercentage')) {
    function discountPercentage($product)
    {
        $discountPercentage = $product->discount_value;

        if ($product->discount_type != "percent") {
            $price = productBasePrice($product);
            $discountAmount = discountedProductBasePrice($product);
            $discountValue = $price - $discountAmount;
            $discountPercentage = ($discountValue * 100) / ($price > 0 ? $price : 1);
        }

        return round($discountPercentage);
    }
}

if (!function_exists('getSubTotal')) {
    function getSubTotal($price, $couponDiscount = true, $couponCode = '', $addTax = true)
    {
        $amount = 0;
        if ($couponDiscount) {
            $amount = getCouponDiscount($price, $couponCode);
        }
        return $price - $amount;
    }
}

if (!function_exists('getTotal')) {
    function getTotal($carts)
    {
        $tax = 0;
        if ($carts) {

            foreach ($carts as $cart) {
                $product    = $cart->product_variation->product;
                $variation  = $cart->product_variation;

                $variationTaxAmount = variationTaxAmount($product, $variation);
                $tax += (float) $variationTaxAmount * $cart->qty;
            }
        }
        return $tax;
    }
}

if (!function_exists('getFavicon')) {
    function getFavicon(){

        $setting = Setting::where('key', '=', "page_favicon")->first();
        return count($setting->getMedia('favicon'))>0 ? $setting->getfirstMedia('favicon')->getfullUrl()  : asset('/pages/images/favicon.png');
    }
}


if (!function_exists('getMeta')) {
    function getMeta()
    {
        $setting = Setting::where('key', '=', "meta_image")->first();
        return count($setting->getMedia('meta')) > 0 ? $setting->getfirstMedia('meta')->getfullUrl()  : asset('/pages/images/favicon.png');
    }
}


if (!function_exists('getUrl')) {
    function getUrl(){
        return URL::to('/');
    }
}

if (!function_exists('getLogo')) {
    function getLogo(){
        $setting = Setting::where('key', '=', "page_logo")->first();
        return count($setting->getMedia('logo'))>0 ? $setting->getfirstMedia('logo')->getfullUrl()  : asset('/pages/images/logo.png');
    }
}

if (!function_exists('getSetting')) {
    function getSetting(){
        return Setting::first();
    }
}

if (!function_exists('getMeta')) {
    function getMeta(){
        $setting = Setting::where('key', '=', "meta_image")->first();
        return count($setting->getMedia('metas'))>0 ? $setting->getfirstMedia('metas')->getfullUrl()  : asset('/pages/metas/logo.png');
    }
}

if (!function_exists('clearSessionExceptCurrent')) {
    function clearSessionExceptCurrent(User $user){
        if(config('session.driver') == 'database'){
            $user->sessions()->where('id', '<>', session()->getId())->delete();
        }else{
            $user->sessions()->where('id', '<>', session()->getId())->delete();
        }
    }
}

if (!function_exists('paginationNumber')) {
    function paginationNumber($value = null){
        return $value != null ? $value : env('DEFAULT_PAGINATION');
    }
}

function certificate_date($dates): string {
    $date = Carbon::parse($dates);
    return ucwords($date->format('d-m-Y'));
}

function humanize_date($dates): string {
    $date = Carbon::parse($dates);
    return ucwords($date->format('F j, Y'));
}

function month($dates): string {
    $date = Carbon::parse($dates);
    return ucwords($date->format('Y'));
}

function day($dates): string{
    $date = Carbon::parse($dates);
    return ucwords($date->format('j'));
}

function year($dates): string{
    $date = Carbon::parse($dates);
    return ucwords($date->format('Y'));
}


function dates($dates): string{
    $date = Carbon::parse($dates);
    return ucwords($date->format('d-m-Y'));
}


function input_date($dates): string{
    $date = Carbon::parse($dates);
    return ucwords($date->format('d-m-Y'));
}


use Illuminate\Support\Facades\DB;
use function App\Helpers\xml_to_array;

function table($name)
{
    return \DB::getTablePrefix() . $name;
}

function quote($value)
{
    return "'$value'";
}

function db_quote($value)
{
    return \DB::connection()->getPdo()->quote($value);
}

function each_batch($array, $batchSize, $skipHeader, $callback)
{
    $batch = [];
    foreach ($array as $i => $value) {
        // skip the header
        if ($i == 0 && $skipHeader) {
            continue;
        }

        if ($i % $batchSize == 0) {
            $callback($batch);
            $batch = [];
        }
        $batch[] = $value;
    }

    // the last callback
    if (sizeof($batch) > 0) {
        $callback($batch);
    }
}

function join_paths()
{
    $paths = array();
    foreach (func_get_args() as $arg) {
        if (is_null($arg)) {
            continue;
        }
        if (preg_match('/http:\/\//i', $arg)) {
            throw new \Exception('Path contains http://! Use `join_url` instead. Error for ' . implode('/', func_get_args()));
        }

        if ($arg !== '') {
            $paths[] = $arg;
        }
    }

    return preg_replace('#/+#', '/', implode('/', $paths));
}

function join_url()
{
    $paths = array();
    foreach (func_get_args() as $arg) {
        if (!empty($arg)) {
            $paths[] = $arg;
        }
    }

    return preg_replace('#(?<=[^:])/+#', '/', implode('/', $paths));
}

function array_unique_by($array, $callback)
{
    $result = [];
    foreach ($array as $value) {
        $key = $callback($value);
        $result[$key] = $value;
    }

    return array_values($result);
}

function get_localization_config($name, $locale)
{
   $defaultConfig = config('localization')['*'];

    if (array_key_exists($locale, config('localization'))) {
        $config = config('localization')[$locale];
    }

    if (isset($config) && array_key_exists($name, $config) && array_key_exists($name, $defaultConfig)) {
        return $config[$name];
    } elseif (array_key_exists($name, $defaultConfig)) {
        return $defaultConfig[$name];
    } else {
        throw new \Exception('Localization config for "' . $name . '" does not exist');
    }

}

function get_datetime_format($name, $locale)
{
    $defaultConfig = config('localization')['*'];

    if (array_key_exists($locale, config('localization'))) {
        $config = config('localization')[$locale];
    }

    if (isset($config) && array_key_exists($name, $config) && array_key_exists($name, $defaultConfig)) {
        return $config[$name];
    } elseif (array_key_exists($name, $defaultConfig)) {
        return $defaultConfig[$name];
    } else {
        throw new \Exception('AC: Invalid datetime format type: ' . $name . ' => make sure the type is available in BOTH local and default (*) settings');
    }

}

function format_datetime(?\Carbon\Carbon $datetime, $name, $locale)
{
    if (is_null($datetime)) {
        return;
    }
    return $datetime->format(get_datetime_format($name, $locale));
}

function exec_enabled()
{
    try {
        exec('ls');
        return function_exists('exec') && !in_array('exec', array_map('trim', explode(', ', ini_get('disable_functions'))));
    } catch (\Throwable $ex) {
        return false;
    }
}

function artisan_migrate()
{
    \Artisan::call('migrate', ['--force' => true]);
}

function isSiteDemo()
{
    return config('app.demo');
}

function language_code()
{
    $default_language = Language::find(Setting::get('default_language'));

    if (isset($_COOKIE['last_language_code'])) {
        $language_code = $_COOKIE['last_language_code'];
    } elseif (app()->getLocale()) {
        $language_code = app()->getLocale();
    } elseif ($default_language) {
        $language_code = $default_language->code;
    } else {
        $language_code = 'en';
    }

    return $language_code;
}

function number_to_percentage($number, $precision = 2)
{
    if (!is_numeric($number)) {
        return $number;
    }

    return sprintf("%.{$precision}f%%", $number * 100);
}

function number_with_delimiter($number, $precision = null, $seperator = null, $locale = null)
{
    if (!is_numeric($number)) {
        return $number;
    }

    if (is_null($locale)) {
        $locale = 'es';
    }

    if (floor($number) == $number && is_null($precision)) {
        $precision = 0;
    }

    if (is_null($precision)) {
        $precision = get_localization_config('number_precision', $locale);
    }

    $decimal = get_localization_config('number_decimal_separator', $locale);

    if (is_null($seperator)) {
        $seperator = get_localization_config('number_thousands_separator', $locale);
    }

    return number_format($number, $precision, $decimal, $seperator);
}

function optimized_paginate($builder, $perPage = 15, $columns = null, $pageName = null, $page = null, $total = null)
{
    $pageName = $pageName ?: 'page';
    $page = $page ?: \Illuminate\Pagination\Paginator::resolveCurrentPage($pageName);
    $columns = $columns ?: ['*'];
    $total = is_null($total) ? $builder->getCountForPagination() : $total;
    // in case $total == 0
    $results = $total ? $builder->forPage($page, $perPage)->get($columns) : collect([]);

    return new \Illuminate\Pagination\LengthAwarePaginator($results, $total, $perPage, $page, [
        'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
        'pageName' => $pageName,
    ]);
}

function distinctCount($builder, $column = null, $method = 'group')
{
    $q = clone $builder;

    if (is_null($column)) {
    } elseif ($method == 'group') {
        $q->groupBy($column)->select($column);
    } elseif ($method == 'distinct') {
        $q->select($column)->distinct();
    }

    $count = DB::table(DB::raw("({$q->toSql()}) as sub"))
        ->addBinding($q->getBindings())
        ->count();

    return $count;
}

function func_enabled($name)
{
    try {
        $disabled = explode(',', ini_get('disable_functions'));

        return !in_array($name, $disabled);
    } catch (\Exception $ex) {
        return false;
    }
}

function app_version()
{
    return trim(file_get_contents(base_path('VERSION')));
}

function extract_email($str)
{
    preg_match("/(?<email>[-0-9a-zA-Z\.+_]+@[-0-9a-zA-Z\.+_]+\.[a-zA-Z]+)/", $str, $matched);
    if (array_key_exists('email', $matched)) {
        return $matched['email'];
    } else {
        return;
    }
}

function extract_name($str)
{
    $parts = explode('<', $str);
    if (count($parts) > 1) {
        return trim($parts[0]);
    }
    $parts = explode('@', extract_email($str));

    return $parts[0];
}

function extract_domain($email)
{
    $email = extract_email($email);
    $domain = substr(strrchr($email, '@'), 1);
    return $domain;
}

function doublequote($str)
{
    return sprintf('"%s"', preg_replace('/^"+|"+$/', '', $str));
}

function format_price($price, $format = '{PRICE}', $html = false)
{
    if ($html) {
        $html = str_replace('{PRICE}', ' <span class="p-amount">' . number_with_delimiter($price) . '</span> ', $format);
        // $html = str_replace(number_with_delimiter($price), ' <span class="p-amount">' . number_with_delimiter($price) . '</span> ', $html);
        return $html;
    } else {
        return str_replace('{PRICE}', number_with_delimiter($price), $format);
    }
}

function isInitiated()
{
    return file_exists(storage_path('app/installed'));
}

function formatSizeUnits($bytes)
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}

function rand_item($arr)
{
    return $arr[array_rand($arr)];
}

function checkEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function demo_auth()
{
    $auth = User::getAuthenticateFromFile();

    return [
        'email' => isset($auth['email']) ? $auth['email'] : '',
        'password' => $auth['password'] ? $auth['password'] : '',
    ];
}

function get_app_identity()
{
    return md5(config('app.key'));
}

function quoteDotEnvValue($value)
{
    $containsSharp = (strpos($value, '#') !== false);

    if ($containsSharp) {
        $value = str_replace('"', '\"', $value);
        $value = '"' . $value . '"';
    }

    return $value;
}

function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function strip_tags_only($text, $allowedTags = [])
{
    if (!is_array($allowedTags)) {
        $allowedTags = [
            $allowedTags
        ];
    }

    array_map(
        function ($allowedTag) use (&$text) {
            $regEx = '#<' . $allowedTag . '.*?>(.*?)</' . $allowedTag . '>#is';
            $text = preg_replace($regEx, '', $text);
        },
        $allowedTags
    );

    return $text;
}

function cursorIterate($query, $orderBy, $size, $callback)
{
    $cursor = null;
    $page = 1;
    do {
        $q = clone $query;
        // The 4th parameter contains the offset cursor
        $list = $q->orderBy($orderBy)->cursorPaginate($size, ['*'], 'cursor', $cursor);
        $callback($list->items(), $page);
        $cursor = $list->nextCursor();
        $page += 1;
    } while ($list->hasMorePages());
}

function makeInlineCss($html, array $cssFiles)
{
    libxml_use_internal_errors(true);

    $htmldoc = new \App\Library\InlineStyleWrapper($html);

    foreach ($cssFiles as $file) {
        if (file_exists($file)) {
            $styles = file_get_contents($file);
            $htmldoc->applyStylesheet($styles);
        }
    }

    return $htmldoc->getHTML();
}

function resize_crop_image($max_width, $max_height, $source_file, $dst_dir, $quality = 80)
{
    $imgsize = getimagesize($source_file);
    $width = $imgsize[0];
    $height = $imgsize[1];
    $mime = $imgsize['mime'];

    switch ($mime) {
        case 'image/gif':
            $image_create = "imagecreatefromgif";
            $image = "imagegif";
            break;

        case 'image/png':
            $image_create = "imagecreatefrompng";
            $image = "imagepng";
            $quality = 7;
            break;

        case 'image/jpeg':
            $image_create = "imagecreatefromjpeg";
            $image = "imagejpeg";
            $quality = 80;
            break;

        default:
            return false;
            break;
    }

    $dst_img = imagecreatetruecolor($max_width, $max_height);
    $src_img = $image_create($source_file);

    $width_new = $height * $max_width / $max_height;
    $height_new = $width * $max_height / $max_width;

    if ($width_new > $width) {
        $h_point = (($height - $height_new) / 2);
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, $h_point, $max_width, $max_height, $width, $height_new);
    } else {
        $w_point = (($width - $width_new) / 2);
        imagecopyresampled($dst_img, $src_img, 0, 0, $w_point, 0, $max_width, $max_height, $width_new, $height);
    }

    $image($dst_img, $dst_dir, $quality);

    if ($dst_img) {
        imagedestroy($dst_img);
    }
    if ($src_img) {
        imagedestroy($src_img);
    }
}

function filterSearchArray($items, $keyword)
{
    // search
    $results = [];
    foreach ($items as $item) {
        $row = [
            'rate' => 0,
            'item' => $item,
        ];

        if (isset($keyword)) {
            $keyword = trim(strtolower($keyword));

            // Keywords
            if (!empty($keyword)) {
                $keywords = preg_split('/\s+/', $keyword);
                $allExist = true;

                foreach ($keywords as $keyword) {
                    $exist = false;

                    foreach ($item['names'] as $name) {
                        $name = trim(strtolower($name));
                        if (strpos($name, $keyword) !== false) {
                            $row['rate'] += 1;
                            $exist = true;
                        }
                    }

                    if (isset($item['keywords'])) {
                        foreach ($item['keywords'] as $k) {
                            $k = trim(strtolower($k));
                            if (strpos($k, $keyword) !== false) {
                                $row['rate'] += 1;
                                $exist = true;
                            }
                        }
                    }

                    if (!$exist) {
                        $allExist = false;
                    }
                }

                if (!$allExist) {
                    $row['rate'] = 0;
                }
            }
        }

        if ($row['rate'] > 0) {
            $results[] = $row;
        }
    }

    usort($results, function ($a, $b) {
        if ($a['rate'] != $b['rate']) {
            return $a['rate'] <=> $b['rate'];
        } else {
            return strcmp(implode(' ', $a['item']['names']), implode(' ', $b['item']['names']));
        }
    });

    return $results;
}

function getPeriodEndsAt($startDate, $amount, $unit)
{
    switch ($unit) {
        case 'month':
            $endsAt = $startDate->addMonthsNoOverflow($amount);
            break;
        case 'day':
            $endsAt = $startDate->addDay($amount);
            break;
        case 'week':
            $endsAt = $startDate->addWeek($amount);
            break;
        case 'year':
            $endsAt = $startDate->addYearsNoOverflow($amount);
            break;
        default:
            throw new \Exception('Invalid time period unit: ' . $unit);
    }

    return $endsAt;
}

function getThemeColor($theme = false)
{
    $colors = [
        'default' => 'rgba(13, 24, 29, 0.85)',
        'blue' => 'rgba(9, 22, 28, 0.9)',
        'green' => 'rgba(11, 29, 29, 0.9)',
        'brown' => 'rgba(27, 21, 10, 0.9)',
        'pink' => 'rgba(28, 11, 19, 0.9)',
        'grey' => '#111111',
        'white' => '#444',
    ];

    if (!$theme || !isset($colors[$theme])) {
        return $colors['default'];
    }

    return $colors[$theme];
}

function getThemeMode($mode, $auto = 'light')
{
    $themeMode = $mode;
    if ($mode == 'auto') {
        if ($auto) {
            $themeMode = $auto;
        }
    }
    return $themeMode;
}

function getLogoMode($mode, $scheme, $daylight = 'light')
{
    if ($scheme !== 'white') {
        return 'light';
    }

    if ($mode == 'auto' && ($daylight == 'light' || $daylight == null)) {
        return 'dark';
    }

    if ($mode == 'light') {
        return 'dark';
    }

    return 'light';
}

function parseRss($config)
{
    $rss = [];

    $rssArray = xml_to_array(simplexml_load_string(\App\Helpers\url_get_contents_ssl_safe($config['url']), 'SimpleXMLElement', LIBXML_NOCDATA));
    $rssFeed = simplexml_load_string(\App\Helpers\url_get_contents_ssl_safe($config['url']), 'SimpleXMLElement', LIBXML_NOCDATA);

    $records = array_slice($rssArray['rss']['channel']['item'], 0, $config['size']);

    $feedData = [];
    $feedData['feed_title'] = (string) $rssFeed->channel->title;
    $feedData['feed_description'] = $rssFeed->channel->description->__toString();
    $feedData['feed_link'] = $rssFeed->channel->link->__toString();
    $feedData['feed_pubdate'] = $rssFeed->channel->pubDate->__toString();
    $feedData['feed_build_date'] = $rssFeed->channel->lastBuildDate->__toString();

    $rss['FeedTitle'] = parseRssTemplate($config['templates']['FeedTitle']['template'], $feedData);
    $rss['FeedSubtitle'] = parseRssTemplate($config['templates']['FeedSubtitle']['template'], $feedData);
    $rss['FeedTagdLine'] = parseRssTemplate($config['templates']['FeedTagdLine']['template'], $feedData);

    $rss['items'] = [];
    $count = 0;

    foreach ($rssFeed->channel->item as $item) {
        $itemData['item_title'] = $item->title;
        $itemData['item_pubdate'] = $item->pubDate;
        $itemData['item_description'] = $item->description;
        $itemData['item_url'] = $item->link;
        $itemData['item_enclosure_url'] = $item->enclosure['url'];
        $itemData['item_enclosure_type'] = $item->enclosure['type'];

        $item = [];
        $item['ItemTitle'] = parseRssTemplate($config['templates']['ItemTitle']['template'], $itemData);
        $item['ItemDescription'] = parseRssTemplate($config['templates']['ItemDescription']['template'], $itemData);
        $item['ItemMeta'] = parseRssTemplate($config['templates']['ItemMeta']['template'], $itemData);
        $item['ItemEnclosure'] = parseRssTemplate($config['templates']['ItemEnclosure']['template'], $itemData);
        $item['ItemStats'] = parseRssTemplate($config['templates']['ItemStats']['template'], $itemData);
        $rss['items'][] = $item;

        $count += 1;
        if ($config['size'] == $count) {
            break;
        }
    }

    return view('helpers.rss.template', [
        'rss' => $rss,
        'templates' => $config['templates'],
    ]);
}

function parseRssTemplate($template, $feedData)
{
    foreach ($feedData as $key => $value) {
        $template = str_replace('@' . $key, $value, $template);
    }

    if (isset($feedData['item_enclosure_url']) && $feedData['item_enclosure_url'] != '') {
        if (strpos($feedData['item_enclosure_type'], 'video') !== false) {
            $html = '<video controls width="320">
                        <source src="https://file-examples-com.github.io/uploads/2017/04/file_example_MP4_480_1_5MG.mp4" type="audio/mpeg">
                        Your browser does not support the audio element.
                </video>';
        } elseif (strpos($feedData['item_enclosure_type'], 'video') !== false) {
            $html = '<audio controls>
                        <source src="' . $feedData['item_enclosure_url'] . '" type="audio/mpeg">
                        Your browser does not support the audio element.
                </audio>';
        } else {
            $html = '<img class="my-2" src="' . $feedData['item_enclosure_url'] . '" height="100px" />';
        }
        $template = str_replace('@item_enclosure', $html, $template);
    }

    return $template;
}

function rssTags()
{
    return [
        'feed' => [
            '@feed_title',
            '@feed_description',
            '@feed_link',
            '@feed_pubdate',
            '@feed_build_date',
        ],
        'item' => [
            '@item_title',
            '@item_pubdate',
            '@item_description',
            '@item_image_url',
        ],
    ];
}

function getFullCodeByLanguageCode($languageCode)
{
    $locales = array(
        'af-ZA',
        'am-ET',
        'ar-AE',
        'ar-BH',
        'ar-DZ',
        'ar-EG',
        'ar-IQ',
        'ar-JO',
        'ar-KW',
        'ar-LB',
        'ar-LY',
        'ar-MA',
        'arn-CL',
        'ar-OM',
        'ar-QA',
        'ar-SA',
        'ar-SY',
        'ar-TN',
        'ar-YE',
        'as-IN',
        'az-Cyrl-AZ',
        'az-Latn-AZ',
        'ba-RU',
        'be-BY',
        'bg-BG',
        'bn-BD',
        'bn-IN',
        'bo-CN',
        'br-FR',
        'bs-Cyrl-BA',
        'bs-Latn-BA',
        'ca-ES',
        'co-FR',
        'cs-CZ',
        'cy-GB',
        'da-DK',
        'de-AT',
        'de-CH',
        'de-DE',
        'de-LI',
        'de-LU',
        'dsb-DE',
        'dv-MV',
        'el-GR',
        'en-US',
        'en-029',
        'en-AU',
        'en-BZ',
        'en-CA',
        'en-GB',
        'en-IE',
        'en-IN',
        'en-JM',
        'en-MY',
        'en-NZ',
        'en-PH',
        'en-SG',
        'en-TT',
        'en-ZA',
        'en-ZW',
        'es-AR',
        'es-BO',
        'es-CL',
        'es-CO',
        'es-CR',
        'es-DO',
        'es-EC',
        'es-ES',
        'es-GT',
        'es-HN',
        'es-MX',
        'es-NI',
        'es-PA',
        'es-PE',
        'es-PR',
        'es-PY',
        'es-SV',
        'es-US',
        'es-UY',
        'es-VE',
        'et-EE',
        'eu-ES',
        'fa-IR',
        'fi-FI',
        'fil-PH',
        'fo-FO',
        'fr-BE',
        'fr-CA',
        'fr-CH',
        'fr-FR',
        'fr-LU',
        'fr-MC',
        'fy-NL',
        'ga-IE',
        'gd-GB',
        'gl-ES',
        'gsw-FR',
        'gu-IN',
        'ha-Latn-NG',
        'he-IL',
        'hi-IN',
        'hr-BA',
        'hr-HR',
        'hsb-DE',
        'hu-HU',
        'hy-AM',
        'id-ID',
        'ig-NG',
        'ii-CN',
        'is-IS',
        'it-CH',
        'it-IT',
        'iu-Cans-CA',
        'iu-Latn-CA',
        'ja-JP',
        'ka-GE',
        'kk-KZ',
        'kl-GL',
        'km-KH',
        'kn-IN',
        'kok-IN',
        'ko-KR',
        'ky-KG',
        'lb-LU',
        'lo-LA',
        'lt-LT',
        'lv-LV',
        'mi-NZ',
        'mk-MK',
        'ml-IN',
        'mn-MN',
        'mn-Mong-CN',
        'moh-CA',
        'mr-IN',
        'ms-BN',
        'ms-MY',
        'mt-MT',
        'nb-NO',
        'ne-NP',
        'nl-BE',
        'nl-NL',
        'nn-NO',
        'nso-ZA',
        'oc-FR',
        'or-IN',
        'pa-IN',
        'pl-PL',
        'prs-AF',
        'ps-AF',
        'pt-BR',
        'pt-PT',
        'qut-GT',
        'quz-BO',
        'quz-EC',
        'quz-PE',
        'rm-CH',
        'ro-RO',
        'ru-RU',
        'rw-RW',
        'sah-RU',
        'sa-IN',
        'se-FI',
        'se-NO',
        'se-SE',
        'si-LK',
        'sk-SK',
        'sl-SI',
        'sma-NO',
        'sma-SE',
        'smj-NO',
        'smj-SE',
        'smn-FI',
        'sms-FI',
        'sq-AL',
        'sr-Cyrl-BA',
        'sr-Cyrl-CS',
        'sr-Cyrl-ME',
        'sr-Cyrl-RS',
        'sr-Latn-BA',
        'sr-Latn-CS',
        'sr-Latn-ME',
        'sr-Latn-RS',
        'sv-FI',
        'sv-SE',
        'sw-KE',
        'syr-SY',
        'ta-IN',
        'te-IN',
        'tg-Cyrl-TJ',
        'th-TH',
        'tk-TM',
        'tn-ZA',
        'tr-TR',
        'tt-RU',
        'tzm-Latn-DZ',
        'ug-CN',
        'uk-UA',
        'ur-PK',
        'uz-Cyrl-UZ',
        'uz-Latn-UZ',
        'vi-VN',
        'wo-SN',
        'xh-ZA',
        'yo-NG',
        'zh-CN',
        'zh-HK',
        'zh-MO',
        'zh-SG',
        'zh-TW',
        'zu-ZA',
    );

    foreach ($locales as $locale) {
        if ($languageCode . '-' . strtoupper($languageCode) === $locale) {
            return $locale;
        }
    }

    foreach ($locales as $locale) {
        if (strpos($locale, $languageCode) === 0) {
            return $locale;
        }
    }

    return null;

}

function getDefaultLogoUrl($type)
{
    if (!in_array($type, ['light', 'dark'])) {
        throw new \Exception('Logo type must be either "light" or "dark"');
    }

    if ($type == 'light') {
        $logo = config('custom.default_logo_light');
    } elseif ($type == 'dark') {
        $logo = config('custom.default_logo_dark');
    }

    return asset($logo);
}

function getSiteLogoUrl($type)
{
    if (!in_array($type, ['light', 'dark'])) {
        throw new \Exception('Logo type must be either "light" or "dark"');
    }

    return getDefaultLogoUrl($type);
}

function app_profile($key)
{
    $profile = config('custom.app_profile');
    if (is_null($profile)) {
        return null;
    }

    $configFile = config_path("{$profile}.php");
    if (!\Illuminate\Support\Facades\File::exists($configFile)) {
        throw new \Exception("Profile file does not exists: {$configFile}");
    }

    return config("{$profile}.{$key}");
}

function get_app_name()
{
    $name = Setting::get('site_name') ?: config('app.name');
    return $name;
}

function get_tmp_primary_server()
{
    if (config('app.saas')) {
        throw new \Exception('Use in non-SAAS mode only');
    }

    if (SendingServer::active()->count()) {
        return SendingServer::active()->first()->mapType();
    } else {
        return null;
    }
}

function get_tmp_quota($customer, $name)
{
    $settings = [
        "email_max" => "-1",
        "list_max" => "-1",
        "subscriber_max" => "-1",
        "subscriber_per_list_max" => "-1",
        "segment_per_list_max" => "-1",
        "campaign_max" => "-1",
        "automation_max" => "-1",
        "max_process" => "1",
        "max_size_upload_total" => "5000000000",
        "max_file_size_upload" => "50000000",
        "unsubscribe_url_required" => "no",
        "access_when_offline" => "no",
        "sending_servers_max" => "-1",
        "sending_domains_max" => "-1",
        "all_email_verification_servers" => "yes",
        "email_verification_servers_max" => "-1",
        "list_import" => "yes",
        "list_export" => "yes",
        "all_sending_server_types" => "yes",
        "api_access" => "yes",
        "plain_text_footer" => "no",
        "html_footer" => "",
    ];

    if (config('app.saas')) {
        return $customer->getNewOrActiveGeneralSubscription()->planGeneral->getOption($name);
    } else {
        if (!array_key_exists($name, $settings)) {
            throw new \Exception("Key '{$name}' not listed");
        }
        return $settings[$name];
    }

}
