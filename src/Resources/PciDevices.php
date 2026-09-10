<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\PciDevice;

/** @method PciDevice find(string $id, array $fields = []) */
class PciDevices extends Resource
{
    public function endpoint(): string
    {
        return 'pcis';
    }

    public function modelClass(): string
    {
        return PciDevice::class;
    }
}
