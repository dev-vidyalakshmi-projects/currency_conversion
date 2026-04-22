<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportRequest extends Model
{
    //
    use HasFactory;

    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_FAILED     = 'failed';

    const RANGE_ONE_YEAR    = 'one_year';
    const RANGE_SIX_MONTHS  = 'six_months';
    const RANGE_ONE_MONTH   = 'one_month';

    const INTERVAL_MONTHLY  = 'monthly';
    const INTERVAL_WEEKLY   = 'weekly';
    const INTERVAL_DAILY    = 'daily';

    protected $fillable = [
        'user_id',
        'currency_code',
        'range',
        'interval',
        'status',
        'error_message',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportData(): HasMany
    {
        return $this->hasMany(ReportData::class)->orderBy('date');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public static function validCombinations(): array
    {
        return [
            self::RANGE_ONE_YEAR   => self::INTERVAL_MONTHLY,
            self::RANGE_SIX_MONTHS => self::INTERVAL_WEEKLY,
            self::RANGE_ONE_MONTH  => self::INTERVAL_DAILY,
        ];
    }
}
