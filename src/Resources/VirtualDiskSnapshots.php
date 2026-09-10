<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\VirtualDiskSnapshot;

/** @method VirtualDiskSnapshot find(string $id, array $fields = []) */
class VirtualDiskSnapshots extends Resource
{
    public function endpoint(): string
    {
        return 'vdi-snapshots';
    }

    public function modelClass(): string
    {
        return VirtualDiskSnapshot::class;
    }
}
