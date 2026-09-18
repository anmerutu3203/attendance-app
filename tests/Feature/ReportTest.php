<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_matches_the_seeded_dummy_data_for_user1(): void
    {
        // Issue #13 のダミーデータの想定値：
        // 総労働744時間・総残業10時間・平均8時間5分・遅刻2回・早退1回・長時間労働1日
        $this->seed();
        $user1 = User::where('email', 'user1@example.com')->firstOrFail();

        $response = $this->actingAs($user1)->get('/attendance/report');

        $response->assertOk();
        $response->assertSeeInOrder(['744:00', '10:00', '8:05']);
        $response->assertSee('2回');
        $response->assertSee('1回');
        $response->assertSee('1日');
    }

    public function test_a_user_with_no_attendance_data_sees_all_zeros(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertOk();
        $response->assertSee('0:00');
        $response->assertSee('0回');
        $response->assertSee('0日');
    }

    public function test_the_report_requires_authentication(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }
}
