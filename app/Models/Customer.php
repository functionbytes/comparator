<?php

namespace App\Models;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasUid;

    protected $table = "customers";

    protected $fillable = [
        'uid',
        'firstname',
        'lastname',
        'email',
        'available',
        'customer',
        'management',
        'subscriber_id',
        'birthday_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query, $uid)
    {
        return $query->where('uid', $uid)->first();
    }

}
