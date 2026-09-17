<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'date', 'clock_in', 'clock_out', 'comment'];

    protected $casts = [
    'date' => 'date',
];

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

    /**
     * 休憩の合計時間を "H:i:s" 形式で返す（休憩なしはnull）
     */
    public function getTotalBreakTimeAttribute(): ?string
    {
        $totalSeconds = $this->breaks->reduce(function (int $carry, AttendanceBreak $break) {
            if ($break->break_in && $break->break_out) {
                $carry += Carbon::parse($break->break_in)->diffInSeconds(Carbon::parse($break->break_out));
            }
            return $carry;
        }, 0);

        return $totalSeconds > 0 ? gmdate('H:i:s', $totalSeconds) : null;
    }

    /**
     * 実労働時間（出勤〜退勤 - 休憩合計）を "H:i:s" 形式で返す（未退勤はnull）
     */
    public function getTotalTimeAttribute(): ?string
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return null;
        }

        $workSeconds = Carbon::parse($this->clock_in)->diffInSeconds(Carbon::parse($this->clock_out));
        $breakSeconds = $this->total_break_time
            ? Carbon::parse($this->total_break_time)->diffInSeconds(Carbon::parse('00:00:00'))
            : 0;

        return gmdate('H:i:s', max(0, $workSeconds - $breakSeconds));
    }

    /**
     * 出勤打刻。勤務外の場合のみ有効。
     */
    public function clockIn(string $time): bool
    {
        if ($this->status !== '勤務外') {
            return false;
        }
        return $this->update(['clock_in' => $time]);
    }

    /**
     * 退勤打刻。出勤中の場合のみ有効（休憩中は不可）。
     */
    public function clockOut(string $time): bool
    {
        if ($this->status !== '出勤中') {
            return false;
        }
        return $this->update(['clock_out' => $time]);
    }

    /**
     * 休憩入。出勤中の場合のみ有効。
     */
    public function startBreak(string $time): bool
    {
        if ($this->status !== '出勤中') {
            return false;
        }
        $this->breaks()->create(['break_in' => $time]);
        return true;
    }

    /**
     * 休憩戻。休憩中の場合のみ有効。
     */
    public function endBreak(string $time): bool
    {
        if ($this->status !== '休憩中') {
            return false;
        }
        $openBreak = $this->breaks()->whereNull('break_out')->latest('id')->first();
        return (bool) $openBreak?->update(['break_out' => $time]);
    }
}