<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationListTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_general_user_sees_only_their_own_applications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownRecord = AttendanceRecord::factory()->create(['user_id' => $user->id]);
        $otherRecord = AttendanceRecord::factory()->create(['user_id' => $otherUser->id]);

        AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $ownRecord->id,
            'requested_comment' => '自分の申請',
        ]);
        AttendanceCorrectionRequest::factory()->create([
            'user_id' => $otherUser->id,
            'attendance_record_id' => $otherRecord->id,
            'requested_comment' => '他人の申請',
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertOk();
        $response->assertSee('自分の申請');
        $response->assertDontSee('他人の申請');
    }

    public function test_an_admin_sees_every_users_applications(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $record->id,
            'requested_comment' => '一般ユーザーの申請',
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');

        $response->assertOk();
        $response->assertSee('一般ユーザーの申請');
    }
}
