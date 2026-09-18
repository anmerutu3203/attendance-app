<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use Illuminate\Database\Seeder;

class BreakSeeder extends Seeder
{
    public function run(): void
    {
        AttendanceRecord::each(function (AttendanceRecord $record) {
            $record->breaks()->create([
                'break_in' => '12:00:00',
                'break_out' => '13:00:00',
            ]);
        });
    }
}
