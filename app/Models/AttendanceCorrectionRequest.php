<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_record_id', 'user_id',
        'requested_clock_in', 'requested_clock_out', 'requested_comment',
        'status', 'approved_at',
    ];

    protected $casts = [
    'approved_at' => 'datetime',
];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestBreaks(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequestBreak::class);
    }

    /**
     * admin-application-list / admin-application-detail の各ビューが
     * 期待するプロパティ名に合わせたアクセサ群（ビュー側は変更しない）。
     */
    public function getApprovalStatusAttribute(): string
    {
        return $this->status === 1 ? '承認済み' : '承認待ち';
    }

    public function getCommentAttribute(): string
    {
        return $this->requested_comment;
    }

    public function getApplicationDateAttribute(): Carbon
    {
        return $this->created_at;
    }

    public function getNewDateAttribute(): Carbon
    {
        return $this->attendanceRecord->date;
    }

    public function getNewClockInAttribute(): string
    {
        return Carbon::parse($this->requested_clock_in)->format('H:i');
    }

    public function getNewClockOutAttribute(): string
    {
        return Carbon::parse($this->requested_clock_out)->format('H:i');
    }

    public function getProposalBreaksAttribute(): Collection
    {
        return $this->requestBreaks;
    }
}