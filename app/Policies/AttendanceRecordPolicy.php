<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * 本人または管理者のみ更新・削除できる。
     */
    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->admin_status || $user->id === $attendanceRecord->user_id;
    }

    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $this->update($user, $attendanceRecord);
    }
}
