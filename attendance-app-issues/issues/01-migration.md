マイグレーション・モデル作成
---
## 概要
テーブル仕様書に基づき、全テーブルのマイグレーションとEloquentモデル・リレーションを実装する。

## 実装する機能/テーブル/モデル/リレーション
- テーブル: `users`（admin_status追加）, `attendance_records`, `breaks`, `attendance_correction_requests`, `attendance_correction_request_breaks`
- モデル: `User`, `AttendanceRecord`, `Break`, `AttendanceCorrectionRequest`, `AttendanceCorrectionRequestBreak`
- リレーション: `User hasMany AttendanceRecord`, `AttendanceRecord hasMany Break`, `AttendanceRecord hasMany AttendanceCorrectionRequest`, `AttendanceCorrectionRequest hasMany AttendanceCorrectionRequestBreak`, `AttendanceCorrectionRequestBreak belongsTo Break（nullable）`
- `AttendanceRecord` に打刻ステータス算出用アクセサ（勤務外/出勤中/休憩中/退勤済）を実装

## 完了条件
- [ ] `sail artisan migrate` が通る
- [ ] 各モデルでリレーションが `with()` 越しに正しく取得できることをTinker等で確認
- [ ] ファイル名規則（モデル: アッパーキャメル、マイグレーション: スネークケース）を遵守
