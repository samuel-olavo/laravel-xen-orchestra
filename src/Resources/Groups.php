<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\Group;

/** @method Group find(string $id, array $fields = []) */
class Groups extends Resource
{
    public function endpoint(): string
    {
        return 'groups';
    }

    public function modelClass(): string
    {
        return Group::class;
    }
}
