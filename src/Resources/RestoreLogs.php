<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\RestoreLog;

/** @method RestoreLog find(string $id, array $fields = []) */
class RestoreLogs extends Resource
{
    public function endpoint(): string
    {
        return 'restore-logs';
    }

    public function modelClass(): string
    {
        return RestoreLog::class;
    }
}
