<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_daily_list_shows_todays_attendance_for_every_staff_member(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create(['admin_status' => false, 'name' => '山田太郎']);
        AttendanceRecord::factory()->create([
            'user_id' => $staff->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/attendance/list');

        $response->assertOk();
        $response->assertSee('山田太郎');
    }

    public function test_the_staff_list_shows_general_users_only(): void
    {
        $admin = User::factory()->create(['admin_status' => true, 'name' => '管理者']);
        $staff = User::factory()->create(['admin_status' => false, 'name' => 'スタッフ花子']);

        $response = $this->actingAs($admin, 'admin')->get('/admin/staff/list');

        $response->assertOk();
        $response->assertSee('スタッフ花子');
        $response->assertDontSee('管理者');
    }

    public function test_the_staff_monthly_detail_shows_their_records(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create();
        AttendanceRecord::factory()->create([
            'user_id' => $staff->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/attendance/staff/' . $staff->id);

        $response->assertOk();
    }

    public function test_csv_export_downloads_the_selected_months_attendance(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $staff = User::factory()->create();
        AttendanceRecord::factory()->create([
            'user_id' => $staff->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')->post('/export', [
            'user_id' => $staff->id,
            'year_month' => today()->format('Y-m'),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('日付,出勤,退勤,休憩,合計', $response->streamedContent());
    }

    public function test_admin_only_screens_are_blocked_for_general_users(): void
    {
        $user = User::factory()->create(['admin_status' => false]);

        $response = $this->actingAs($user)->get('/admin/staff/list');

        $response->assertRedirect('/login');
    }
}
