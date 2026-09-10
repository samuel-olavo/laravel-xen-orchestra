<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\BackupArchive;

/** @method BackupArchive find(string $id, array $fields = []) */
class BackupArchives extends Resource
{
    public function endpoint(): string
    {
        return 'backup-archives';
    }

    public function modelClass(): string
    {
        return BackupArchive::class;
    }
}
