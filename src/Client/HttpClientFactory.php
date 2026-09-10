<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Client;

use Psr\Http\Client\ClientInterface;

/**
 * Builds the default PSR-18 client.
 *
 * This is the only place in the package that names a concrete HTTP
 * implementation. XenOrchestraClient itself only ever sees the interface, so
 * swapping Guzzle for anything else means passing your own client in — nothing
 * in the core needs to change.
 *
 * It also exists because two things you genuinely need against a XOA — TLS
 * verification toggles and timeouts — are not expressible through PSR-18. They
 * belong to the transport, so they are configured here.
 */
final class HttpClientFactory
{
    /**
     * @param  bool|string  $verifySsl  false to accept self-signed certificates,
     *                                  or the path to a CA bundle to pin against.
     */
    public static function make(
        bool|string $verifySsl = true,
        int $timeout = 30,
        int $connectTimeout = 10,
    ): ClientInterface {
        return new StreamingHttpClient([
            'verify' => $verifySsl,
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
            'http_errors' => false,
            'allow_redirects' => false,
        ]);
    }

    /**
     * A client tuned for long-polling task endpoints (`?wait=true`), where the
     * XOA legitimately holds the connection open until the task finishes.
     */
    public static function makeForWaiting(
        bool|string $verifySsl = true,
        int $timeout = 300,
        int $connectTimeout = 10,
    ): ClientInterface {
        return self::make($verifySsl, $timeout, $connectTimeout);
    }
}
