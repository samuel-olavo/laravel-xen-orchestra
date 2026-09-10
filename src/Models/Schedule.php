<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\RunsActions;

class Schedule extends Model
{
    use RunsActions;
    public static function endpoint(): string
    {
        return 'schedules';
    }

    public function run(bool $sync = false): Task
    {
        return $this->action('run', sync: $sync);
    }
}
