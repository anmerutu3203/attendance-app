（応用）公開API 読み取り系（一覧・詳細）
---
## 概要
勤怠一覧・詳細の取得APIを実装する（FN058〜FN059、AP01・AP02）。

## 実装する機能
- `Api\V1\AttendanceRecordController@index`/`show`
- `IndexAttendanceRecordRequest`（user_id/date/month/page/per_page）
- `AttendanceRecordResource`（`whenLoaded`で一覧/詳細を出し分け）
- ページネーション（デフォルト20、最大100）

## 完了条件
- [ ] GET `/api/v1/attendance-records` で200とdata+meta（current_page等）が返る
- [ ] GET `/api/v1/attendance-records/{id}` で存在しないIDに404 `{"error": "勤怠情報が見つかりませんでした。"}` が返る
