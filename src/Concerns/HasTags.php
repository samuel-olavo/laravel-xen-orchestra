<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Concerns;

/**
 * `PUT`/`DELETE /<collection>/<id>/tags/<tag>` — 10 collections, 20 endpoints.
 *
 * Note these are among the very few write operations the REST API exposes, and
 * they are synchronous: no task is returned.
 */
trait HasTags
{
    /** Tags already present on the object — pure data, no request. */
    public function tags(): array
    {
        $tags = $this->get('tags', []);

        return is_array($tags) ? array_values($tags) : [];
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags(), true);
    }

    public function addTag(string $tag): static
    {
        $this->client->put($this->path('tags', rawurlencode($tag)));

        $tags = $this->tags();

        if (! in_array($tag, $tags, true)) {
            $tags[] = $tag;
            $this->attributes['tags'] = $tags;
        }

        return $this;
    }

    public function removeTag(string $tag): static
    {
        $this->client->delete($this->path('tags', rawurlencode($tag)));

        $this->attributes['tags'] = array_values(
            array_filter($this->tags(), static fn ($existing) => $existing !== $tag)
        );

        return $this;
    }
}
