<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = isset($validated['date'])
            ? Carbon::createFromFormat('Y-m-d', $validated['date'])
            : today();

        $users = User::where('admin_status', false)->get();

        $attendanceRecords = AttendanceRecord::whereDate('date', $date)
            ->with('breaks')
            ->get();

        return view('admin.admin-attendance-list', [
            'date' => $date,
            'previousDay' => $date->copy()->subDay()->format('Y-m-d'),
            'nextDay' => $date->copy()->addDay()->format('Y-m-d'),
            'users' => $users,
            'attendanceRecords' => $attendanceRecords,
        ]);
    }
}