<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\Network;

/**
 * `/networks`
 *
 * @method Network find(string $id, array $fields = [])
 */
class Networks extends Resource
{
    public function endpoint(): string
    {
        return 'networks';
    }

    public function modelClass(): string
    {
        return Network::class;
    }
}
