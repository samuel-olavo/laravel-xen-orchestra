<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\AclPrivilege;

/** @method AclPrivilege find(string $id, array $fields = []) */
class AclPrivileges extends Resource
{
    public function endpoint(): string
    {
        return 'acl-privileges';
    }

    public function modelClass(): string
    {
        return AclPrivilege::class;
    }

    /** Returns the created object as a partial model, without an implicit GET. */
    public function create(array $attributes): AclPrivilege
    {
        return new AclPrivilege((array) $this->client->post($this->endpoint(), $attributes), $this->client);
    }
}
