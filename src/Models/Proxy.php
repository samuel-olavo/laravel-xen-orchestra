<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

class Proxy extends Model
{
    public static function endpoint(): string
    {
        return 'proxies';
    }
}
