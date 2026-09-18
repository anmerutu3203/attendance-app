<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_list_shows_records_for_the_current_month(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertOk();
        $response->assertSee(today()->format('Y/m/d'));
    }

    public function test_a_month_with_no_records_still_renders_without_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertOk();
    }

    public function test_the_list_requires_authentication(): void
    {
        $response = $this->get('/attendance/list');

        $response->assertRedirect('/login');
    }
}
