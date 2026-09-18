<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRecordRequest extends FormRequest
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
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'date' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('attendance_records')
                    ->where(fn ($query) => $query->where('user_id', $this->input('user_id'))),
            ],
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => '従業員を指定してください',
            'user_id.exists' => '指定された従業員が見つかりません',
            'date.required' => '日付を指定してください',
            'date.date_format' => '日付の形式が不正です',
            'date.unique' => 'その日付の勤怠情報は既に登録されています',
            'clock_in.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_in.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'comment.max' => '備考は255文字以内で入力してください',
        ];
    }
}
