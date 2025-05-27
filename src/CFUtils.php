<?php

declare(strict_types=1);

namespace yananob\MyGcpTools;

use Psr\Http\Message\ServerRequestInterface;
use CloudEvents\V1\CloudEventInterface;

/**
 * Google Cloud Functions (GCF) 環境向けのユーティリティクラス。
 * HTTPリクエストやCloudEventの処理、環境情報の取得など、GCF開発で役立つ機能を提供します。
 */
final class CFUtils
{
    /**
     * CFUtils constructor.
     * 現状、特に初期化処理は行いません。
     */
    public function __construct() {}

    /**
     * HTTPリクエストがローカル環境からのものか判定します。
     * Hostヘッダーが "localhost" または "127.0.0.1" を含む場合にローカルと判定します。
     *
     * @param ServerRequestInterface $request PSR-7準拠のサーバーリクエストインターフェース。
     * @return bool ローカル環境からのリクエストであれば true、そうでなければ false。
     */
    public static function isLocalHttp(ServerRequestInterface $request): bool
    {
        return str_contains($request->getHeader("Host")[0], "localhost") || str_contains($request->getHeader("Host")[0], "127.0.0.1");
    }

    /**
     * CloudEventがローカルテスト用のものか判定します。
     * イベントIDが "9999999999" の場合にローカルテスト用イベントと判定します。
     *
     * @param CloudEventInterface $event CloudEvents SDKのイベントインターフェース。
     * @return bool ローカルテスト用イベントであれば true、そうでなければ false。
     */
    public static function isLocalEvent(CloudEventInterface $event): bool
    {
        return ($event->getId() === "9999999999");
    }

    /**
     * Cloud Functionsの関数名を取得します。
     * 環境変数 'K_SERVICE' から関数名を取得します。これはKnative環境（GCFの実行環境の一つ）で設定される標準的な環境変数です。
     * 環境変数が設定されていない場合は、指定されたデフォルト名を返します。
     *
     * @param string $defaultName 環境変数 'K_SERVICE' が未設定の場合に返すデフォルトの関数名。
     * @return string 関数名。取得できなかった場合はデフォルト名。
     */
    public static function getFunctionName(string $defaultName = ''): string
    {
        $funcName = getenv('K_SERVICE');
        // getenv() は失敗した場合に false を返すことがあるため、is_boolでチェック
        return is_bool($funcName) ? $defaultName : $funcName;
    }

    /**
     * 現在の環境がテスト環境かどうかを判定します。
     * 関数名が空（K_SERVICEが設定されていないなど）の場合、または関数名に "test" という文字列が含まれる場合にテスト環境とみなします。
     *
     * @return bool テスト環境であれば true、そうでなければ false。
     */
    public static function isTestingEnv(): bool
    {
        $funcName = self::getFunctionName('');
        if (empty($funcName)) {
            // 関数名が取得できない場合は、ローカルでのユニットテスト環境などの可能性があるためtrueとする
            return true;
        }
        // 関数名に "test" が含まれていればテスト環境と判定
        return str_contains($funcName, "test");
    }

    /**
     * リクエストURLからベースパス部分を取得します。
     * 例: https://example.com/my-function の場合、 "/my-function" を返します。
     *
     * @param bool $isLocal ローカル環境かどうか。これによりURLのスキーム（http/https）を決定します。
     * @param ServerRequestInterface $request PSR-7準拠のサーバーリクエストインターフェース。
     * @return string URLのベースパス。
     */
    public static function getBasePath(bool $isLocal, $request): string
    {
        $urlElems = self::__getUrlElements($isLocal, $request);
        return $urlElems["path"];
    }

    /**
     * リクエストURLからベースURL全体（スキーム、ホスト、ポート、パス）を取得します。
     * 例: ローカル環境でホストが 'localhost:8080'、関数名(K_SERVICE)が 'my-func' の場合、 "http://localhost:8080/my-func" を返します。
     *
     * @param bool $isLocal ローカル環境かどうか。
     * @param ServerRequestInterface $request PSR-7準拠のサーバーリクエストインターフェース。
     * @return string ベースURL。
     */
    public static function getBaseUrl(bool $isLocal, $request): string
    {
        $urlElems = self::__getUrlElements($isLocal, $request);
        // ポート番号が存在する場合のみURLに含める
        $port = (!empty($urlElems["port"]) ? (":" . $urlElems["port"]) : "");
        return $urlElems["scheme"] . "://" . $urlElems["host"] . $port . $urlElems["path"];
    }

    /**
     * リクエスト情報からURLの構成要素（スキーム、ホスト、ポート、パス）を解析して配列として取得するプライベートヘルパーメソッド。
     *
     * @param bool $isLocal ローカル環境かどうか。
     * @param ServerRequestInterface $request PSR-7準拠のサーバーリクエストインターフェース。
     * @return array URLの構成要素を格納した連想配列。キー: "scheme", "host", "port", "path"。
     */
    private static function __getUrlElements(bool $isLocal, $request): array
    {
        $params = $request->getServerParams();
        // ローカル環境ならhttp、そうでなければhttpsをデフォルトのプロトコルとする
        $protocol = $isLocal ? "http" : "https";
        // K_SERVICE（関数名）が存在すればパスに含める。存在しなければルートパスとする。
        $path = "/" . (array_key_exists('K_SERVICE', $params) ? $params["K_SERVICE"] : "");
        // 完全なURLを組み立てる（クエリパラメータやフラグメントは含まない）
        $fullUrl = "{$protocol}://{$params['HTTP_HOST']}{$path}";
        // parse_urlでURLを分解
        return parse_url($fullUrl);
    }

    /**
     * リクエストからGETクエリパラメータ、POSTフォームデータ、JSONペイロードをマージして取得します。
     * 同じキーが存在する場合、後のもので上書きされます（GET -> フォームデータ -> JSONボディ の優先順位）。
     *
     * @param ServerRequestInterface $request PSR-7準拠のサーバーリクエストインターフェース。
     * @return array マージされたリクエストパラメータの連想配列。
     */
    public static function getMergedRequestParams(ServerRequestInterface $request): array
    {
        // Content-Typeヘッダーを取得
        $contentType = $request->getHeaderLine('Content-Type');

        // ボディパラメータを格納する配列を初期化
        $bodyParams = [];
        if (stripos($contentType, 'application/json') !== false) {
            // Content-Typeが 'application/json' の場合
            // リクエストボディを生文字列として取得し、JSONデコードする
            $rawBody = (string) $request->getBody();
            $bodyParams = json_decode($rawBody, true) ?? []; // デコード失敗時は空配列
        } elseif (
            stripos($contentType, 'application/x-www-form-urlencoded') !== false ||
            stripos($contentType, 'multipart/form-data') !== false
        ) {
            // Content-Typeがフォームデータ (x-www-form-urlencoded または multipart/form-data) の場合
            // PSR-7のgetParsedBody()でパース済みのボディを取得
            $bodyParams = (array) $request->getParsedBody();
        }

        // GETクエリパラメータとボディパラメータをマージ
        // array_mergeの仕様により、後の配列の同じキーの値で上書きされる
        return array_merge((array)$request->getQueryParams(), $bodyParams);
    }
}
