<?php

namespace App\Http\Requests\Api\V1;

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
     * 部分更新に対応するため、送信された項目のみ検証する（未送信の項目は既存値を保持）。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var AttendanceRecord $attendanceRecord */
        $attendanceRecord = $this->route('attendanceRecord');

        return [
            'date' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
                Rule::unique('attendance_records')
                    ->where(fn ($query) => $query->where('user_id', $attendanceRecord->user_id))
                    ->ignore($attendanceRecord->id),
            ],
            'clock_in' => ['sometimes', 'required', 'date_format:H:i:s'],
            'clock_out' => ['sometimes', 'nullable', 'date_format:H:i:s'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => '勤怠日は必須です。',
            'date.date_format' => '勤怠日は YYYY-MM-DD 形式で指定してください。',
            'date.unique' => 'この日付の勤怠は既に登録されています。',
            'clock_in.required' => '出勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.date_format' => '退勤時刻は HH:MM:SS 形式で指定してください。',
            'comment.max' => '備考は 255 文字以内で入力してください。',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if ($validator->errors()->has('clock_in') || $validator->errors()->has('clock_out')) {
                return; // 出退勤自体が既にエラーなら前後関係チェックは意味を持たないためスキップ
            }

            // 片方だけ送信された場合は、もう片方を既存値と比較する
            /** @var AttendanceRecord $attendanceRecord */
            $attendanceRecord = $this->route('attendanceRecord');
            $clockIn = $this->input('clock_in', $attendanceRecord->clock_in);
            $clockOut = $this->input('clock_out', $attendanceRecord->clock_out);

            if ($clockIn && $clockOut && Carbon::parse($clockIn)->gte(Carbon::parse($clockOut))) {
                $validator->errors()->add('clock_out', '退勤時刻は出勤時刻より後の時刻を指定してください。');
            }
        });
    }
}
