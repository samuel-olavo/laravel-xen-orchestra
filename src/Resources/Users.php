<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\User;

/** @method User find(string $id, array $fields = []) */
class Users extends Resource
{
    public function endpoint(): string
    {
        return 'users';
    }

    public function modelClass(): string
    {
        return User::class;
    }
}
