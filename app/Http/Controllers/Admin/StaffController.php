<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\MonthlyAttendanceFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        return view('admin.staff-attendance-list', [
            'user' => $user,
            'date' => $month,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'formattedAttendanceRecords' => $this->monthlyAttendanceFormatter->format($month, $this->monthlyRecords($user, $month)),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'year_month' => ['required', 'date_format:Y-m'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $month = Carbon::createFromFormat('Y-m', $validated['year_month'])->startOfMonth();

        $rows = $this->monthlyAttendanceFormatter->format($month, $this->monthlyRecords($user, $month));

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // ExcelでUTF-8のCSVが文字化けしないようBOMを付与
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['date'],
                    $row['clock_in'],
                    $row['clock_out'],
                    $row['total_break_time'] ? Carbon::parse($row['total_break_time'])->format('G:i') : '',
                    $row['total_time'] ? Carbon::parse($row['total_time'])->format('G:i') : '',
                ]);
            }

            fclose($handle);
        }, "{$user->name}_{$month->format('Y-m')}.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return Collection<string, AttendanceRecord>
     */
    private function monthlyRecords(User $user, Carbon $month): Collection
    {
        return $user->attendanceRecords()
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->with('breaks')
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->date->format('Y-m-d'));
    }
}