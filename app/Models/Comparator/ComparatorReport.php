<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ComparatorReport extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comparator_reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'template_id',
        'name',
        'status',
        'executed_at'
    ];

    /**
     * Get the analytics associated with the report.
     */
    public function analytics(): HasOne
    {
        return $this->hasOne(ComparatorReportAnalytics::class, 'report_id');
    }

    /**
     * Get the product snapshots for the report.
     */
    public function productSnapshots(): HasMany
    {
        return $this->hasMany(ComparatorReportProductSnapshot::class, 'report_id');
    }

    /**
     * Get the price history for the report.
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(ComparatorReportPriceHistory::class, 'report_id');
    }

    /**
     * Get the execution logs for the report.
     */
    public function executionLogs(): HasMany
    {
        return $this->hasMany(ComparatorReportTemplateScheduleExecutionLog::class, 'report_id');
    }

    /**
     * @param string[] $fillable
     * @return ComparatorReport
     */
    public function setFillable(array $fillable): ComparatorReport
    {
        $this->fillable = $fillable;
        return $this;
    }
}
