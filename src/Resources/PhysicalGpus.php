<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\PhysicalGpu;

/** @method PhysicalGpu find(string $id, array $fields = []) */
class PhysicalGpus extends Resource
{
    public function endpoint(): string
    {
        return 'pgpus';
    }

    public function modelClass(): string
    {
        return PhysicalGpu::class;
    }
}
