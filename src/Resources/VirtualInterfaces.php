<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\VirtualInterface;

/** @method VirtualInterface find(string $id, array $fields = []) */
class VirtualInterfaces extends Resource
{
    public function endpoint(): string
    {
        return 'vifs';
    }

    public function modelClass(): string
    {
        return VirtualInterface::class;
    }

    /** Returns the created object as a partial model, without an implicit GET. */
    public function create(array $attributes): VirtualInterface
    {
        return new VirtualInterface((array) $this->client->post($this->endpoint(), $attributes), $this->client);
    }
}
