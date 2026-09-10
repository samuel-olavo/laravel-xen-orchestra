<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Client\XenOrchestraClient;

/**
 * An unresolved pointer to another object.
 *
 * The API is self-describing: every object carries an `href`, and nested
 * references carry one too. That is what makes relation-following possible
 * without this package hardcoding a map of which type relates to which.
 *
 * Resolving costs a request, so it is an explicit `fetch()` and never implicit.
 */
final class Reference
{
    public function __construct(
        public readonly string $href,
        public readonly ?string $type,
        public readonly ?string $uuid,
        private readonly XenOrchestraClient $client,
    ) {
    }

    /**
     * Resolve the reference into the most specific model class available,
     * falling back to a generic object for collections this package does not
     * model explicitly.
     *
     * @param  list<string>  $fields
     */
    public function fetch(array $fields = []): Model
    {
        $payload = $this->client->get($this->href, $fields === [] ? [] : ['fields' => $fields]);
        $attributes = is_array($payload) ? $payload : [];

        $attributes['href'] ??= $this->href;

        $class = ModelResolver::forHref($this->href);

        return new $class($attributes, $this->client);
    }

    /**
     * The collection segment this reference points at, e.g. `srs` for
     * `/rest/v0/srs/<uuid>`. Useful for branching without a request.
     */
    public function collection(): ?string
    {
        return ModelResolver::collectionFor($this->href);
    }

    public function __toString(): string
    {
        return $this->href;
    }
}
