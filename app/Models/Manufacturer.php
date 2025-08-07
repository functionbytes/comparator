<?php

namespace App\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Manufacturer extends Model
{

    use HasUid;

    protected $table = "manufacturers";

    protected $fillable = [
        'title',
        'uid',
        'available',
        'created_at',
        'updated_at'
    ];

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeUid($query, $uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    public function products(): HasMany
    {
        // manufacturer_id se encuentra en products
        return $this->hasMany(Product::class);
    }


}
