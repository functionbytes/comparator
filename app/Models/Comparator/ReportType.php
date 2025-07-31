<?php

namespace App\Models\Comparator;

use Illuminate\Database\Eloquent\Model;

class ReportType extends Model
{
    protected $table = 'comparator_reports_types';

    protected $fillable = [
        'code',
        'title',
        'description',
        'available',
    ];

    protected $casts = [
        'available' => 'integer',
    ];

}
