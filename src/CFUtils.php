<?php

declare(strict_types=1);

namespace yananob\my_gcptools;

use Psr\Http\Message\ServerRequestInterface;
use CloudEvents\V1\CloudEventInterface;

final class CFUtils
{
    public function __construct()
    {
    }

    public static function isLocalHttp(ServerRequestInterface $request): bool
    {
        return str_contains($request->getHeader("Host")[0], "localhost") || str_contains($request->getHeader("Host")[0], "127.0.0.1");
    }

    public static function isLocalEvent(CloudEventInterface $event): bool
    {
        return ($event->getId() === "9999999999");
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
}
