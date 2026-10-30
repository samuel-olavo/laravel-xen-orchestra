<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\Plugin;

/** @method Plugin find(string $id, array $fields = []) */
class Plugins extends Resource
{
    public function endpoint(): string
    {
        return 'plugins';
    }

    public function modelClass(): string
    {
        return Plugin::class;
    }
}
