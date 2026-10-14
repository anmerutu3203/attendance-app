（応用）公開APIの仕様準拠修正
---
## 概要
公開API（#18〜#20）を仕様書（FN058〜FN062、AP01〜AP05）に合わせて修正する。あわせて、ヘッダーロゴのリンク先（`/`）を追加する。

## 実装する機能
- 読み取り系（GET）は認証不要、書き込み系（POST/PUT/PATCH/DELETE）のみ `auth:sanctum` 必須にする
- Request クラスを `App\Http\Requests\Api\V1` に移動
- `StoreAttendanceRecordRequest`
  - 管理者以外は `user_id` を自分のIDで上書き（他人の勤怠を登録不可）
  - 時刻形式を `H:i:s` に変更、`clock_out` を任意項目に変更
  - エラーメッセージを項目ごとの日本語文言に変更
- `UpdateAttendanceRecordRequest`
  - `user_id` の変更を不可にする
  - 時刻形式を `H:i:s` に変更、エラーメッセージを日本語文言に変更
- `IndexAttendanceRecordRequest` に日本語エラーメッセージを追加
- 詳細（show）で `user`（id・nameのみ）と `correction_requests` を返す
- 401 のメッセージを `Unauthenticated.`、403 のメッセージを「この操作を実行する権限がありません。」に変更
- `/` にアクセスしたとき、ログイン状態に応じて管理者勤怠一覧／勤怠打刻／ログイン画面へリダイレクト

## 完了条件
- [ ] 未ログインでも GET 一覧・詳細で200が返り、書き込み系は401 `{"message": "Unauthenticated."}` が返る
- [ ] 一般ユーザーが他人の `user_id` を指定して登録しても、自分の勤怠として登録される
- [ ] 不正データの422で、項目ごとの日本語メッセージが返る
- [ ] 詳細のレスポンスに `user` と `correction_requests` が含まれ、メールアドレスは含まれない
- [ ] `/` へのアクセスがログイン状態に応じてリダイレクトされる
- [ ] `sail test` が全件成功する
