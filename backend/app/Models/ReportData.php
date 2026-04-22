<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportData extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'report_request_id',
        'date',
        'rate',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'rate' => 'float',
        ];
    }

    public function reportRequest(): BelongsTo
    {
        return $this->belongsTo(ReportRequest::class);
    }
}
