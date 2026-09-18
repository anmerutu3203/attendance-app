<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    private const STANDARD_CLOCK_IN = '09:00:00';
    private const STANDARD_CLOCK_OUT = '18:00:00';
    private const STANDARD_WORK_SECONDS = 8 * 3600;
    private const LONG_WORK_SECONDS = 10 * 3600;
    private const MONTHS_IN_REPORT = 6;

    public function index(): View
    {
        $user = Auth::user();
        $rangeStart = now()->subMonths(self::MONTHS_IN_REPORT - 1)->startOfMonth();
        $rangeEnd = now()->endOfMonth();

        $records = $user->attendanceRecords()
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->whereNotNull('clock_in')
            ->whereNotNull('clock_out')
            ->with('breaks')
            ->get()
            ->map(fn (AttendanceRecord $record) => [
                'month' => $record->date->format('Y-m'),
                'clock_in' => $record->clock_in,
                'clock_out' => $record->clock_out,
                'work_seconds' => $this->toSeconds($record->total_time),
            ]);

        $totalWorkSeconds = $records->sum('work_seconds');
        $totalOvertimeSeconds = $records->sum(
            fn (array $record) => max($record['work_seconds'] - self::STANDARD_WORK_SECONDS, 0)
        );
        $averageWorkSeconds = $records->isNotEmpty()
            ? intdiv($totalWorkSeconds, $records->count())
            : 0;

        $monthlyTrend = collect(range(0, self::MONTHS_IN_REPORT - 1))
            ->map(fn (int $i) => $rangeStart->copy()->addMonths($i)->format('Y-m'))
            ->map(fn (string $month) => [
                'month' => $month,
                'totalWorkTime' => $this->formatDuration(
                    $records->where('month', $month)->sum('work_seconds')
                ),
            ]);

        $currentMonthRecords = $records->where('month', now()->format('Y-m'));

        return view('reports.index', [
            'totalWorkTime' => $this->formatDuration($totalWorkSeconds),
            'totalOvertime' => $this->formatDuration($totalOvertimeSeconds),
            'averageWorkTime' => $this->formatDuration($averageWorkSeconds),
            'monthlyTrend' => $monthlyTrend,
            'lateCount' => $currentMonthRecords
                ->filter(fn (array $record) => $record['clock_in'] > self::STANDARD_CLOCK_IN)
                ->count(),
            'earlyLeaveCount' => $currentMonthRecords
                ->filter(fn (array $record) => $record['clock_out'] < self::STANDARD_CLOCK_OUT)
                ->count(),
            'longWorkCount' => $currentMonthRecords
                ->filter(fn (array $record) => $record['work_seconds'] >= self::LONG_WORK_SECONDS)
                ->count(),
        ]);
    }

    private function toSeconds(string $time): int
    {
        [$hours, $minutes, $seconds] = explode(':', $time);

        return ((int) $hours) * 3600 + ((int) $minutes) * 60 + (int) $seconds;
    }

    private function formatDuration(int $seconds): string
    {
        $totalMinutes = intdiv($seconds, 60);

        return sprintf('%d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
    }
}
