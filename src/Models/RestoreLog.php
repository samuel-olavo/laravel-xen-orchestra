<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

class RestoreLog extends Model
{
    public static function endpoint(): string
    {
        return 'restore-logs';
    }
}
