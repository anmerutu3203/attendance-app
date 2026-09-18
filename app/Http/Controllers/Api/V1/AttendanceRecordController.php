<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
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
