<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $table = 'providers';
    // PK autoincremental nativa: id
    protected $fillable = ['uid', 'title', 'code', 'available'];

    // Sugerencia: si sueles tratar "code" como id externo, crea un scope
    public function scopeCode($q, string $code)
    {
        return $q->where('code', $code);
    }
}