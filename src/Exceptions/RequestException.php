<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Thrown when Xen Orchestra answers with a non-2xx status code.
 *
 * The raw response is kept so callers can inspect headers or the decoded body
 * without having to re-issue the request.
 */
class RequestException extends XenOrchestraException
{
    public function __construct(
        string $message,
        public readonly int $status,
        public readonly string $method,
        public readonly string $uri,
        public readonly ?ResponseInterface $response = null,
        public readonly ?array $body = null,
    ) {
        parent::__construct($message, $status);
    }

    public static function fromResponse(string $method, string $uri, ResponseInterface $response): self
    {
        $status = $response->getStatusCode();
        $body = self::decode($response);
        $detail = self::detailFrom($body) ?? $response->getReasonPhrase();

        $class = match (true) {
            $status === 401, $status === 403 => AuthenticationException::class,
            $status === 404 => NotFoundException::class,
            $status >= 500 => ServerException::class,
            default => self::class,
        };

        /** @var self $exception */
        $exception = new $class(
            sprintf('XO %s %s failed with HTTP %d%s', $method, $uri, $status, $detail !== '' ? ': '.$detail : ''),
            $status,
            $method,
            $uri,
            $response,
            $body,
        );

        return $exception;
    }

    private static function decode(ResponseInterface $response): ?array
    {
        $raw = (string) $response->getBody();

        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : ['message' => mb_substr($raw, 0, 500)];
    }

    /**
     * XO is not perfectly consistent about where the human-readable error sits,
     * so we look in the places it is known to use.
     */
    private static function detailFrom(?array $body): ?string
    {
        if ($body === null) {
            return null;
        }

        foreach (['message', 'error', 'detail', 'description'] as $key) {
            if (isset($body[$key]) && is_string($body[$key])) {
                return $body[$key];
            }
        }

        if (isset($body['error']['message']) && is_string($body['error']['message'])) {
            return $body['error']['message'];
        }

        return null;
    }
}
