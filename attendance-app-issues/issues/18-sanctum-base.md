（応用）公開API基盤（Sanctum導入）
---
## 概要
書き込み系APIの認証基盤を整備する（FN057、FN063〜FN068）。

## 実装する機能
- `composer require laravel/sanctum`、`personal_access_tokens`マイグレーション
- `User`に`HasApiTokens`追加
- `Api\V1\`名前空間のコントローラー分離
- `AttendanceRecordPolicy`（本人または管理者のみ更新・削除可）
- `app/Exceptions/Handler.php`でのエラーJSON統一（404/403）

## 完了条件
- [ ] 未認証で書き込み系APIを叩くと401が返る
- [ ] 他ユーザーの勤怠を更新・削除しようとすると403が返る
