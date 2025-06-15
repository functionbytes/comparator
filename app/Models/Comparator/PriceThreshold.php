<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceThreshold extends Model
{
    protected $table = 'comparator_price_thresholds';

    protected $fillable = [
        'template_id',
        'type',
        'min_threshold',
        'max_threshold',
        'action',
    ];

    protected $casts = [
        'min_threshold' => 'decimal:2',
        'max_threshold' => 'decimal:2',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo('App\Models\Comparator\ReportTemplate', 'template_id');
    }

}
