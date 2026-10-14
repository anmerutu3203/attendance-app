使っていないFortify機能をオフにする
---
## 概要
基本設計書に記載したルートとアプリのルートを一致させるため、使っていない Fortify の機能をオフにする。

## 実装する機能
- `config/fortify.php` の features を、会員登録（registration）とメール認証（emailVerification）だけにする
- 2段階認証・パスワードリセット・プロフィール更新・パスワード変更をオフにする
- `FortifyServiceProvider` から、オフにした機能の設定を削除
- 不要になった `App\Actions\Fortify` のクラス（ResetUserPassword / UpdateUserPassword / UpdateUserProfileInformation / PasswordValidationRules）を削除

## 完了条件
- [ ] `/two-factor-challenge`・`/forgot-password`・`/user/password` などのルートが無くなる
- [ ] 会員登録・ログイン・ログアウト・メール認証は引き続き動作する
- [ ] `sail test` が全件成功する
