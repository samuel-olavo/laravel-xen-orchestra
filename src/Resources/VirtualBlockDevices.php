<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\VirtualBlockDevice;

/** @method VirtualBlockDevice find(string $id, array $fields = []) */
class VirtualBlockDevices extends Resource
{
    public function endpoint(): string
    {
        return 'vbds';
    }

    public function modelClass(): string
    {
        return VirtualBlockDevice::class;
    }

    /** Returns the created object as a partial model, without an implicit GET. */
    public function create(array $attributes): VirtualBlockDevice
    {
        return new VirtualBlockDevice((array) $this->client->post($this->endpoint(), $attributes), $this->client);
    }
}
