<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function create(Request $request): View
    {
        return view('user.attendance-register', [
            'user' => $request->user(),
            'formattedDate' => now()->isoFormat('YYYY年M月D日(ddd)'),
            'formattedTime' => now()->format('H:i'),
        ]);
    }

    public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'action' => ['required', Rule::in(['clock_in', 'clock_out', 'break_in', 'break_out'])],
    ]);

    $user = $request->user();
    $now = now()->format('H:i:s');

    $succeeded = DB::transaction(function () use ($user, $validated, $now) {
        $record = $user->attendanceRecords()
            ->where('date', today())
            ->lockForUpdate()
            ->first();

        if (! $record) {
            try {
                $record = $user->attendanceRecords()->create(['date' => today()]);
            } catch (\Illuminate\Database\QueryException $e) {
                // 23000: 一意制約違反（user_id, date）。同時リクエストで既に作成済みのため再取得する。
                // それ以外のDBエラーは想定外なので再スローする。
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $record = $user->attendanceRecords()->where('date', today())->lockForUpdate()->firstOrFail();
            }
        }

        return match ($validated['action']) {
            'clock_in' => $record->clockIn($now),
            'clock_out' => $record->clockOut($now),
            'break_in' => $record->startBreak($now),
            'break_out' => $record->endBreak($now),
        };
    });

    return $succeeded
        ? redirect('/attendance')->with('status', '打刻しました。')
        : redirect('/attendance')->with('error', '現在の状態ではその操作はできません。');
}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = isset($validated['date'])
            ? Carbon::createFromFormat('Y-m', $validated['date'])->startOfMonth()
            : now()->startOfMonth();

        $records = $request->user()
            ->attendanceRecords()
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->with('breaks')
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->date->format('Y-m-d'));

        return view('user.user-attendance-list', [
            'date' => $month,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'formattedAttendanceRecords' => $this->buildMonthlyRecords($month, $records),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<string, \App\Models\AttendanceRecord>  $records
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function buildMonthlyRecords(Carbon $month, Collection $records): Collection
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