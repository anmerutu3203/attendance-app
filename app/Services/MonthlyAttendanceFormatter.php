<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MonthlyAttendanceFormatter
{
    /**
     * 月内の全日付をキーに、勤怠一覧の表示用データを組み立てる。
     * 打刻の無い日は各項目が空欄になる。
     *
     * @param  \Illuminate\Support\Collection<string, AttendanceRecord>  $records  日付文字列(Y-m-d)をキーにした勤怠レコード
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function format(Carbon $month, Collection $records): Collection
    {
        return collect(range(1, $month->daysInMonth))->map(function (int $day) use ($month, $records) {
            $current = $month->copy()->day($day);
            $record = $records->get($current->format('Y-m-d'));

            return [
                'id' => $record?->id,
                'date' => $current->format('Y/m/d'),
                'clock_in' => $record?->clock_in ? Carbon::parse($record->clock_in)->format('H:i') : '',
                'clock_out' => $record?->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : '',
                'total_break_time' => $record?->total_break_time,
                'total_time' => $record?->total_time,
            ];
        });
    }
}