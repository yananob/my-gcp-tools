# My GCP Tools (PHP)

これは、Google Cloud Platform (GCP) 関連のPHPツールを集めたライブラリです。

## 主な機能

- Google Cloud Functions 環境で役立つユーティリティ関数 (Cloud Functions Utils)

## セットアップ

1. `composer install` を実行して、依存関係をインストールします。
2. 必要に応じて、`configs_sample` ディレクトリにある設定ファイルのサンプルを元に、実際の設定ファイルを作成してください。 (例: `configs/firebase.json`)

## テスト

テストを実行するには、リポジトリのルートディレクトリで以下のコマンドを実行します。

```bash
bash tests/run_tests.sh
```

これには、PHPStanによる静的解析とPHPUnitによる単体テストが含まれます。

## 使い方 (CFUtils)

`yananob\MyGcpTools\CFUtils` クラスは、Cloud Functions 環境で役立つ静的メソッドを提供します。

```php
<?php

use yananob\MyGcpTools\CFUtils;
use Psr\Http\Message\ServerRequestInterface; // HTTPリクエストの例
use CloudEvents\V1\CloudEventInterface; // CloudEventの例

// ローカル環境からのHTTPリクエストかどうかを判定
$isLocal = CFUtils::isLocalHttp($request);

// ローカルからのテスト用CloudEventかどうかを判定
$isTestEvent = CFUtils::isLocalEvent($event);

// 現在の関数名を取得 (K_SERVICE 環境変数から)
$functionName = CFUtils::getFunctionName('default-function');

// テスト環境かどうかを判定 (関数名が空または "test" を含む場合)
$isTestEnv = CFUtils::isTestingEnv();

// リクエストからベースパスを取得
// 例: https://example.com/my-function -> /my-function
$basePath = CFUtils::getBasePath($isLocal, $request);

// リクエストからベースURLを取得
// 例: $isLocal = true, $request host = localhost:8080, K_SERVICE = my-func
// -> http://localhost:8080/my-func
$baseUrl = CFUtils::getBaseUrl($isLocal, $request);

// リクエストのGETパラメータ、POSTフォームデータ、JSONペイロードをマージして取得
$params = CFUtils::getMergedRequestParams($request);

```

## 設定ファイルサンプル

- `configs_sample/firebase.json.sample`: Firebase/Google Cloud サービスアカウントキーのサンプルです。実際の値に置き換えて使用してください。
- `configs_sample/gcputils.json.sample`: Cloud Functions のベースURLなどを定義する設定ファイルのサンプルです。

## GitHub Actions

このリポジトリでは、以下のイベントで自動的にテストが実行されます。

- `main` ブランチへのプッシュ
- プルリクエスト (opened, reopened, synchronize)
- スケジュール実行 (毎週金曜日 21:01 UTC)
- 手動実行 (workflow_dispatch)

詳細は `.github/workflows/run-test.yml` を参照してください。
