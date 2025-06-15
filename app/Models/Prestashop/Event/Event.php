<?php

namespace App\Models\Prestashop\Event;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class Event extends Model
{

   // use HasFactory, SoftDeletes, LogsActivity;

    protected $connection = 'prestashop';

    protected $table = "aalv_alsernet_event_manager";

    //protected static $recordEvents = ['deleted','updated','created'];

    protected $fillable = [
        'uid',
        'title',
        'color_flag',
        'filter_flag',
        'management_flag',
        'priority_flag',
        'color_buttom',
        'hover_buttom',
        'cms',
        'featured',
        'amazing',
        'available',
        'completed',
        'iva',
        'processing',
        'processed',
        'banners_unique',
        'banners',
        'banners_backup',
        'start_at',
        'end_at',
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


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty() ->logFillable() ->setDescriptionForEvent(fn(string $eventName) => "This model has been {$eventName}");
    }

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }


    public function categories()
    {
        return $this->hasMany('App\Models\Prestashop\Event\EventCategory');
    }

    public function banners()
    {
        return $this->hasMany('App\Models\Prestashop\Event\EventCategory');
    }

    public function backups()
    {
        return $this->hasMany('App\Models\Prestashop\Event\EventCategory');
    }

}

