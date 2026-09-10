<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\BackupRepository;

/** @method BackupRepository find(string $id, array $fields = []) */
class BackupRepositories extends Resource
{
    public function endpoint(): string
    {
        return 'backup-repositories';
    }

    public function modelClass(): string
    {
        return BackupRepository::class;
    }

    /** Returns the created object as a partial model, without an implicit GET. */
    public function create(array $attributes): BackupRepository
    {
        return new BackupRepository((array) $this->client->post($this->endpoint(), $attributes), $this->client);
    }
}
