<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Concerns;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Models\GenericObject;

/**
 * `GET /<collection>/<id>/alarms`, available on 13 different collections.
 *
 * This trait and its siblings are why writing this package by hand was
 * feasible: the API is not 200 distinct shapes, it is ~30 collections crossed
 * with a handful of repeated sub-resources.
 */
trait HasAlarms
{
    use HydratesModels;

    /** @param list<string> $fields */
    public function alarms(array $fields = []): Collection
    {
        $payload = $this->client->get(
            $this->path('alarms'),
            $fields === [] ? [] : ['fields' => $fields],
        );

        return $this->hydrateMany($payload, GenericObject::class, $this->client);
    }
}
