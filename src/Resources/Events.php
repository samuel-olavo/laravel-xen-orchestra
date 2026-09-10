<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Client\EventStream;
use SamuelOlavo\XenOrchestra\Client\StreamingClientInterface;
use SamuelOlavo\XenOrchestra\Client\XenOrchestraClient;

final class Events
{
    public function __construct(private XenOrchestraClient $client)
    {
    }

    public function open(): EventStream
    {
        if (! $this->client->httpClient() instanceof StreamingClientInterface) {
            throw new \LogicException('SSE requires a StreamingClientInterface transport. Use XenOrchestraClient::make() or implement that interface.');
        }
        $response = $this->client->request('GET', 'events', headers: ['Accept' => 'text/event-stream'], stream: true);
        if (! str_starts_with(strtolower($response->header('Content-Type') ?? ''), 'text/event-stream')) {
            $response->psr->getBody()->close();
            throw new \UnexpectedValueException('Expected an SSE response from /events.');
        }

        return new EventStream($response->psr->getBody());
    }

    /** Use the connection ID from the init event; collection names use XO types, e.g. VM. */
    public function subscribe(string $connectionId, string $collection, array|string|null $fields = null): string
    {
        $body = ['collection' => $collection];
        if ($fields !== null) {
            $body['fields'] = $fields;
        }
        $result = $this->client->post('events/'.rawurlencode($connectionId).'/subscriptions', $body);

        return $result['id'];
    }

    public function unsubscribe(string $connectionId, string $subscriptionId): void
    {
        $this->client->delete('events/'.rawurlencode($connectionId).'/subscriptions/'.rawurlencode($subscriptionId));
    }
}
