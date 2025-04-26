<?php

declare(strict_types=1);

namespace yananob\MyGcpTools;

use Psr\Http\Message\ServerRequestInterface;
use CloudEvents\V1\CloudEventInterface;

final class CFUtils
{
    public function __construct() {}

    public static function isLocalHttp(ServerRequestInterface $request): bool
    {
        return str_contains($request->getHeader("Host")[0], "localhost") || str_contains($request->getHeader("Host")[0], "127.0.0.1");
    }

    public static function isLocalEvent(CloudEventInterface $event): bool
    {
        return ($event->getId() === "9999999999");
    }

    public static function getFunctionName(string $defaultName = ''): string
    {
        $funcName = getenv('K_SERVICE');
        return is_bool($funcName) ? $defaultName : $funcName;
    }

    public static function isTestingEnv(): bool
    {
        $funcName = self::getFunctionName('');
        if (empty($funcName)) {
            return true;
        }
        return str_contains($funcName, "test");
    }

    /**
     * @param bool $isLocal
     * @param ServerRequestInterface $request
     * @return string
     */
    public static function getBasePath(bool $isLocal, $request): string
    {
        $urlElems = self::__getUrlElements($isLocal, $request);
        return $urlElems["path"];
    }

    /**
     * @param bool $isLocal
     * @param ServerRequestInterface $request
     * @return string
     */
    public static function getBaseUrl(bool $isLocal, $request): string
    {
        $urlElems = self::__getUrlElements($isLocal, $request);
        $port = (!empty($urlElems["port"]) ? (":" . $urlElems["port"]) : "");
        return $urlElems["scheme"] . "://" . $urlElems["host"] . $port . $urlElems["path"];
    }

    /**
     * @param bool $isLocal
     * @param ServerRequestInterface $request
     * @return array
     */
    private static function __getUrlElements(bool $isLocal, $request): array
    {
        $params = $request->getServerParams();
        $protocol = $isLocal ? "http" : "https";
        $path = "/" . (array_key_exists('K_SERVICE', $params) ? $params["K_SERVICE"] : "");
        $fullUrl = "{$protocol}://{$params['HTTP_HOST']}{$path}";
        return parse_url($fullUrl);
    }

    /**
     * リクエストからGET・POSTフォーム・JSONペイロードをマージして取得する
     *
     * @param Psr\Http\Message\ServerRequestInterface $request
     * @return array
     */
    function getMergedRequestParams(ServerRequestInterface $request): array
    {
        // Content-Typeチェック
        $contentType = $request->getHeaderLine('Content-Type');
        // フォーム or JSONのボディパラメータ
        $bodyParams = [];
        if (stripos($contentType, 'application/json') !== false) {
            // JSONなら、生ボディをパース
            $rawBody = (string) $request->getBody();
            $bodyParams = json_decode($rawBody, true) ?? [];
        } elseif (
            stripos($contentType, 'application/x-www-form-urlencoded') !== false ||
            stripos($contentType, 'multipart/form-data') !== false
        ) {
            // フォームデータなら、パース済みボディを取得
            $bodyParams = (array) $request->getParsedBody();
        } else {
            throw new \InvalidArgumentException("Unsupported Content-Type: {$contentType}");
        }

        // マージ（後勝ち）
        return array_merge((array)$request->getQueryParams(), $bodyParams);
    }
}
