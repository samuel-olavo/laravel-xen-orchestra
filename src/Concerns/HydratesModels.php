<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Concerns;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Client\XenOrchestraClient;
use SamuelOlavo\XenOrchestra\Models\Model;

/**
 * Turns a raw collection payload into models.
 *
 * Handles the single most surprising behaviour of this API: a collection
 * requested without `fields` comes back as a list of href *strings*, not
 * objects. Those become partial models, so calling code keeps working and can
 * call `fetch()` (or, better, ask for fields up front).
 */
trait HydratesModels
{
    /**
     * @param  class-string<Model>  $class
     * @return Collection<Model>
     */
    protected function hydrateMany(mixed $payload, string $class, XenOrchestraClient $client): Collection
    {
        if (! is_array($payload)) {
            return new Collection();
        }

        $models = [];

        foreach ($payload as $item) {
            $model = $this->hydrateOne($item, $class, $client);

            if ($model !== null) {
                $models[] = $model;
            }
        }

        return new Collection($models);
    }

    /**
     * @param  class-string<Model>  $class
     * @return Model|null
     */
    protected function hydrateOne(mixed $item, string $class, XenOrchestraClient $client): ?Model
    {
        if (is_string($item)) {
            return new $class(['href' => $item], $client);
        }

        if (is_array($item)) {
            return new $class($item, $client);
        }

        return null;
    }
}
