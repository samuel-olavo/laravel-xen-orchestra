<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\Server;

/** @method Server find(string $id, array $fields = []) */
class Servers extends Resource
{
    public function endpoint(): string
    {
        return 'servers';
    }

    public function modelClass(): string
    {
        return Server::class;
    }

    /** Returns the created object as a partial model, without an implicit GET. */
    public function create(array $attributes): Server
    {
        return new Server((array) $this->client->post($this->endpoint(), $attributes), $this->client);
    }
}
