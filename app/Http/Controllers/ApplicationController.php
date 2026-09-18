<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->admin_status) {
            // admin-application-list.blade.php は `$application->AttendanceRecord`（大文字）で
            // アクセスするため、eager load 側もキーを合わせて再クエリ（N+1）を防ぐ。
            $applications = AttendanceCorrectionRequest::with(['AttendanceRecord', 'user'])
                ->latest()
                ->get();

            return view('admin.admin-application-list', [
                'applications' => $applications,
            ]);
        }

        $applications = $user->attendanceCorrectionRequests()
            ->with('attendanceRecord')
            ->latest()
            ->get();

        return view('user.user-application-list', [
            'user' => $user,
            'formattedApplications' => $applications->map(
                fn (AttendanceCorrectionRequest $application) => $this->formatApplicationForUser($application)
            ),
        ]);
    }

    /**
     * 申請詳細への遷移。実体は紐づく勤怠詳細画面（/attendance/{id}）へのリダイレクト。
     * FN033: 「詳細」を押下すると勤怠詳細画面に遷移すること
     */
    public function show(Request $request, AttendanceCorrectionRequest $attendanceCorrectionRequest): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user->admin_status || $attendanceCorrectionRequest->user_id === $user->id,
            403
        );

        return redirect('/attendance/' . $attendanceCorrectionRequest->attendance_record_id);
    }

    /**
     * 管理者向け：申請内容の確認画面（承認前）。
     */
    public function showApproval(AttendanceCorrectionRequest $attendanceCorrectionRequest): View
    {
        $attendanceCorrectionRequest->load(['attendanceRecord', 'user', 'requestBreaks']);

        return view('admin.admin-application-detail', [
            'user' => $attendanceCorrectionRequest->user,
            'application' => $attendanceCorrectionRequest,
        ]);
    }

    /**
     * 管理者向け：申請の承認。勤怠データへ反映し、申請を承認済みにする。
     * FN050 / FN051
     */
    public function approve(AttendanceCorrectionRequest $attendanceCorrectionRequest): RedirectResponse
    {
        if ($attendanceCorrectionRequest->status === 1) {
            return redirect('/stamp_correction_request/approve/' . $attendanceCorrectionRequest->id);
        }

        DB::transaction(function () use ($attendanceCorrectionRequest) {
            $attendanceRecord = $attendanceCorrectionRequest->attendanceRecord;

            $attendanceRecord->update([
                'clock_in' => $attendanceCorrectionRequest->requested_clock_in,
                'clock_out' => $attendanceCorrectionRequest->requested_clock_out,
                'comment' => $attendanceCorrectionRequest->requested_comment,
            ]);

            foreach ($attendanceCorrectionRequest->requestBreaks as $requestBreak) {
                if ($requestBreak->break_id) {
                    $requestBreak->break()->update([
                        'break_in' => $requestBreak->requested_break_in,
                        'break_out' => $requestBreak->requested_break_out,
                    ]);
                } else {
                    $attendanceRecord->breaks()->create([
                        'break_in' => $requestBreak->requested_break_in,
                        'break_out' => $requestBreak->requested_break_out,
                    ]);
                }
            }

            $attendanceCorrectionRequest->update([
                'status' => 1,
                'approved_at' => now(),
            ]);
        });

        return redirect('/stamp_correction_request/approve/' . $attendanceCorrectionRequest->id)
            ->with('status', '承認しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatApplicationForUser(AttendanceCorrectionRequest $application): array
    {
        return [
            'id' => $application->id,
            'approval_status' => $application->approval_status,
            'date' => $application->attendanceRecord->date->format('Y年n月j日'),
            'comment' => $application->requested_comment,
            'application_date' => $application->created_at->format('Y年n月j日'),
        ];
    }
}
