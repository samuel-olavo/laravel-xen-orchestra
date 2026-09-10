<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\Schedule;

/** @method Schedule find(string $id, array $fields = []) */
class Schedules extends Resource
{
    public function endpoint(): string
    {
        return 'schedules';
    }

    public function modelClass(): string
    {
        return Schedule::class;
    }
}
