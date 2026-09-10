<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use ArrayAccess;
use JsonSerializable;
use SamuelOlavo\XenOrchestra\Client\XenOrchestraClient;

/**
 * Base for every object returned by the API.
 *
 * One rule governs this class, and it is worth stating plainly because it is
 * easy to break later: **reading a property never performs HTTP.** Attribute
 * access returns whatever the API already gave us, or null. Anything that
 * touches the network is a method with a verb in its name — `fetch()`,
 * `tasks()`, `alarms()`. Eloquent-style lazy loading on property access reads
 * beautifully in a README and produces invisible N+1 storms in production.
 *
 * @implements ArrayAccess<string, mixed>
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
    /** @param array<string, mixed> $attributes */
    public function __construct(
        protected array $attributes,
        protected XenOrchestraClient $client,
    ) {
    }

    /**
     * The collection path this model lives under, e.g. `vms`.
     */
    abstract public static function endpoint(): string;

    // ---------------------------------------------------------------------
    // Identity
    // ---------------------------------------------------------------------

    public function id(): ?string
    {
        $id = $this->attributes['id'] ?? $this->attributes['uuid'] ?? null;

        if (is_string($id)) {
            return $id;
        }

        // A collection listed without `fields` yields bare href strings, so the
        // id has to come back out of the path.
        $href = $this->href();

        return $href === null ? null : basename(parse_url($href, PHP_URL_PATH) ?: $href);
    }

    public function href(): ?string
    {
        $href = $this->attributes['href'] ?? null;

        if (is_string($href)) {
            return $href;
        }

        $id = $this->attributes['id'] ?? $this->attributes['uuid'] ?? null;

        return is_string($id) ? static::endpoint().'/'.rawurlencode($id) : null;
    }

    /**
     * True when all we hold is a reference.
     *
     * Listing a collection without `fields` returns href strings only — the
     * single most common surprise when working with this API. Call `fetch()` to
     * fill the object in, or pass `fields()` to the query in the first place.
     */
    public function isPartial(): bool
    {
        $known = array_diff(array_keys($this->attributes), ['href', 'id', 'uuid']);

        return $known === [];
    }

    // ---------------------------------------------------------------------
    // Attributes — pure data, never network
    // ---------------------------------------------------------------------

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }

    public function jsonSerialize(): array
    {
        return $this->attributes;
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->attributes[(string) $offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[(string) $offset]);
    }

    // ---------------------------------------------------------------------
    // Network — always explicit
    // ---------------------------------------------------------------------

    /**
     * Re-read this object from the XOA, refreshing it in place.
     *
     * @param  list<string>  $fields  Restrict the payload; empty means everything.
     */
    public function fetch(array $fields = []): static
    {
        $href = $this->href();

        if ($href === null) {
            return $this;
        }

        $payload = $this->client->get($href, $fields === [] ? [] : ['fields' => $fields]);

        if (is_array($payload)) {
            $this->attributes = $payload + $this->attributes;
        }

        return $this;
    }

    /**
     * A nested `href` reference, e.g. an alarm's `object`.
     *
     * Returns a Reference rather than a Model because resolving it costs a
     * request — you call `->fetch()` on it when you actually want the object.
     */
    public function reference(string $key): ?Reference
    {
        $value = $this->attributes[$key] ?? null;

        if (is_string($value) && str_contains($value, '/')) {
            return new Reference($value, null, null, $this->client);
        }

        if (! is_array($value) || ! isset($value['href'])) {
            return null;
        }

        return new Reference(
            $value['href'],
            $value['type'] ?? null,
            $value['uuid'] ?? null,
            $this->client,
        );
    }

    public function client(): XenOrchestraClient
    {
        return $this->client;
    }

    /** @internal used by resources when hydrating results */
    protected function path(string ...$segments): string
    {
        $href = $this->href();
        if ($href === null) {
            throw new \LogicException('A remote operation requires an object ID or href.');
        }

        return implode('/', [rtrim($href, '/'), ...$segments]);
    }
}
