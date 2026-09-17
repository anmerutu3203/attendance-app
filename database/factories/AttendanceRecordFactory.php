<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => null,
        ];
    }
}