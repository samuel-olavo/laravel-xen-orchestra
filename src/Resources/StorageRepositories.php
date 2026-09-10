<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\StorageRepository;

/**
 * `/srs`
 *
 * XO also exposes `/sms`, whose operations are literally named GetSrs/GetSr —
 * an alias of this collection. It is deliberately not mirrored here: one way to
 * reach storage repositories is enough.
 *
 * @method StorageRepository find(string $id, array $fields = [])
 */
class StorageRepositories extends Resource
{
    public function endpoint(): string
    {
        return 'srs';
    }

    public function modelClass(): string
    {
        return StorageRepository::class;
    }
}
