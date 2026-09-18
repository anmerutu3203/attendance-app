<?php

namespace Tests\Unit;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_is_off_duty_when_not_clocked_in(): void
    {
        $record = AttendanceRecord::factory()->create(['clock_in' => null, 'clock_out' => null]);

        $this->assertSame('勤務外', $record->status);
    }

    public function test_status_is_working_after_clock_in(): void
    {
        $record = AttendanceRecord::factory()->create(['clock_in' => '09:00:00', 'clock_out' => null]);

        $this->assertSame('出勤中', $record->status);
    }

    public function test_status_is_on_break_when_break_has_no_break_out(): void
    {
        $record = AttendanceRecord::factory()->create(['clock_in' => '09:00:00', 'clock_out' => null]);
        $record->breaks()->create(['break_in' => '12:00:00', 'break_out' => null]);

        $this->assertSame('休憩中', $record->fresh(['breaks'])->status);
    }

    public function test_status_is_finished_after_clock_out(): void
    {
        $record = AttendanceRecord::factory()->create(['clock_in' => '09:00:00', 'clock_out' => '18:00:00']);

        $this->assertSame('退勤済', $record->status);
    }

    public function test_total_time_subtracts_total_break_time_from_clock_range(): void
    {
        $record = AttendanceRecord::factory()->create(['clock_in' => '09:00:00', 'clock_out' => '18:00:00']);
        $record->breaks()->create(['break_in' => '12:00:00', 'break_out' => '13:00:00']);
        $record->breaks()->create(['break_in' => '15:00:00', 'break_out' => '15:15:00']);

        $record->refresh();

        $this->assertSame('01:15:00', $record->total_break_time);
        $this->assertSame('07:45:00', $record->total_time);
    }

    public function test_total_time_is_null_when_not_clocked_out(): void
    {
        $record = AttendanceRecord::factory()->create(['clock_in' => '09:00:00', 'clock_out' => null]);

        $this->assertNull($record->total_time);
    }

    public function test_clock_in_only_succeeds_when_off_duty(): void
    {
        $record = AttendanceRecord::factory()->create(['clock_in' => null, 'clock_out' => null]);

        $this->assertTrue($record->clockIn('09:00:00'));
        $this->assertFalse($record->clockIn('09:30:00'));
    }

    public function test_clock_out_only_succeeds_while_working(): void
    {
        $record = AttendanceRecord::factory()->create(['clock_in' => null, 'clock_out' => null]);

        $this->assertFalse($record->clockOut('18:00:00'));

        $record->clockIn('09:00:00');
        $this->assertTrue($record->clockOut('18:00:00'));
    }

    public function test_user_admin_status_is_cast_to_boolean(): void
    {
        $admin = User::factory()->create(['admin_status' => 1]);
        $staff = User::factory()->create(['admin_status' => 0]);

        $this->assertTrue($admin->admin_status);
        $this->assertFalse($staff->admin_status);
    }

    public function test_user_has_many_attendance_records(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()
            ->count(3)
            ->sequence(fn ($sequence) => ['date' => today()->subDays($sequence->index)])
            ->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->attendanceRecords);
    }
}
