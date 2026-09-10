<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\PhysicalBlockDevice;

/** @method PhysicalBlockDevice find(string $id, array $fields = []) */
class PhysicalBlockDevices extends Resource
{
    public function endpoint(): string
    {
        return 'pbds';
    }

    public function modelClass(): string
    {
        return PhysicalBlockDevice::class;
    }
}
