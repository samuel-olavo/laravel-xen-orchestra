<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Concerns;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Models\Task;

/** `GET /<collection>/<id>/tasks` — tasks currently attached to the object. */
trait HasTasks
{
    use HydratesModels;

    /** @param list<string> $fields */
    public function tasks(array $fields = [], array $query = []): Collection
    {
        $payload = $this->client->get(
            $this->path('tasks'),
            ($fields === [] ? [] : ['fields' => $fields]) + $query,
        );

        return $this->hydrateMany($payload, Task::class, $this->client);
    }
}
