<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;

class Group extends Model
{
    use HydratesModels;

    public static function endpoint(): string
    {
        return 'groups';
    }

    public function aclRoles(array $fields = [], array $query = []): Collection
    {
        $payload = $this->client->get($this->path('acl-roles'), ($fields === [] ? [] : ['fields' => $fields]) + $query);

        return $this->hydrateMany($payload, AclRole::class, $this->client);
    }

    public function users(array $fields = [], array $query = []): Collection
    {
        $payload = $this->client->get($this->path('users'), ($fields === [] ? [] : ['fields' => $fields]) + $query);

        return $this->hydrateMany($payload, User::class, $this->client);
    }
}
