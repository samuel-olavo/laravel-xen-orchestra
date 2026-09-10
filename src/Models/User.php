<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Concerns\DeletesObject;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;
use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;
use SamuelOlavo\XenOrchestra\Concerns\UpdatesObject;

class User extends Model
{
    use HasTasks;
    use DeletesObject;
    use UpdatesObject;
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

    public function privileges(array $fields = [], array $query = []): Collection
    {
        return $this->hydrateMany(
            $this->client->get($this->path('acl-privileges'), ($fields === [] ? [] : ['fields' => $fields]) + $query),
            AclPrivilege::class,
            $this->client,
        );
    }

    public function authenticationTokens(array $query = []): array
    {
        return (array) $this->client->get($this->path('authentication_tokens'), $query);
    }

    /** Returns the complete {token: ...} response. Treat it as a credential. */
    public function createAuthenticationToken(array $attributes = []): array
    {
        return (array) $this->client->post($this->path('authentication_tokens'), $attributes);
    }
}
