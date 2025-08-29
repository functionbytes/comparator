<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductPortes extends Model
{
    public $timestamps   = false;
    public $incrementing = false;

    // Usa la nueva conexión
    protected $connection = 'prestashop';

    protected $table = 'aalv_portes_producto';
    protected $guarded = [];

    public static function getImporte(string $reference, string $isoCode): float
    {
        $row = DB::connection('prestashop')->table('aalv_portes_producto as app')
            ->leftJoin('aalv_portes as ap', 'ap.codigo', '=', 'app.codigo')
            ->leftJoin('aalv_country as ac', 'ac.id_country', '=', 'app.id_pais')
            ->where('app.referencia', $reference)
            ->whereRaw('LOWER(ac.iso_code) = ?', [mb_strtolower($isoCode)])
            ->select('ap.importe')
            ->first();

        return isset($row->importe) ? (float)$row->importe : 0.0;
    }
}
