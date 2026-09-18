<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class AttendanceRecordController extends Controller
{
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $query = AttendanceRecord::query();

        if (! empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (! empty($validated['date'])) {
            $query->whereDate('date', $validated['date']);
        }

        if (! empty($validated['month'])) {
            $month = Carbon::createFromFormat('Y-m', $validated['month']);
            $query->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);
        }

        $records = $query->orderBy('date')->paginate($validated['per_page'] ?? 20);

        return AttendanceRecordResource::collection($records);
    }

    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load('breaks');

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function update(Request $request, AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorize('update', $attendanceRecord);

        $attendanceRecord->update($request->only(['clock_in', 'clock_out', 'comment']));

        return response()->json($attendanceRecord);
    }

    public function destroy(AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->json(null, 204);
    }
}
