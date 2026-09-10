<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\VirtualMachineTemplate;

/** @method VirtualMachineTemplate find(string $id, array $fields = []) */
class VirtualMachineTemplates extends Resource
{
    public function endpoint(): string
    {
        return 'vm-templates';
    }

    public function modelClass(): string
    {
        return VirtualMachineTemplate::class;
    }
}
