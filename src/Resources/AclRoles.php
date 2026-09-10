<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\AclRole;

/** @method AclRole find(string $id, array $fields = []) */
class AclRoles extends Resource
{
    public function endpoint(): string
    {
        return 'acl-roles';
    }

    public function modelClass(): string
    {
        return AclRole::class;
    }

    /** Returns the created object as a partial model, without an implicit GET. */
    public function create(array $attributes): AclRole
    {
        return new AclRole((array) $this->client->post($this->endpoint(), $attributes), $this->client);
    }
}
