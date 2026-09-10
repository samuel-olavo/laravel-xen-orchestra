<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Client\FilterBuilder;
use SamuelOlavo\XenOrchestra\Client\XenOrchestraClient;
use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;
use SamuelOlavo\XenOrchestra\Models\Model;

/**
 * Fluent query builder for one XO collection.
 *
 * Every fluent method returns a clone, so a resource handle can be reused
 * without one query's constraints leaking into the next.
 *
 * The API supports exactly four query parameters — `fields`, `filter`, `limit`
 * and `ndjson`. There is no offset and no cursor, so there is no pagination to
 * model: `limit` is the only lever.
 */
abstract class Resource
{
    use HydratesModels;

    /** @var list<string> */
    protected array $fields = [];

    protected ?string $filter = null;

    protected ?int $limit = null;

    public function __construct(protected XenOrchestraClient $client)
    {
    }

    /** The collection path, e.g. `vms`. */
    abstract public function endpoint(): string;

    /** @return class-string<Model> */
    abstract public function modelClass(): string;

    // ---------------------------------------------------------------------
    // Query construction
    // ---------------------------------------------------------------------

    /**
     * Restrict the properties returned.
     *
     * This matters more than it looks: a collection requested **without**
     * `fields` comes back as a list of bare href strings rather than objects.
     * Asking for the fields you need is both faster and the difference between
     * getting data and getting pointers.
     *
     * @param  string|list<string>  ...$fields
     */
    public function fields(string|array ...$fields): static
    {
        $flat = [];

        foreach ($fields as $field) {
            foreach ((array) $field as $one) {
                $flat[] = $one;
            }
        }

        $clone = clone $this;
        $clone->fields = array_values(array_unique([...$this->fields, ...$flat]));

        return $clone;
    }

    /**
     * Set the filter expression.
     *
     * Accepts a raw complex-matcher string — the primary and fully expressive
     * form — or a callback receiving a {@see FilterBuilder} for the common
     * cases:
     *
     *   ->filter('power_state:Running !tags:test')
     *   ->filter(fn ($f) => $f->where('power_state', 'Running')->whereNot('tags', 'test'))
     *
     * @param  string|FilterBuilder|callable(FilterBuilder): FilterBuilder  $filter
     */
    public function filter(string|FilterBuilder|callable $filter): static
    {
        $expression = match (true) {
            is_string($filter) => trim($filter),
            $filter instanceof FilterBuilder => $filter->toString(),
            default => $filter(FilterBuilder::make())->toString(),
        };

        $clone = clone $this;
        $clone->filter = $expression === '' ? null : $expression;

        return $clone;
    }

    /**
     * Sugar over {@see filter()} for simple equality, ANDed with any existing
     * expression. Anything more involved deserves the raw string.
     */
    public function where(string|array $field, mixed $value = null): static
    {
        return $this->appendFilter(
            FilterBuilder::make()->where($field, $value)->toString()
        );
    }

    public function whereNot(string|array $field, mixed $value = null): static
    {
        return $this->appendFilter(
            FilterBuilder::make()->whereNot($field, $value)->toString()
        );
    }

    /** @param list<mixed> $values */
    public function whereIn(string $field, array $values): static
    {
        return $this->appendFilter(
            FilterBuilder::make()->whereIn($field, $values)->toString()
        );
    }

    protected function appendFilter(string $expression): static
    {
        if ($expression === '') {
            return $this;
        }

        $clone = clone $this;
        $clone->filter = $this->filter === null ? $expression : $this->filter.' '.$expression;

        return $clone;
    }

    public function limit(int $limit): static
    {
        $clone = clone $this;
        $clone->limit = $limit;

        return $clone;
    }

    // ---------------------------------------------------------------------
    // Execution
    // ---------------------------------------------------------------------

    /** @return Collection<Model> */
    public function get(): Collection
    {
        $payload = $this->client->get($this->endpoint(), $this->query());

        return $this->hydrateMany($payload, $this->modelClass(), $this->client);
    }

    /**
     * All objects in the collection, optionally with fields.
     *
     * @param  list<string>  $fields
     * @return Collection<Model>
     */
    public function all(array $fields = []): Collection
    {
        return ($fields === [] ? $this : $this->fields($fields))->get();
    }

    public function first(): ?Model
    {
        return $this->limit(1)->get()->first();
    }

    /**
     * `GET /<collection>/<id>`
     *
     * @param  list<string>  $fields
     *
     * @throws \SamuelOlavo\XenOrchestra\Exceptions\NotFoundException
     */
    public function find(string $id, array $fields = []): Model
    {
        $payload = $this->client->get(
            $this->endpoint().'/'.rawurlencode($id),
            $fields === [] ? [] : ['fields' => $fields],
        );

        $class = $this->modelClass();
        $attributes = is_array($payload) ? $payload : [];

        return new $class($attributes, $this->client);
    }

    /**
     * Stream the collection as newline-delimited JSON.
     *
     * @return Collection<Model>
     */
    public function ndjson(): Collection
    {
        $response = $this->client->request(
            'GET',
            $this->endpoint(),
            query: $this->query() + ['ndjson' => true],
        );

        return $this->hydrateMany($response->ndjson(), $this->modelClass(), $this->client);
    }

    /**
     * The query parameters this builder will send — handy in tests and when
     * working out why XO returned something unexpected.
     *
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return array_filter([
            'fields' => $this->fields === [] ? null : $this->fields,
            'filter' => $this->filter,
            'limit' => $this->limit,
        ], static fn ($value) => $value !== null);
    }

    /** The full URL this builder will request. Useful for debugging. */
    public function toUrl(): string
    {
        return $this->client->uriFor($this->endpoint(), $this->query());
    }

    public function client(): XenOrchestraClient
    {
        return $this->client;
    }
}
