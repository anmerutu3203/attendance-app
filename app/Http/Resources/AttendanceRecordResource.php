<?php

namespace App\Http\Resources;

use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'date' => $this->date->format('Y-m-d'),
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            // 合計時間は休憩から計算するため、breaksをEager Loadingしたときだけ出力する（N+1対策）
            'total_time' => $this->whenLoaded('breaks', fn () => $this->toHourMinute($this->total_time)),
            'total_break_time' => $this->whenLoaded('breaks', fn () => $this->toHourMinute($this->total_break_time)),
            'comment' => $this->comment,
            // 以下の関連はEager Loadingしたときだけ出力される。
            // 認証不要のAPIなので、ユーザーのメールアドレスは返さない。
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'breaks' => $this->whenLoaded(
                'breaks',
                fn () => $this->breaks->map(fn (AttendanceBreak $break) => [
                    'id' => $break->id,
                    'break_in' => $break->break_in,
                    'break_out' => $break->break_out,
                ])
            ),
            // 修正申請は詳細(show)でのみEager Loadingするため、一覧では出力されない
            'applications' => $this->whenLoaded(
                'correctionRequests',
                fn () => $this->correctionRequests->map(fn (AttendanceCorrectionRequest $correctionRequest) => [
                    'id' => $correctionRequest->id,
                    'requested_clock_in' => $correctionRequest->requested_clock_in,
                    'requested_clock_out' => $correctionRequest->requested_clock_out,
                    'requested_comment' => $correctionRequest->requested_comment,
                    'status' => $correctionRequest->status,
                    'approved_at' => $correctionRequest->approved_at,
                    'created_at' => $correctionRequest->created_at,
                ])
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * モデルの "H:i:s" を API仕様の "H:i" 形式にする
     */
    private function toHourMinute(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }
}
