<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\UpdatesObject;

class VirtualDisk extends Model
{
    use UpdatesObject;

    public static function endpoint(): string
    {
        return 'vdis';
    }
}
