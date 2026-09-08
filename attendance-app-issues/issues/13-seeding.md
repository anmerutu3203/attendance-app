シーディング（ダミーデータ作成）
---
## 概要
採点・動作確認用のダミーデータを作成する。

## 実装する機能
- `UserSeeder`（user1/user2一般、user3管理者、パスワード`password`）
- `AttendanceRecordSeeder` / `BreakSeeder`（全ユーザー分の実運用に近いデータ）
- user1限定の意図的データ（過去5ヶ月の通常勤務75日＋当月17日分の遅刻/早退/残業/長時間労働パターン、固定休憩12:00-13:00）

## 完了条件
- [ ] `sail artisan migrate --seed` が通る
- [ ] user1で `/attendance/report` を開いた際の想定値（総労働744時間、総残業10時間、平均8時間5分、遅刻2回、早退1回、長時間労働1日）と一致する（Issue #17実装後に検証）
