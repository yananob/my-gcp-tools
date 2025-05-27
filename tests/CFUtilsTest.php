<?php

declare(strict_types=1);

// オートローダーの読み込み。通常はPHPUnitのブートストラッププロセスやComposerが処理します。
// require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use yananob\MyGcpTools\CFUtils;

/**
 * `ServerRequestInterface` のモッククラス。
 * CFUtilsのURL関連メソッドのテストに使用します。
 * コンストラクタでサーバーパラメータの配列を受け取り、`getServerParams()` メソッドで
 * `CFUtils` が期待する形式の配列（'HTTP_HOST' と 'K_SERVICE' をキーに持つ）を返します。
 */
class MockServerRequestInterface
{
    /**
     * @param array $serverParams [HTTP_HOST, K_SERVICE] の形式の配列。
     */
    public function __construct(private array $serverParams)
    {
    }

    /**
     * モックされたサーバーパラメータを取得します。
     *
     * @return array サーバーパラメータの連想配列。
     */
    public function getServerParams(): array
    {
        return [
            "HTTP_HOST" => $this->serverParams[0], // HTTPホスト (例: "localhost", "example.com:8080")
            "K_SERVICE" => $this->serverParams[1], // K_SERVICE 環境変数 (例: "my-function", "")
        ];
    }
}

/**
 * `CFUtils` クラスのPHPUnitテストクラス。
 */
final class CFUtilsTest extends TestCase
{
    /**
     * `CFUtils::isTestingEnv()` メソッドのテスト。
     * このテスト環境では `K_SERVICE` が設定されていない（または "test" を含む）と仮定し、
     * `isTestingEnv()` が `true` を返すことをアサートします。
     */
    public function testIsTestingEnv(): void
    {
        $this->assertSame(true, CFUtils::isTestingEnv());
    }

    /**
     * `testGetBasePath` メソッドのデータプロバイダ。
     * 様々なシナリオ（ローカル/リモート、パスの有無）のテストデータを提供します。
     *
     * @return array テストケースの配列。各要素は [isLocal, serverParams, expectedPath]。
     */
    public static function providerGetBasePath(): array
    {
        return [
            // case                                 => [isLocal, serverParams (host, K_SERVICE), expected path]
            "ローカル環境、パスなし (K_SERVICEが空)"      => [true,  ["localhost", ""], "/"],
            "ローカル環境、パスあり (K_SERVICEがgcptool)" => [true,  ["localhost", "gcptool"], "/gcptool"],
            "リモート環境、パスなし (K_SERVICEが空)"     => [false, ["hogehoge.com", ""], "/"],
            "リモート環境、パスあり (K_SERVICEがgcptool)"=> [false, ["hogehoge.com", "gcptool"], "/gcptool"],
        ];
    }

    /**
     * `CFUtils::getBasePath()` メソッドのテスト。
     * `providerGetBasePath` から提供されるデータを使用してテストを実行します。
     *
     * @param bool $isLocal ローカル環境かどうか。
     * @param array $serverParams モックリクエストのサーバーパラメータ。
     * @param string $expected 期待されるベースパス。
     */
    #[DataProvider("providerGetBasePath")]
    public function testGetBasePath(bool $isLocal, array $serverParams, string $expected): void
    {
        $mock = new MockServerRequestInterface($serverParams);
        $this->assertSame($expected, CFUtils::getBasePath($isLocal, $mock));
    }

    /**
     * `testGetBaseUrl` メソッドのデータプロバイダ。
     * 様々なシナリオ（ローカル/リモート、ポートの有無、パスの有無）のテストデータを提供します。
     *
     * @return array テストケースの配列。各要素は [isLocal, serverParams, expectedUrl]。
     */
    public static function providerGetBaseUrl(): array
    {
        return [
            // case                                           => [isLocal, serverParams (host, K_SERVICE), expected URL]
            "ローカル、ポートなし、パスなし"                          => [true,  ["localhost", ""], "http://localhost/"],
            "ローカル、ポートあり (8080)、パスなし"                  => [true,  ["localhost:8080", ""], "http://localhost:8080/"],
            "ローカル、ポートなし、パスあり (gcptool)"                => [true,  ["localhost", "gcptool"], "http://localhost/gcptool"],
            "ローカル、ポートあり (8080)、パスあり (gcptool)"        => [true,  ["localhost:8080", "gcptool"], "http://localhost:8080/gcptool"],
            "リモート、ポートなし、パスなし"                         => [false, ["hogehoge.com", ""], "https://hogehoge.com/"],
            "リモート、ポートあり (8080)、パスなし"                 => [false, ["hogehoge.com:8080", ""], "https://hogehoge.com:8080/"],
            "リモート、ポートなし、パスあり (gcptool)"               => [false, ["hogehoge.com", "gcptool"], "https://hogehoge.com/gcptool"],
            "リモート、ポートあり (8080)、パスあり (gcptool)"       => [false, ["hogehoge.com:8080", "gcptool"], "https://hogehoge.com:8080/gcptool"],
        ];
    }

    /**
     * `CFUtils::getBaseUrl()` メソッドのテスト。
     * `providerGetBaseUrl` から提供されるデータを使用してテストを実行します。
     *
     * @param bool $isLocal ローカル環境かどうか。
     * @param array $serverParams モックリクエストのサーバーパラメータ。
     * @param string $expected 期待されるベースURL。
     */
    #[DataProvider("providerGetBaseUrl")]
    public function testGetBaseUrl(bool $isLocal, array $serverParams, string $expected): void
    {
        $mock = new MockServerRequestInterface($serverParams);
        $this->assertSame($expected, CFUtils::getBaseUrl($isLocal, $mock));
    }
}
