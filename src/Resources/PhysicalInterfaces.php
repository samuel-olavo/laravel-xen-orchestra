<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\PhysicalInterface;

/** @method PhysicalInterface find(string $id, array $fields = []) */
class PhysicalInterfaces extends Resource
{
    public function endpoint(): string
    {
        return 'pifs';
    }

    public function modelClass(): string
    {
        return PhysicalInterface::class;
    }
}
