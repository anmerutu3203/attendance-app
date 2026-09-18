<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'admin_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'admin_status' => 'boolean',
    ];

    protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'admin_status' => 'boolean',
    ];
}

public function attendanceRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(AttendanceRecord::class);
}

public function attendanceCorrectionRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(AttendanceCorrectionRequest::class);
}

/**
 * 今日の勤怠ステータス（勤務外/出勤中/休憩中/退勤済）を返す
 */
public function getAttendanceStatusAttribute(): string
{
    $record = $this->attendanceRecords()
        ->whereDate('date', today())
        ->with('breaks')
        ->first();

    return $record?->status ?? '勤務外';
}
}
