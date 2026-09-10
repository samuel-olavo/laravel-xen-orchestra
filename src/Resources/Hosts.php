<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\Host;

/**
 * `/hosts`
 *
 * @method Host find(string $id, array $fields = [])
 */
class Hosts extends Resource
{
    public function endpoint(): string
    {
        return 'hosts';
    }

    public function modelClass(): string
    {
        return Host::class;
    }

    public function running(): static
    {
        return $this->where('power_state', 'Running');
    }
}
