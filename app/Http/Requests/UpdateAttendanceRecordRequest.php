<?php

namespace App\Http\Requests;

use App\Models\AttendanceRecord;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class UpdateAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var AttendanceRecord $attendanceRecord */
        $attendanceRecord = $this->route('attendanceRecord');

        return [
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'date' => [
                'sometimes',
                'date_format:Y-m-d',
                Rule::unique('attendance_records')
                    ->where(fn ($query) => $query->where(
                        'user_id',
                        $this->input('user_id', $attendanceRecord->user_id)
                    ))
                    ->ignore($attendanceRecord->id),
            ],
            'clock_in' => ['sometimes', 'date_format:H:i'],
            'clock_out' => ['sometimes', 'date_format:H:i'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => '指定された従業員が見つかりません',
            'date.date_format' => '日付の形式が不正です',
            'date.unique' => 'その日付の勤怠情報は既に登録されています',
            'clock_in.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'comment.max' => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if ($validator->errors()->has('clock_in') || $validator->errors()->has('clock_out')) {
                return; // 出退勤自体が既にエラーなら前後関係チェックは意味を持たないためスキップ
            }

            /** @var AttendanceRecord $attendanceRecord */
            $attendanceRecord = $this->route('attendanceRecord');
            $clockIn = $this->input('clock_in', $attendanceRecord->clock_in);
            $clockOut = $this->input('clock_out', $attendanceRecord->clock_out);

            if ($clockIn && $clockOut && Carbon::parse($clockIn)->gte(Carbon::parse($clockOut))) {
                $validator->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です');
            }
        });
    }
}
