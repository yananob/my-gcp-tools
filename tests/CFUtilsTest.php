<?php

declare(strict_types=1);

// require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use yananob\my_gcptools\CFUtils;

class MockServerRequestInterface
{
    public function __construct(private array $serverParams)
    {
    }
    public function getServerParams(): array
    {
        return [
            "HTTP_HOST" => $this->serverParams[0],
            "K_SERVICE" => $this->serverParams[1],
        ];
    }
}

final class CFUtilsTest extends TestCase
{
    public static function providerGetBasePath(): array
    {
        return [
            // case => [isLocal, serverParams, expected]
            "local, with path" => [true, ["localhost", ""], "/"],
            "local, without path" => [true, ["localhost", "gcptool"], "/gcptool"],
            "remote, with path" => [false, ["hogehoge.com", ""], "/"],
            "remote, without path" => [false, ["hogehoge.com", "gcptool"], "/gcptool"],
        ];
    }

    #[DataProvider("providerGetBasePath")]
    public function testGetBasePath(bool $isLocal, array $serverParams, string $expected): void
    {
        $mock = new MockServerRequestInterface($serverParams);
        $this->assertSame($expected, CFUtils::getBasePath($isLocal, $mock));
    }

    public static function providerGetBaseUrl(): array
    {
        return [
            // case => [isLocal, serverParams, expected]
            "local, without port & path" => [true, ["localhost", ""], "http://localhost/"],
            "local, with port" => [true, ["localhost:8080", ""], "http://localhost:8080/"],
            "local, without path" => [true, ["localhost", "gcptool"], "http://localhost/gcptool"],
            "local, with port & path" => [true, ["localhost:8080", "gcptool"], "http://localhost:8080/gcptool"],
            "remote, without port & path" => [false, ["hogehoge.com", ""], "https://hogehoge.com/"],
            "remote, with port" => [false, ["hogehoge.com:8080", ""], "https://hogehoge.com:8080/"],
            "remote, without path" => [false, ["hogehoge.com", "gcptool"], "https://hogehoge.com/gcptool"],
            "remote, with port & path" => [false, ["hogehoge.com:8080", "gcptool"], "https://hogehoge.com:8080/gcptool"],
        ];
    }

    #[DataProvider("providerGetBaseUrl")]
    public function testGetBaseUrl(bool $isLocal, array $serverParams, string $expected): void
    {
        $mock = new MockServerRequestInterface($serverParams);
        $this->assertSame($expected, CFUtils::getBaseUrl($isLocal, $mock));
    }
}
