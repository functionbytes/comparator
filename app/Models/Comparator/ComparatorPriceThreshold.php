<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComparatorPriceThreshold extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comparator_price_thresholds';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'template_id',
        'type',
        'min_threshold',
        'max_threshold',
        'action',
    ];

    /**
     * Get the report template that owns the price threshold.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ComparatorReportTemplate::class, 'template_id');
    }
}
