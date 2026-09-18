<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailAndCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_view_their_own_attendance_detail(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/attendance/' . $record->id);

        $response->assertOk();
    }

    public function test_a_user_cannot_view_another_users_attendance_detail(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($otherUser)->get('/attendance/' . $record->id);

        $response->assertForbidden();
    }

    public function test_an_admin_can_view_any_users_attendance_detail(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $owner = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($admin)->get('/attendance/' . $record->id);

        $response->assertOk();
    }

    public function test_a_general_user_submitting_a_correction_creates_a_pending_request(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/' . $record->id, [
            'new_clock_in' => '09:30',
            'new_clock_out' => '18:30',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['13:00'],
            'comment' => '電車遅延のため',
        ]);

        $response->assertRedirect('/attendance/' . $record->id);
        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_record_id' => $record->id,
            'user_id' => $user->id,
            'status' => 0,
        ]);
        // 申請はまだ承認されていないので、勤怠自体は変更されない
        $this->assertSame('09:00:00', $record->fresh()->clock_in);
    }

    public function test_a_record_with_a_pending_request_cannot_be_edited_again(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);
        $record->correctionRequests()->create([
            'user_id' => $user->id,
            'requested_clock_in' => '09:30:00',
            'requested_clock_out' => '18:30:00',
            'requested_comment' => '既存の申請',
            'status' => 0,
        ]);

        $response = $this->actingAs($user)->post('/attendance/' . $record->id, [
            'new_clock_in' => '10:00',
            'new_clock_out' => '19:00',
            'comment' => '再申請',
        ]);

        $response->assertSessionHas('error', '承認待ちのため修正はできません。');
    }

    public function test_clock_out_must_be_after_clock_in(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        $response = $this->from('/attendance/' . $record->id)->actingAs($user)->post('/attendance/' . $record->id, [
            'new_clock_in' => '19:00',
            'new_clock_out' => '09:00',
            'comment' => '不正な時間',
        ]);

        $response->assertSessionHasErrors('new_clock_out');
    }
}
