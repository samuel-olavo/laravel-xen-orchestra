<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\Pool;

/**
 * `/pools`
 *
 * @method Pool find(string $id, array $fields = [])
 */
class Pools extends Resource
{
    public function endpoint(): string
    {
        return 'pools';
    }

    public function modelClass(): string
    {
        return Pool::class;
    }
}
