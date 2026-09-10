<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Client\Response;
use SamuelOlavo\XenOrchestra\Concerns\DeletesObject;
use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTags;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;
use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;

class VirtualMachineTemplate extends Model
{
    use HydratesModels;
    use HasTags;
    use HasTasks;
    use HasMessages;
    use HasAlarms;
    use DeletesObject;
    public static function endpoint(): string
    {
        return 'vm-templates';
    }

    public function disks(array $fields = [], array $query = []): Collection
    {
        return $this->hydrateMany(
            $this->client->get($this->path('vdis'), ($fields === [] ? [] : ['fields' => $fields]) + $query),
            VirtualDisk::class,
            $this->client,
        );
    }

    /** The response body is streamed; use saveTo() or consume its PSR-7 stream. */
    public function export(string $format = 'xva', array $query = []): Response
    {
        return $this->client->download($this->path().'.'.rawurlencode($format), $query);
    }
}
