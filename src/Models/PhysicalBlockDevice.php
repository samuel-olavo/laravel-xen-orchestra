<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\RunsActions;

class PhysicalBlockDevice extends Model
{
    use RunsActions;
    public static function endpoint(): string
    {
        return 'pbds';
    }

    public function plug(bool $sync = false): Task
    {
        return $this->action('plug', sync: $sync);
    }

    public function unplug(bool $sync = false): Task
    {
        return $this->action('unplug', sync: $sync);
    }
}
