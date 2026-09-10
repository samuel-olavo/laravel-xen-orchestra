<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Concerns\DeletesObject;
use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;
use SamuelOlavo\XenOrchestra\Concerns\RunsActions;
use SamuelOlavo\XenOrchestra\Concerns\UpdatesObject;

class AclRole extends Model
{
    use RunsActions;
    use DeletesObject;
    use UpdatesObject;
    use HydratesModels;

    public static function endpoint(): string
    {
        return 'acl-roles';
    }

    public function users(array $fields = [], array $query = []): Collection
    {
        $payload = $this->client->get($this->path('users'), ($fields === [] ? [] : ['fields' => $fields]) + $query);

        return $this->hydrateMany($payload, User::class, $this->client);
    }

    public function groups(array $fields = [], array $query = []): Collection
    {
        $payload = $this->client->get($this->path('groups'), ($fields === [] ? [] : ['fields' => $fields]) + $query);

        return $this->hydrateMany($payload, Group::class, $this->client);
    }

    public function copy(array $attributes = [], bool $sync = false): Task
    {
        return $this->action('copy', $attributes, $sync);
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

    public function addGroup(string $id): static
    {
        $this->client->request('PUT', $this->path('groups', rawurlencode($id)));

        return $this;
    }

    public function removeGroup(string $id): static
    {
        $this->client->request('DELETE', $this->path('groups', rawurlencode($id)));

        return $this;
    }

    public function privileges(array $fields = [], array $query = []): Collection
    {
        return $this->hydrateMany(
            $this->client->get($this->path('privileges'), ($fields === [] ? [] : ['fields' => $fields]) + $query),
            AclPrivilege::class,
            $this->client,
        );
    }
}
