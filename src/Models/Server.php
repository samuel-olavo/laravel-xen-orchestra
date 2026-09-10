<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\DeletesObject;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;
use SamuelOlavo\XenOrchestra\Concerns\RunsActions;

class Server extends Model
{
    use RunsActions;
    use HasTasks;
    use DeletesObject;
    public static function endpoint(): string
    {
        return 'servers';
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
