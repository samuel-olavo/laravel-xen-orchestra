<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Concerns;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Models\GenericObject;

/** `GET /<collection>/<id>/messages` — XAPI messages attached to the object. */
trait HasMessages
{
    use HydratesModels;

    /** @param list<string> $fields */
    public function messages(array $fields = [], array $query = []): Collection
    {
        $payload = $this->client->get(
            $this->path('messages'),
            ($fields === [] ? [] : ['fields' => $fields]) + $query,
        );

        return $this->hydrateMany($payload, GenericObject::class, $this->client);
    }
}
