<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 管理者以外は自分の勤怠のみ登録できる。管理者も user_id 未指定なら自分の勤怠として登録する。
     */
    protected function prepareForValidation(): void
    {
        if (! $this->user()->admin_status || ! $this->filled('user_id')) {
            $this->merge(['user_id' => $this->user()->id]);
        }
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
            'clock_in' => ['required', 'date_format:H:i:s'],
            'clock_out' => ['nullable', 'date_format:H:i:s', 'after:clock_in'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => '指定されたユーザーが見つかりません。',
            'date.required' => '勤怠日は必須です。',
            'date.date_format' => '勤怠日は YYYY-MM-DD 形式で指定してください。',
            'date.unique' => 'この日付の勤怠は既に登録されています。',
            'clock_in.required' => '出勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.date_format' => '退勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.after' => '退勤時刻は出勤時刻より後の時刻を指定してください。',
            'comment.max' => '備考は 255 文字以内で入力してください。',
        ];
    }
}
