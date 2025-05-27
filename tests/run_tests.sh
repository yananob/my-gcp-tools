#!/bin/bash
# このスクリプトは、プロジェクトのテストスイートを実行します。
# PHPStanによる静的解析とPHPUnitによる単体テストが含まれます。

# -e: コマンドがエラー終了した場合、直ちにスクリプトを終了します。
# -u: 未定義の変数を参照しようとした場合、エラーとして扱います。
set -eu

# PHPStan を実行して静的解析を行います。
# -c phpstan.neon: phpstan.neon 設定ファイルを使用します。
echo "Running PHPStan for static analysis..."
./vendor/bin/phpstan analyze -c phpstan.neon

# PHPUnit を実行して単体テストを行います。
# --colors=auto: サポートされていれば、出力を色付けします。
# --display-notices: テスト実行中に発生したPHPのnoticeを表示します。
# --display-warnings: テスト実行中に発生したPHPのwarningを表示します。
# tests/: tests/ ディレクトリ内のテストを実行します。
echo "Running PHPUnit tests..."
./vendor/bin/phpunit --colors=auto --display-notices --display-warnings tests/

echo "All tests completed."
