<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Testing;

use GuzzleHttp\Psr7\Response as PsrResponse;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SamuelOlavo\XenOrchestra\Client\StreamingClientInterface;

/**
 * A PSR-18 client that answers from a stub table instead of the network.
 *
 * This is the payoff of injecting the HTTP client rather than reaching for a
 * facade inside the core: testing this package, and testing *your* code that
 * uses it, needs no HTTP interception and no global state.
 *
 * Stub keys are matched against the request path (the `/rest/v0` prefix is
 * ignored) and may contain `*` wildcards. First match wins, so register
 * specific patterns before general ones.
 */
class FakeHttpClient implements StreamingClientInterface
{
    public function sendStreamingRequest(RequestInterface $request): ResponseInterface
    {
        return $this->sendRequest($request);
    }

    /** @var array<string, list<array{status:int, body:mixed, headers:array<string,string>}>> */
    protected array $stubs = [];

    /** @var list<RequestInterface> */
    protected array $recorded = [];

    /** @param array<string, mixed> $stubs path pattern => payload */
    public function __construct(array $stubs = [])
    {
        foreach ($stubs as $pattern => $payload) {
            $this->stub($pattern, $payload);
        }
    }

    /**
     * Queue a JSON response for a path pattern. The payload is a PHP value and
     * gets JSON-encoded, so `stub('vms/abc/actions/start', '/rest/v0/tasks/1')`
     * produces a quoted JSON string — which is what XO actually sends back for
     * an action.
     *
     * Calling it repeatedly for the same pattern queues successive responses,
     * which is how you test a task that is pending on the first read and
     * finished on the second. The final stub stays in place for further reads.
     *
     * @param  array<string, string>  $headers
     */
    public function stub(string $pattern, mixed $payload, int $status = 200, array $headers = []): static
    {
        return $this->stubRaw(
            $pattern,
            json_encode($payload, JSON_THROW_ON_ERROR),
            $status,
            ['Content-Type' => 'application/json'] + $headers,
        );
    }

    /**
     * Queue a response body verbatim, without JSON encoding — for ndjson
     * streams, `audit.txt`, or deliberately malformed payloads.
     *
     * @param  array<string, string>  $headers
     */
    public function stubRaw(string $pattern, string $body, int $status = 200, array $headers = []): static
    {
        $this->stubs[$pattern][] = [
            'status' => $status,
            'body' => $body,
            'headers' => $headers,
        ];

        return $this;
    }

    /** Queue an error response. */
    public function stubError(string $pattern, int $status, mixed $payload = null): static
    {
        return $this->stub($pattern, $payload ?? ['message' => 'stubbed error'], $status);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->recorded[] = $request;

        $path = $this->normalise($request->getUri()->getPath());

        foreach ($this->stubs as $pattern => $queue) {
            if ($queue === [] || ! $this->matches($pattern, $path)) {
                continue;
            }

            // Keep the last stub in place so repeated reads keep working.
            $stub = count($queue) > 1 ? array_shift($this->stubs[$pattern]) : $queue[0];

            return new PsrResponse(
                $stub['status'],
                $stub['headers'] === [] ? ['Content-Type' => 'application/json'] : $stub['headers'],
                $stub['body'],
            );
        }

        return new PsrResponse(404, ['Content-Type' => 'application/json'], json_encode([
            'message' => sprintf('No stub registered for %s %s', $request->getMethod(), $path),
        ], JSON_THROW_ON_ERROR));
    }

    protected function normalise(string $path): string
    {
        $path = ltrim($path, '/');

        return str_starts_with($path, 'rest/v0/') ? substr($path, 8) : $path;
    }

    protected function matches(string $pattern, string $path): bool
    {
        $pattern = $this->normalise($pattern);

        if ($pattern === $path) {
            return true;
        }

        if (! str_contains($pattern, '*')) {
            return false;
        }

        $regex = '#^'.str_replace('\*', '.*', preg_quote($pattern, '#')).'$#';

        return preg_match($regex, $path) === 1;
    }

    // ---------------------------------------------------------------------
    // Assertions
    // ---------------------------------------------------------------------

    /** @return list<RequestInterface> */
    public function recorded(): array
    {
        return $this->recorded;
    }

    public function lastRequest(): ?RequestInterface
    {
        return $this->recorded === [] ? null : $this->recorded[array_key_last($this->recorded)];
    }

    public function requestCount(): int
    {
        return count($this->recorded);
    }

    /** Assert a request was made whose path (and optionally method) matches. */
    public function assertSent(string $pattern, ?string $method = null): static
    {
        foreach ($this->recorded as $request) {
            $path = $this->normalise($request->getUri()->getPath());

            if ($this->matches($pattern, $path) && ($method === null || $request->getMethod() === $method)) {
                Assert::assertTrue(true);

                return $this;
            }
        }

        Assert::fail(sprintf(
            'Expected a request to [%s]%s, but the ones made were: %s',
            $pattern,
            $method === null ? '' : ' with method '.$method,
            $this->describeRecorded(),
        ));
    }

    public function assertNothingSent(): static
    {
        Assert::assertSame([], $this->recorded, 'Expected no requests, got: '.$this->describeRecorded());

        return $this;
    }

    /** Assert the query string of the most recent request contains a pair. */
    public function assertQuery(string $key, string $value): static
    {
        $request = $this->lastRequest();

        Assert::assertNotNull($request, 'No request was made.');

        parse_str($request->getUri()->getQuery(), $query);

        Assert::assertSame(
            $value,
            $query[$key] ?? null,
            sprintf('Query parameter [%s] did not match. Full query: %s', $key, $request->getUri()->getQuery()),
        );

        return $this;
    }

    protected function describeRecorded(): string
    {
        if ($this->recorded === []) {
            return '(none)';
        }

        return implode(', ', array_map(
            fn (RequestInterface $r) => $r->getMethod().' '.$this->normalise($r->getUri()->getPath()),
            $this->recorded,
        ));
    }
}
