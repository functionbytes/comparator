<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplateScheduleExecution extends Model
{
    protected $table = 'comparator_report_templates_schedules_executions';

    protected $fillable = [
        'schedule_id',
        'executed_at',
        'next_execution_at',
        'status',
        'reports_generated',
        'error_log',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'next_execution_at' => 'datetime',
        'reports_generated' => 'integer',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\ReportTemplateSchedule', 'schedule_id');
    }
}
