<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;

class User extends Model
{
    use HydratesModels;

    public static function endpoint(): string
    {
        return 'users';
    }

    public function groups(array $fields = [], array $query = []): Collection
    {
        $payload = $this->client->get($this->path('groups'), ($fields === [] ? [] : ['fields' => $fields]) + $query);

        return $this->hydrateMany($payload, Group::class, $this->client);
    }
}
