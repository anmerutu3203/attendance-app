#!/usr/bin/env bash
#
# issues/ 配下の *.md を1件ずつGitHub Issueとして登録するスクリプト
#
# 各Markdownファイルの1行目をタイトル、3行目以降（"---"の次の行から）を本文として扱う
#
# 事前準備:
#   1. GitHub CLI (gh) をインストール
#   2. gh auth login で認証を済ませる
#   3. REPO変数を対象リポジトリに合わせて書き換える（下記）
#
# 実行方法:
#   chmod +x create_issues.sh
#   ./create_issues.sh
#
set -euo pipefail

REPO="anmerutu3203/attendance-app"
ISSUES_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/issues"

if ! command -v gh &> /dev/null; then
  echo "エラー: GitHub CLI (gh) が見つかりません。先にインストールしてください。"
  exit 1
fi

for file in "$ISSUES_DIR"/*.md; do
  filename="$(basename "$file")"

  # 1行目 = タイトル
  title="$(sed -n '1p' "$file")"

  # 3行目以降 = 本文（1行目タイトル、2行目"---"を除く）
  body="$(tail -n +3 "$file")"

  echo "登録中: [$filename] $title"

  gh issue create \
    --repo "$REPO" \
    --title "$title" \
    --body "$body"

  # API制限対策で少し待機
  sleep 1
done

echo "全てのIssue登録が完了しました。"
