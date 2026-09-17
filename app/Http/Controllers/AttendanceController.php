<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $user = $request->user();
        $record = $user->attendanceRecords()->firstOrCreate(['date' => today()]);
        $now = now()->format('H:i:s');

        match ($request->input('action')) {
            'clock_in' => $this->clockIn($record, $now),
            'clock_out' => $this->clockOut($record, $now),
            'break_in' => $this->breakIn($record, $now),
            'break_out' => $this->breakOut($record, $now),
            default => null,
        };

        return redirect('/attendance');
    }

    /**
     * @param  \App\Models\AttendanceRecord  $record
     */
    private function clockIn($record, string $now): void
    {
        if (is_null($record->clock_in)) {
            $record->update(['clock_in' => $now]);
        }
    }

    /**
     * @param  \App\Models\AttendanceRecord  $record
     */
    private function clockOut($record, string $now): void
    {
        if (! is_null($record->clock_in) && is_null($record->clock_out)) {
            $record->update(['clock_out' => $now]);
        }
    }

    /**
     * @param  \App\Models\AttendanceRecord  $record
     */
    private function breakIn($record, string $now): void
    {
        if ($record->status === '出勤中') {
            $record->breaks()->create(['break_in' => $now]);
        }
    }

    /**
     * @param  \App\Models\AttendanceRecord  $record
     */
    private function breakOut($record, string $now): void
    {
        $openBreak = $record->breaks()->whereNull('break_out')->latest('id')->first();
        if ($openBreak) {
            $openBreak->update(['break_out' => $now]);
        }
    }
}