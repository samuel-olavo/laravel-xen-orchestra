<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\BackupJob;

/** @method BackupJob find(string $id, array $fields = []) */
class BackupJobs extends Resource
{
    public function endpoint(): string
    {
        return 'backup-jobs';
    }

    public function modelClass(): string
    {
        return BackupJob::class;
    }
}
