<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\MonthlyAttendanceFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(
        private readonly MonthlyAttendanceFormatter $monthlyAttendanceFormatter
    ) {
    }

    public function index(): View
    {
        $users = User::where('admin_status', false)->get();

        return view('admin.staff-list', [
            'users' => $users,
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = isset($validated['date'])
            ? Carbon::createFromFormat('Y-m', $validated['date'])->startOfMonth()
            : now()->startOfMonth();

        $records = $user->attendanceRecords()
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->with('breaks')
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->date->format('Y-m-d'));

        return view('admin.staff-attendance-list', [
            'user' => $user,
            'date' => $month,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'formattedAttendanceRecords' => $this->monthlyAttendanceFormatter->format($month, $records),
        ]);
    }
}