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
use App\Http\Requests\AttendanceUpdateRequest;
use App\Models\User;


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

    public function show(Request $request, AttendanceRecord $attendanceRecord): View
{
    $user = $request->user();

    if (! $user->admin_status && $attendanceRecord->user_id !== $user->id) {
        abort(403);
    }

    $attendanceRecord->load(['breaks', 'user']);
    $data = $this->formatRecordForView($attendanceRecord);

    if ($user->admin_status) {
        return view('admin.admin-detail', [
            'user' => $attendanceRecord->user,
            'attendanceRecord' => $data,
        ]);
    }

    $data['application'] = $attendanceRecord->correctionRequests()
        ->where('status', 0)
        ->latest()
        ->first();

    return view('user.user-detail', [
        'user' => $user,
        'data' => $data,
    ]);
}

public function update(AttendanceUpdateRequest $request, AttendanceRecord $attendanceRecord): RedirectResponse
{
    // FN030 / FN038: 承認待ちの申請がある間は一般・管理者とも修正不可
    if ($attendanceRecord->correctionRequests()->where('status', 0)->exists()) {
        return back()->with('error', '承認待ちのため修正はできません。');
    }

    $user = $request->user();

    return $user->admin_status
        ? $this->updateAsAdmin($request, $attendanceRecord) // Issue #10 で実装
        : $this->createCorrectionRequest($request, $attendanceRecord, $user);
}

/**
 * @return array<string, mixed>
 */
private function formatRecordForView(AttendanceRecord $attendanceRecord): array
{
    return [
        'id' => $attendanceRecord->id,
        'year' => $attendanceRecord->date->format('Y年'),
        'date' => $attendanceRecord->date->format('n月j日'),
        'clock_in' => $attendanceRecord->clock_in ? Carbon::parse($attendanceRecord->clock_in)->format('H:i') : '',
        'clock_out' => $attendanceRecord->clock_out ? Carbon::parse($attendanceRecord->clock_out)->format('H:i') : '',
        'breaks' => $attendanceRecord->breaks->map(fn (\App\Models\AttendanceBreak $break) => [
            'break_in' => $break->break_in ? Carbon::parse($break->break_in)->format('H:i') : '',
            'break_out' => $break->break_out ? Carbon::parse($break->break_out)->format('H:i') : '',
        ])->all(),
        'comment' => $attendanceRecord->comment,
    ];
}

private function createCorrectionRequest(
    AttendanceUpdateRequest $request,
    AttendanceRecord $attendanceRecord,
    User $user
): RedirectResponse {
    $validated = $request->validated();
    $existingBreaks = $attendanceRecord->breaks; // インデックス = Bladeのnew_break_in[index]と対応

    DB::transaction(function () use ($validated, $attendanceRecord, $user, $existingBreaks) {
        $correctionRequest = $attendanceRecord->correctionRequests()->create([
            'user_id' => $user->id,
            'requested_clock_in' => $validated['new_clock_in'] . ':00',
            'requested_clock_out' => $validated['new_clock_out'] . ':00',
            'requested_comment' => $validated['comment'],
            'status' => 0,
        ]);

        foreach ($validated['new_break_in'] ?? [] as $index => $breakIn) {
            $breakOut = $validated['new_break_out'][$index] ?? null;

            if (blank($breakIn) && blank($breakOut)) {
                continue;
            }

            $correctionRequest->requestBreaks()->create([
                'break_id' => $existingBreaks->get($index)?->id,
                'requested_break_in' => $breakIn ? $breakIn . ':00' : null,
                'requested_break_out' => $breakOut ? $breakOut . ':00' : null,
            ]);
        }
    });

    return redirect('/attendance/' . $attendanceRecord->id)
        ->with('status', '修正申請を送信しました。');
}

private function updateAsAdmin(AttendanceUpdateRequest $request, AttendanceRecord $attendanceRecord): RedirectResponse
{
    // Issue #10 で実装
    abort(501);
}
}