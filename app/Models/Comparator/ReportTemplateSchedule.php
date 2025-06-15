<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplateSchedule extends Model
{
    protected $table = 'comparator_report_templates_schedules';

    protected $fillable = [
        'template_id',
        'frequency',
        'time_slots',
        'day_of_week',
        'day_of_month',
        'available',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'time_slots' => 'json',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'available' => 'integer',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\ReportTemplate', 'template_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany('App\Models\Comparator\ReportTemplateScheduleExecution', 'schedule_id');
    }

}
