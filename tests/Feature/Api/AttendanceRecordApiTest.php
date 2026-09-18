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
            ->assertJson(['error' => 'この操作を行う権限がありません。']);

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

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/attendance-records?per_page=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonMissingPath('data.0.breaks');
    }

    public function test_index_rejects_a_per_page_over_the_maximum(): void
    {
        Sanctum::actingAs(User::factory()->create());

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

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/attendance-records?user_id={$user->id}&month=2026-05");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.date', '2026-05-10');
    }

    public function test_show_includes_breaks(): void
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id]);
        $record->breaks()->create(['break_in' => '12:00:00', 'break_out' => '13:00:00']);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/attendance-records/{$record->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data.breaks');
        $response->assertJsonPath('data.breaks.0.break_in', '12:00:00');
    }

    public function test_show_returns_404_for_a_missing_record(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/attendance-records/999999')
            ->assertStatus(404)
            ->assertJson(['error' => '勤怠情報が見つかりませんでした。']);
    }

    public function test_store_creates_a_record_and_returns_201(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => 'テスト',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'comment' => 'テスト',
        ]);
    }

    public function test_store_rejects_clock_out_before_clock_in(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'clock_in' => '19:00',
            'clock_out' => '18:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('clock_out');
    }

    public function test_store_rejects_a_duplicate_date_for_the_same_user(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-01-15']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'user_id' => $user->id,
            'date' => '2026-01-15',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('date');
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
