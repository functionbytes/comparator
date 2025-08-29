<?php
// app/Models/Competitor.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Competitor extends Model
{
    protected $table = 'competitors';

    protected $fillable = [
        'uid',
        'title',
        'iso_code',
        'available',
    ];

    protected $casts = [
        'available' => 'integer',
    ];

    // Genera UID automáticamente si no se envía
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    // Scope útil si quieres por ISO
    public function scopeIso($q, string $iso)
    {
        return $q->where('iso_code', strtoupper($iso));
    }
}
