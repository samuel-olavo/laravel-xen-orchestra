<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\Proxy;

/** @method Proxy find(string $id, array $fields = []) */
class Proxies extends Resource
{
    public function endpoint(): string
    {
        return 'proxies';
    }

    public function modelClass(): string
    {
        return Proxy::class;
    }
}
