<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\DeletesObject;
use SamuelOlavo\XenOrchestra\Concerns\UpdatesObject;

class AclPrivilege extends Model
{
    use DeletesObject;
    use UpdatesObject;
    public static function endpoint(): string
    {
        return 'acl-privileges';
    }
}
