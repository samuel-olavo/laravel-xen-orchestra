<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\Alarm;

/** @method Alarm find(string $id, array $fields = []) */
class Alarms extends Resource
{
    public function endpoint(): string
    {
        return 'alarms';
    }

    public function modelClass(): string
    {
        return Alarm::class;
    }
}
