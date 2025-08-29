<?php

// app/Models/PriceChange.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceChange extends Model
{
    protected $fillable = [
        'referencia',
        'precio_con_iva',
        'nuevo_precio_con_iva',
        'source',
        'user_id',
        'ip',
        'contexto',
    ];

    protected $casts = [
        'precio_con_iva'       => 'decimal:4',
        'nuevo_precio_con_iva' => 'decimal:4',
        'contexto'             => 'array',
    ];
}

