<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\BackupLog;

/** @method BackupLog find(string $id, array $fields = []) */
class BackupLogs extends Resource
{
    public function endpoint(): string
    {
        return 'backup-logs';
    }

    public function modelClass(): string
    {
        return BackupLog::class;
    }
}
