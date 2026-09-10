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

    /** Returns the created object as a partial model, without an implicit GET. */
    public function create(array $attributes): User
    {
        return new User((array) $this->client->post($this->endpoint(), $attributes), $this->client);
    }

    public function me(): User
    {
        return $this->find('me');
    }
}
