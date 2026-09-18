<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStampTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_full_day_can_be_stamped_in_order(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/attendance', ['action' => 'clock_in'])
            ->assertRedirect('/attendance')
            ->assertSessionHas('status', '打刻しました。');

        $this->post('/attendance', ['action' => 'break_in'])
            ->assertSessionHas('status', '打刻しました。');

        $this->post('/attendance', ['action' => 'break_out'])
            ->assertSessionHas('status', '打刻しました。');

        $this->post('/attendance', ['action' => 'clock_out'])
            ->assertSessionHas('status', '打刻しました。');

        $record = $user->attendanceRecords()->whereDate('date', today())->with('breaks')->first();
        $this->assertNotNull($record->clock_in);
        $this->assertNotNull($record->clock_out);
        $this->assertCount(1, $record->breaks);
        $this->assertSame('退勤済', $record->status);
    }

    public function test_clocking_out_before_clocking_in_fails(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/attendance', ['action' => 'clock_out'])
            ->assertSessionHas('error', '現在の状態ではその操作はできません。');
    }

    public function test_an_invalid_action_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/attendance', ['action' => 'not_a_real_action']);

        $response->assertSessionHasErrors('action');
    }

    public function test_stamping_requires_authentication(): void
    {
        $response = $this->post('/attendance', ['action' => 'clock_in']);

        $response->assertRedirect('/login');
    }
}
