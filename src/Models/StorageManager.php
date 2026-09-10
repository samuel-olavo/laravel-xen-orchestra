<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

class StorageManager extends Model
{
    public static function endpoint(): string
    {
        return 'sms';
    }
}
