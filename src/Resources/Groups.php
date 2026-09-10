<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\Group;

/** @method Group find(string $id, array $fields = []) */
class Groups extends Resource
{
    public function endpoint(): string
    {
        return 'groups';
    }

    public function modelClass(): string
    {
        return Group::class;
    }

    /** Returns the created object as a partial model, without an implicit GET. */
    public function create(array $attributes): Group
    {
        return new Group((array) $this->client->post($this->endpoint(), $attributes), $this->client);
    }
}
