<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;

class PhysicalInterface extends Model
{
    use HasTasks;
    use HasMessages;
    use HasAlarms;
    public static function endpoint(): string
    {
        return 'pifs';
    }
}
