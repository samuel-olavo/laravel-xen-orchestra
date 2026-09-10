<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Concerns\DeletesObject;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;
use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;
use SamuelOlavo\XenOrchestra\Concerns\UpdatesObject;

class Group extends Model
{
    use HasTasks;
    use DeletesObject;
    use UpdatesObject;
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

    public function addUser(string $id): static
    {
        $this->client->request('PUT', $this->path('users', rawurlencode($id)));

        return $this;
    }

    public function removeUser(string $id): static
    {
        $this->client->request('DELETE', $this->path('users', rawurlencode($id)));

        return $this;
    }
}
