<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\RunsActions;

class BackupArchive extends Model
{
    use RunsActions;

    public static function endpoint(): string
    {
        return 'backup-archives';
    }

    /** Mount a backup disk as a read-only SR. Requires an administrator. */
    public function mountLiveDisk(array $attributes, bool $sync = false): Task
    {
        return $this->action('mount_live_disk', $attributes, $sync);
    }

    public function unmountLiveDisk(string $liveDiskId, bool $sync = false): Task
    {
        $payload = $this->client->post(
            $this->path('live_disks', rawurlencode($liveDiskId), 'actions', 'unmount'),
            [],
            $sync ? ['sync' => true] : [],
        );

        return Task::fromActionResponse($payload, $this->client, $sync);
    }
}
