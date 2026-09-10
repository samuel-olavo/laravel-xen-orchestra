<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Client;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use SamuelOlavo\XenOrchestra\Exceptions\XenOrchestraException;

/**
 * A thin, immutable view over a PSR-7 response.
 *
 * Exists so the escape hatch (Client::request()) can hand back status codes and
 * headers without leaking PSR-7 into every call site, while the common helpers
 * still return plain decoded arrays.
 */
final readonly class Response
{
    public function __construct(
        public ResponseInterface $psr,
        public string $method,
        public string $uri,
    ) {
    }

    public function status(): int
    {
        return $this->psr->getStatusCode();
    }

    public function successful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    public function header(string $name): ?string
    {
        $values = $this->psr->getHeader($name);

        return $values === [] ? null : $values[0];
    }

    public function body(): string
    {
        $stream = $this->psr->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        return (string) $stream;
    }

    /**
     * Decoded JSON body, or null for empty bodies (204 No Content, and some XO
     * action endpoints that answer with nothing at all).
     *
     * A non-JSON body is the classic symptom of a reverse proxy or SSO portal
     * sitting in front of the XOA and answering with HTML, so the failure names
     * that possibility instead of surfacing a bare "Syntax error".
     *
     * @return array<array-key, mixed>|scalar|null
     */
    public function json(): mixed
    {
        $raw = trim($this->body());

        if ($raw === '') {
            return null;
        }

        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new XenOrchestraException(
                sprintf(
                    'Expected JSON from %s %s but got %s. First bytes: %s',
                    $this->method,
                    $this->uri,
                    $this->header('Content-Type') ?? 'an unknown content type',
                    var_export(mb_substr($raw, 0, 120), true),
                ),
                previous: $e,
            );
        }
    }

    /**
     * Decoded body coerced to an array. Scalar bodies (XO returns a bare
     * JSON string for task references) are wrapped rather than discarded.
     *
     * @return array<array-key, mixed>
     */
    public function array(): array
    {
        $decoded = $this->json();

        return match (true) {
            is_array($decoded) => $decoded,
            $decoded === null => [],
            default => ['value' => $decoded],
        };
    }

    /**
     * Parse an `ndjson=true` response into one array per line.
     *
     * @return list<array<array-key, mixed>>
     */
    public function ndjson(): array
    {
        $rows = [];

        foreach (explode("\n", $this->body()) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                $rows[] = $decoded;
            }
        }

        return $rows;
    }
}
