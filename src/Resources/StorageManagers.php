<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\StorageManager;

/** @method StorageManager find(string $id, array $fields = []) */
class StorageManagers extends Resource
{
    public function endpoint(): string
    {
        return 'sms';
    }

    public function modelClass(): string
    {
        return StorageManager::class;
    }
}
