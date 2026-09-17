<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $applications = $user->attendanceCorrectionRequests()
            ->with('attendanceRecord')
            ->latest()
            ->get();

        return view('user.user-application-list', [
            'user' => $user,
            'formattedApplications' => $applications->map(
                fn (AttendanceCorrectionRequest $application) => $this->formatApplication($application)
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
     * @return array<string, mixed>
     */
    private function formatApplication(AttendanceCorrectionRequest $application): array
    {
        return [
            'id' => $application->id,
            'approval_status' => $application->status === 1 ? '承認済み' : '承認待ち',
            'date' => $application->attendanceRecord->date->format('Y年n月j日'),
            'comment' => $application->requested_comment,
            'application_date' => $application->created_at->format('Y年n月j日'),
        ];
    }
}