<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\VirtualMachineController;

/** @method VirtualMachineController find(string $id, array $fields = []) */
class VirtualMachineControllers extends Resource
{
    public function endpoint(): string
    {
        return 'vm-controllers';
    }

    public function modelClass(): string
    {
        return VirtualMachineController::class;
    }
}
