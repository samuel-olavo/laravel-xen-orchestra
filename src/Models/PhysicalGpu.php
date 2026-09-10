<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

class PhysicalGpu extends Model
{
    public static function endpoint(): string
    {
        return 'pgpus';
    }
}
