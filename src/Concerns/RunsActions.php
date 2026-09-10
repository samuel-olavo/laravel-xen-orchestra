<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Concerns;

use SamuelOlavo\XenOrchestra\Models\Task;

trait RunsActions
{
    protected function action(string $action, array $body = [], bool $sync = false): Task
    {
        $payload = $this->client->post(
            $this->path('actions', $action),
            $body,
            $sync ? ['sync' => true] : [],
        );

        return Task::fromActionResponse($payload, $this->client, $sync);
    }
}
