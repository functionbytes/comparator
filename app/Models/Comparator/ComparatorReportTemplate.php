<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComparatorReportTemplate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comparator_report_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',       // Asumido
        'schedule'    // Asumido
    ];

    /**
     * Get the price thresholds for the report template.
     */
    public function priceThresholds(): HasMany
    {
        return $this->hasMany(ComparatorPriceThreshold::class, 'template_id');
    }
}
