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

    public static function getBasePath(bool $isLocal, ServerRequestInterface $request): string
    {
        $urlElems = self::__getUrlElements($isLocal, $request);
        return $urlElems["path"];
    }

    public static function getBaseUrl(bool $isLocal, ServerRequestInterface $request): string
    {
        $urlElems = self::__getUrlElements($isLocal, $request);
        return $urlElems["scheme"] . "://" . $urlElems["host"] . ":" . $urlElems["port"] . $urlElems["path"];
    }

    private static function __getUrlElements(bool $isLocal, ServerRequestInterface $request): array
    {
        $protocol = $isLocal ? "http" : "https";
        $fullUrl = "{$protocol}://{$request->getServerParams()['HTTP_HOST']}{$request->getServerParams()['REQUEST_URI']}";
        return parse_url($fullUrl);
    }
}
