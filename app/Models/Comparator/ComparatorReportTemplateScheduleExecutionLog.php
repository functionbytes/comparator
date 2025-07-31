<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComparatorReportTemplateScheduleExecutionLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comparator_report_templates_schedules_execution_logs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'report_id',
        'level',
        'message',
        'context',
        'source',
        'logged_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
        'logged_at' => 'datetime',
    ];

    /**
     * Get the report that owns the log entry.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(ComparatorReport::class, 'report_id');
    }
}
