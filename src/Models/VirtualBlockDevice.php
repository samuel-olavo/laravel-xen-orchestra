<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\DeletesObject;
use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;
use SamuelOlavo\XenOrchestra\Concerns\RunsActions;

class VirtualBlockDevice extends Model
{
    use RunsActions;
    use HasTasks;
    use HasMessages;
    use HasAlarms;
    use DeletesObject;
    public static function endpoint(): string
    {
        return 'vbds';
    }

    public function connect(bool $sync = false): Task
    {
        return $this->action('connect', sync: $sync);
    }

    public function disconnect(bool $sync = false): Task
    {
        return $this->action('disconnect', sync: $sync);
    }
}
