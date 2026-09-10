<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

class BackupLog extends Model
{
    public static function endpoint(): string
    {
        return 'backup-logs';
    }
}
