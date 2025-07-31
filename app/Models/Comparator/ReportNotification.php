<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportNotification extends Model
{
    protected $table = 'comparator_report_notifications';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'template_id',
        'channel',
        'recipients',
        'conditions',
        'trigger',
        'is_active',
    ];

    protected $casts = [
        'recipients' => 'json',
        'conditions' => 'json',
        'is_active' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\ReportTemplate', 'template_id');
    }

}
