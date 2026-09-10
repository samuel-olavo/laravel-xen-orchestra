<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\StorageRepository;

/**
 * `/srs`
 *
 * @method StorageRepository find(string $id, array $fields = [])
 */
class StorageRepositories extends Resource
{
    /** Returns a partial SR; call fetch() when the full object is needed. */
    public function create(array $attributes): StorageRepository
    {
        return new StorageRepository((array) $this->client->post($this->endpoint(), $attributes), $this->client);
    }

    public function endpoint(): string
    {
        return 'srs';
    }

    public function modelClass(): string
    {
        return StorageRepository::class;
    }
}
