<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceCorrectionRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attendance_record_id' => AttendanceRecord::factory(),
            'user_id' => User::factory(),
            'requested_clock_in' => '09:00:00',
            'requested_clock_out' => '19:00:00',
            'requested_comment' => '電車遅延のため',
            'status' => 0,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 1,
            'approved_at' => now(),
        ]);
    }
}