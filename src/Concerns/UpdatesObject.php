<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Concerns;

trait UpdatesObject
{
    /**
     * Send API field names unchanged. Call fetch() to refresh local attributes.
     * XO applies fields sequentially and does not roll back earlier changes.
     */
    public function update(array $attributes): static
    {
        $this->client->patch($this->path(), $attributes);

        return $this;
    }
}
