<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\Task;

/**
 * `/tasks`
 *
 * @method Task find(string $id, array $fields = [])
 */
class Tasks extends Resource
{
    public function endpoint(): string
    {
        return 'tasks';
    }

    public function modelClass(): string
    {
        return Task::class;
    }

    public function pending(): static
    {
        return $this->where('status', 'pending');
    }

    public function failed(): static
    {
        return $this->where('status', 'failure');
    }

    /**
     * `DELETE /tasks` — clear finished tasks from the list.
     */
    public function purge(): void
    {
        $this->client->delete('tasks', $this->query());
    }
}
