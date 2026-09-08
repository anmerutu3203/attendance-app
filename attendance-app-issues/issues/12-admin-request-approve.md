管理者：申請一覧・承認機能
---
## 概要
全ユーザーの修正申請一覧と承認処理を実装する（FN047〜FN051）。

## 実装する機能
- `StampCorrectionRequestController@index`（管理者用、`/stamp_correction_request/list`）
- `StampCorrectionRequestController@show`/`approve`（`/stamp_correction_request/approve/{id}`）
- 承認処理（`attendance_correction_requests.status`更新、`attendance_records`/`breaks`への反映）

## 完了条件
- [ ] 承認待ち/承認済みが正しく一覧表示される
- [ ] 承認すると管理者・一般ユーザー両方の一覧が「承認済み」に更新される
- [ ] 承認により実際の勤怠データが修正内容通りに更新される
