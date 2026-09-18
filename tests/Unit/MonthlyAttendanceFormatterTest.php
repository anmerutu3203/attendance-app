<?php

namespace Tests\Unit;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\MonthlyAttendanceFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonthlyAttendanceFormatterTest extends TestCase
{
    use RefreshDatabase;

    public function test_days_without_a_record_are_blank(): void
    {
        $month = Carbon::create(2026, 6, 1);
        $formatted = (new MonthlyAttendanceFormatter())->format($month, collect());

        $this->assertCount($month->daysInMonth, $formatted);

        $firstDay = $formatted->first();
        $this->assertSame('2026/06/01', $firstDay['date']);
        $this->assertSame('', $firstDay['clock_in']);
        $this->assertSame('', $firstDay['clock_out']);
        $this->assertNull($firstDay['id']);
    }

    public function test_days_with_a_record_are_filled_in(): void
    {
        $month = Carbon::create(2026, 6, 1);
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-06-05',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $record->breaks()->create(['break_in' => '12:00:00', 'break_out' => '13:00:00']);

        $records = $user->attendanceRecords()->with('breaks')->get()
            ->keyBy(fn (AttendanceRecord $r) => $r->date->format('Y-m-d'));

        $formatted = (new MonthlyAttendanceFormatter())->format($month, $records)
            ->firstWhere('date', '2026/06/05');

        $this->assertSame($record->id, $formatted['id']);
        $this->assertSame('09:00', $formatted['clock_in']);
        $this->assertSame('18:00', $formatted['clock_out']);
    }
}
