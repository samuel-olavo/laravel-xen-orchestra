<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\VirtualDisk;

/** @method VirtualDisk find(string $id, array $fields = []) */
class VirtualDisks extends Resource
{
    public function endpoint(): string
    {
        return 'vdis';
    }

    public function modelClass(): string
    {
        return VirtualDisk::class;
    }

    /** Returns the created object as a partial model, without an implicit GET. */
    public function create(array $attributes): VirtualDisk
    {
        return new VirtualDisk((array) $this->client->post($this->endpoint(), $attributes), $this->client);
    }
}
