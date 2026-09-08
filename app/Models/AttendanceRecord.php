<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'date', 'clock_in', 'clock_out', 'comment'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    /**
     * 勤務外 / 出勤中 / 休憩中 / 退勤済 を算出する
     */
    public function getStatusAttribute(): string
    {
        if (is_null($this->clock_in)) {
            return '勤務外';
        }
        if (! is_null($this->clock_out)) {
            return '退勤済';
        }
        $openBreak = $this->breaks->firstWhere('break_out', null);
        if ($openBreak) {
            return '休憩中';
        }

        return '出勤中';
    }
}