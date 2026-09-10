<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\VirtualDisk;

/** @method VirtualDisk find(string $id, array $fields = []) */
class VirtualDisks extends Resource
{
    public function endpoint(): string
    {
        return 'vdis';
    }

    public function modelClass(): string
    {
        return VirtualDisk::class;
    }
}
