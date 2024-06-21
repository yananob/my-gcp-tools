<?php

declare(strict_types=1);

namespace yananob\my_gcptools;

use Psr\Http\Message\ServerRequestInterface;
use CloudEvents\V1\CloudEventInterface;

final class GcpUtils
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
}
