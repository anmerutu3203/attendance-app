<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_a_request_applies_it_to_the_attendance_record(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => null,
        ]);
        $application = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $record->id,
            'requested_clock_in' => '09:30:00',
            'requested_clock_out' => '18:30:00',
            'requested_comment' => '電車遅延のため',
            'status' => 0,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/stamp_correction_request/approve/' . $application->id);

        $response->assertRedirect('/stamp_correction_request/approve/' . $application->id);

        $record->refresh();
        $this->assertSame('09:30:00', $record->clock_in);
        $this->assertSame('18:30:00', $record->clock_out);
        $this->assertSame('電車遅延のため', $record->comment);
        $this->assertSame(1, $application->fresh()->status);
        $this->assertNotNull($application->fresh()->approved_at);
    }

    public function test_a_general_user_cannot_approve_requests(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);
        $application = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $record->id,
        ]);

        $response = $this->actingAs($user)
            ->post('/stamp_correction_request/approve/' . $application->id);

        $response->assertRedirect('/login');
    }
}
