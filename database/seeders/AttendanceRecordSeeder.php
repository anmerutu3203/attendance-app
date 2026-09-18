<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceRecordSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUser1(User::where('email', 'user1@example.com')->firstOrFail());
        $this->seedRegular(User::where('email', 'user2@example.com')->firstOrFail(), months: 2, take: 40);
    }

    /**
     * user1限定：過去5ヶ月の通常勤務75日＋当月17日分の意図的パターン。
     * 全日固定休憩12:00-13:00（BreakSeederで付与）を前提に、
     * 総労働744時間・総残業10時間・遅刻2回・早退1回・長時間労働1日となるよう設計。
     */
    private function seedUser1(User $user): void
    {
        foreach ($this->weekdaysBeforeCurrentMonth(months: 5, take: 75) as $date) {
            $user->attendanceRecords()->create([
                'date' => $date,
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ]);
        }

        $patterns = [
            ['09:20:00', '18:00:00'], // 遅刻
            ['09:40:00', '18:00:00'], // 遅刻
            ['09:00:00', '17:00:00'], // 早退
            ['09:00:00', '21:00:00'], // 長時間労働（残業3時間）
            ['09:00:00', '19:00:00'], // 残業1時間
            ['09:00:00', '19:00:00'],
            ['09:00:00', '19:00:00'],
            ['09:00:00', '19:00:00'],
            ['09:00:00', '19:00:00'],
            ['09:00:00', '19:00:00'],
            ['09:00:00', '19:00:00'],
            ['09:00:00', '18:00:00'], // 通常
            ['09:00:00', '18:00:00'],
            ['09:00:00', '18:00:00'],
            ['09:00:00', '18:00:00'],
            ['09:00:00', '18:00:00'],
            ['09:00:00', '18:00:00'],
        ];

        foreach ($this->weekdaysInCurrentMonth(take: 17) as $index => $date) {
            [$clockIn, $clockOut] = $patterns[$index];

            $user->attendanceRecords()->create([
                'date' => $date,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
            ]);
        }
    }

    private function seedRegular(User $user, int $months, int $take): void
    {
        foreach ($this->weekdaysBeforeCurrentMonth($months, $take) as $date) {
            $user->attendanceRecords()->create([
                'date' => $date,
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ]);
        }
    }

    /**
     * @return Collection<int, Carbon>
     */
    private function weekdaysInCurrentMonth(int $take): Collection
    {
        return $this->weekdaysBetween(now()->startOfMonth(), now()->endOfMonth(), $take);
    }

    /**
     * @return Collection<int, Carbon>
     */
    private function weekdaysBeforeCurrentMonth(int $months, int $take): Collection
    {
        $start = now()->startOfMonth()->subMonths($months);
        $end = now()->startOfMonth()->subDay();

        return $this->weekdaysBetween($start, $end, $take);
    }

    /**
     * @return Collection<int, Carbon>
     */
    private function weekdaysBetween(Carbon $start, Carbon $end, int $take): Collection
    {
        $days = collect();

        for ($date = $start->copy(); $date->lte($end) && $days->count() < $take; $date->addDay()) {
            if ($date->isWeekday()) {
                $days->push($date->copy());
            }
        }

        return $days;
    }
}
