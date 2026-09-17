<?php

namespace App\Http\Requests;

use App\Models\AttendanceRecord;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AttendanceRecord $attendanceRecord */
        $attendanceRecord = $this->route('attendanceRecord');
        $user = $this->user();

        if (! $user || ! $attendanceRecord) {
            return false;
        }

        return $user->admin_status || $attendanceRecord->user_id === $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['required', 'date_format:H:i'],
            'new_break_in' => ['array'],
            'new_break_in.*' => ['nullable', 'date_format:H:i'],
            'new_break_out' => ['array'],
            'new_break_out.*' => ['nullable', 'date_format:H:i'],
            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'new_clock_in.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_in.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_out.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_clock_out.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'comment.required' => '備考を記入してください',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $clockIn = $this->input('new_clock_in');
            $clockOut = $this->input('new_clock_out');

            // 出退勤自体が既にエラーなら、休憩の前後関係チェックは意味を持たないためスキップ
            if ($validator->errors()->has('new_clock_in') || $validator->errors()->has('new_clock_out')) {
                return;
            }

            if ($clockIn >= $clockOut) {
                $validator->errors()->add('new_clock_out', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            foreach ((array) $this->input('new_break_in', []) as $index => $breakIn) {
                $breakOut = $this->input("new_break_out.$index");

                if (blank($breakIn) && blank($breakOut)) {
                    continue; // 末尾の追加用空欄行はスキップ
                }

                if ($breakIn && ($breakIn < $clockIn || $breakIn > $clockOut)) {
                    $validator->errors()->add("new_break_in.$index", '休憩時間が不適切な値です');
                }

                if ($breakOut && $breakOut > $clockOut) {
                    $validator->errors()->add("new_break_out.$index", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }
}