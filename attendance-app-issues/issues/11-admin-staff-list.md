管理者：スタッフ一覧・スタッフ別勤怠機能
---
## 概要
全一般ユーザーの一覧とスタッフ別月次勤怠を実装する（FN041〜FN044、FN046）。

## 実装する機能
- `Admin\StaffController@index`（`/admin/staff/list`）
- `Admin\StaffController@show`（`/admin/attendance/staff/{id}`、月切り替え対応）
- 詳細画面への遷移

## 完了条件
- [ ] 氏名・メールアドレスが正しく一覧表示される
- [ ] 月切り替え・詳細遷移が正しく動作する
