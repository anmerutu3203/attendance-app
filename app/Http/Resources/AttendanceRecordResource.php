<?php

namespace App\Http\Resources;

use App\Models\AttendanceBreak;
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
            'comment' => $this->comment,
            // 一覧(index)ではbreaksをEager Loadingしないため出力されず、
            // 詳細(show)ではEager Loadingした上でこのキーが出力される。
            'breaks' => $this->whenLoaded(
                'breaks',
                fn () => $this->breaks->map(fn (AttendanceBreak $break) => [
                    'id' => $break->id,
                    'break_in' => $break->break_in,
                    'break_out' => $break->break_out,
                ])
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
