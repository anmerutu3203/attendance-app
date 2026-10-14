（応用）公開APIのレスポンスをAPI仕様書に合わせる
---
## 概要
API仕様書（AP01〜AP04、API Resource の構造）のレスポンス例・実装方法と、現在の実装の差分を修正する。

## 実装する機能
- 詳細（show）の修正申請のキー名を `correction_requests` から `applications` に変更
- 一覧の各行に `user_name`・`total_time`・`total_break_time`（`HH:MM` 形式）を追加し、`with(['user', 'breaks'])` で Eager Loading する
- 一覧の並び順を `latest('date')`（新しい日付が先）に変更
- 登録・更新後に `load(['user', 'breaks'])` してから返す
- 登録時の `user_id` は認証ユーザーから自動付与し、リクエストの `user_id` は受け付けない（管理者による他ユーザーの登録機能を削除）

## 完了条件
- [ ] 詳細のレスポンスに `applications` が含まれる
- [ ] 一覧のレスポンスに `user_name`・`total_time`・`total_break_time` が含まれ、新しい日付順に並ぶ
- [ ] 登録・更新のレスポンスに `user` と `breaks` が含まれる
- [ ] 管理者が他ユーザーの `user_id` を送っても、自分の勤怠として登録される
- [ ] `sail test` が全件成功する
