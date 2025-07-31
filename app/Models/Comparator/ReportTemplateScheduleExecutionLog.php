<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplateScheduleExecutionLog extends Model
{
    protected $table = 'comparator_report_templates_schedules_execution_logs';
    public const CREATED_AT = 'logged_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'report_id',
        'level',
        'message',
        'context',
        'source',
    ];

    protected $casts = [
        'context' => 'json',
        'logged_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\Report', 'report_id');
    }

}
