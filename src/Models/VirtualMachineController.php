<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTags;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;
use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;

class VirtualMachineController extends Model
{
    use HydratesModels;
    use HasTags;
    use HasTasks;
    use HasMessages;
    use HasAlarms;
    public static function endpoint(): string
    {
        return 'vm-controllers';
    }

    public function disks(array $fields = [], array $query = []): Collection
    {
        return $this->hydrateMany(
            $this->client->get($this->path('vdis'), ($fields === [] ? [] : ['fields' => $fields]) + $query),
            VirtualDisk::class,
            $this->client,
        );
    }
}
