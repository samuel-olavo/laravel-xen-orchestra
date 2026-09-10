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
}
