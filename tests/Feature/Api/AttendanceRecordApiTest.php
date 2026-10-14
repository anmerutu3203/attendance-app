<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_to_write_endpoints_return_401(): void
    {
        $record = AttendanceRecord::factory()->create();

        $this->postJson('/api/v1/attendance-records', [])
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Unauthenticated.']);
        $this->putJson("/api/v1/attendance-records/{$record->id}", ['comment' => 'x'])
            ->assertStatus(401);
        $this->deleteJson("/api/v1/attendance-records/{$record->id}")
            ->assertStatus(401);
    }

    public function test_a_user_cannot_update_or_delete_another_users_record(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $this->putJson("/api/v1/attendance-records/{$record->id}", ['comment' => 'hack'])
            ->assertStatus(403)
            ->assertJson(['error' => 'この操作を実行する権限がありません。']);

        $this->deleteJson("/api/v1/attendance-records/{$record->id}")
            ->assertStatus(403);
    }

    public function test_the_owner_can_update_their_own_record(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->putJson("/api/v1/attendance-records/{$record->id}", ['comment' => '本人による更新'])
            ->assertOk()
            ->assertJsonPath('data.comment', '本人による更新');
    }

    public function test_an_admin_can_update_any_users_record(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $owner = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/attendance-records/{$record->id}", ['comment' => '管理者による更新'])
            ->assertOk()
            ->assertJsonPath('data.comment', '管理者による更新');
    }

    public function test_updating_a_missing_record_returns_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/attendance-records/999999', ['comment' => 'x'])
            ->assertStatus(404)
            ->assertJson(['error' => '勤怠情報が見つかりませんでした。']);
    }

    public function test_index_returns_paginated_data_and_meta(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()
            ->count(25)
            ->sequence(fn ($sequence) => ['date' => today()->subDays($sequence->index)])
            ->create(['user_id' => $user->id]);

        // 読み取り系は認証不要
        $response = $this->getJson('/api/v1/attendance-records?per_page=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.last_page', 3);
        // 新しい日付順で、ユーザー名付き。修正申請は一覧では出力されない
        $response->assertJsonPath('data.0.date', today()->format('Y-m-d'));
        $response->assertJsonPath('data.0.user_name', $user->name);
        $response->assertJsonMissingPath('data.0.applications');
    }

    public function test_index_defaults_to_20_per_page(): void
    {
        $this->getJson('/api/v1/attendance-records')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 20);
    }

    public function test_index_rejects_a_per_page_over_the_maximum(): void
    {
        $this->getJson('/api/v1/attendance-records?per_page=101')
            ->assertStatus(422);
    }

    public function test_index_can_filter_by_user_id_and_month(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-05-10']);
        AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-06-10']);
        AttendanceRecord::factory()->create(['user_id' => $otherUser->id, 'date' => '2026-05-10']);

        $response = $this->getJson("/api/v1/attendance-records?user_id={$user->id}&month=2026-05");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.date', '2026-05-10');
    }

    public function test_index_can_filter_by_date(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-05-10']);
        AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-05-11']);

        $this->getJson('/api/v1/attendance-records?date=2026-05-11')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.date', '2026-05-11');
    }

    public function test_show_includes_user_breaks_applications_and_total_times(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $record->breaks()->create(['break_in' => '12:00:00', 'break_out' => '13:00:00']);
        $record->correctionRequests()->create(['user_id' => $user->id, 'requested_comment' => '修正お願いします']);

        $response = $this->getJson("/api/v1/attendance-records/{$record->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data.breaks');
        $response->assertJsonPath('data.breaks.0.break_in', '12:00:00');
        $response->assertJsonPath('data.user.name', $user->name);
        $response->assertJsonPath('data.applications.0.requested_comment', '修正お願いします');
        $response->assertJsonPath('data.total_time', '08:00');
        $response->assertJsonPath('data.total_break_time', '01:00');
    }

    public function test_show_returns_404_for_a_missing_record(): void
    {
        $this->getJson('/api/v1/attendance-records/999999')
            ->assertStatus(404)
            ->assertJson(['error' => '勤怠情報が見つかりませんでした。']);
    }

    public function test_store_creates_a_record_and_returns_201(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-01-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'テスト',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.user_name', $user->name);
        $response->assertJsonPath('data.breaks', []);
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'comment' => 'テスト',
        ]);
    }

    public function test_store_allows_omitting_clock_out(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/attendance-records', ['date' => '2026-01-15', 'clock_in' => '09:00:00'])
            ->assertCreated()
            ->assertJsonPath('data.clock_out', null);
    }

    public function test_store_ignores_user_id_and_uses_the_authenticated_user(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $otherUser = User::factory()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/attendance-records', [
            'user_id' => $otherUser->id,
            'date' => '2026-01-15',
            'clock_in' => '09:00:00',
        ])->assertCreated()->assertJsonPath('data.user_id', $admin->id);
    }

    public function test_store_returns_japanese_validation_messages(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/attendance-records', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'date' => '勤怠日は必須です。',
                'clock_in' => '出勤時刻は必須です。',
            ]);

        $this->postJson('/api/v1/attendance-records', [
            'date' => '2026/01/15',
            'clock_in' => '09:00',
            'clock_out' => '18時',
            'comment' => str_repeat('a', 256),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'date' => '勤怠日は YYYY-MM-DD 形式で指定してください。',
                'clock_in' => '出勤時刻は HH:MM:SS 形式で指定してください。',
                'clock_out' => '退勤時刻は HH:MM:SS 形式で指定してください。',
                'comment' => '備考は 255 文字以内で入力してください。',
            ]);
    }

    public function test_store_rejects_clock_out_before_clock_in(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-01-15',
            'clock_in' => '19:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['clock_out' => '退勤時刻は出勤時刻より後の時刻を指定してください。']);
    }

    public function test_store_rejects_a_duplicate_date_for_the_same_user(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-01-15']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-01-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date' => 'この日付の勤怠は既に登録されています。']);
    }

    public function test_update_supports_partial_fields(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/attendance-records/{$record->id}", [
            'comment' => '部分更新のみ',
        ]);

        $response->assertOk();
        $this->assertSame('部分更新のみ', $record->fresh()->comment);
        $this->assertSame('09:00:00', $record->fresh()->clock_in);
    }

    public function test_update_rejects_clock_out_before_the_existing_clock_in(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id, 'clock_in' => '09:00:00']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/attendance-records/{$record->id}", ['clock_out' => '08:00:00'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['clock_out' => '退勤時刻は出勤時刻より後の時刻を指定してください。']);
    }

    public function test_update_keeping_the_same_date_does_not_trigger_the_unique_rule(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-01-15']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/attendance-records/{$record->id}", ['date' => '2026-01-15'])
            ->assertOk();
    }

    public function test_update_to_a_date_already_used_by_another_record_fails(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-01-10']);
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-01-15']);
        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/attendance-records/{$record->id}", ['date' => '2026-01-10'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date');
    }

    public function test_destroy_removes_the_record_and_returns_204(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/attendance-records/{$record->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('attendance_records', ['id' => $record->id]);
    }

    public function test_destroying_a_missing_record_returns_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson('/api/v1/attendance-records/999999')
            ->assertStatus(404);
    }
}
