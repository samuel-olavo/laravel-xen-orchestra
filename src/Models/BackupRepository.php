<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\RunsActions;
use SamuelOlavo\XenOrchestra\Concerns\UpdatesObject;

class BackupRepository extends Model
{
    use RunsActions;
    use UpdatesObject;
    public static function endpoint(): string
    {
        return 'backup-repositories';
    }

    public function forget(bool $sync = false): Task
    {
        return $this->action('forget', sync: $sync);
    }

    public function benchmark(bool $sync = false): Task
    {
        return $this->action('benchmark', sync: $sync);
    }

    public function health(): array
    {
        return (array) $this->client->get($this->path('health'));
    }

    /** Options: vmUuid, merge and remove. Omitted options use server defaults. */
    public function reclaimSpace(array $attributes = [], bool $sync = false): Task
    {
        return $this->action('reclaim-space', $attributes, $sync);
    }
}
