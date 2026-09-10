<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\VirtualMachineSnapshot;

/** @method VirtualMachineSnapshot find(string $id, array $fields = []) */
class VirtualMachineSnapshots extends Resource
{
    public function endpoint(): string
    {
        return 'vm-snapshots';
    }

    public function modelClass(): string
    {
        return VirtualMachineSnapshot::class;
    }
}
