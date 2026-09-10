<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Models\Model;
use SamuelOlavo\XenOrchestra\Models\VirtualMachine;

/**
 * `/vms` — and, through the scopes below, the snapshot and template
 * collections that share its shape.
 *
 * @method VirtualMachine find(string $id, array $fields = [])
 */
class VirtualMachines extends Resource
{
    protected string $endpoint = 'vms';

    public function endpoint(): string
    {
        return $this->endpoint;
    }

    public function modelClass(): string
    {
        return VirtualMachine::class;
    }

    // ---------------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------------

    public function running(): static
    {
        return $this->where('power_state', 'Running');
    }

    public function halted(): static
    {
        return $this->where('power_state', 'Halted');
    }

    public function suspended(): static
    {
        return $this->where('power_state', 'Suspended');
    }

    public function tagged(string $tag): static
    {
        return $this->where('tags', $tag);
    }

    /**
     * Switch to `/vm-snapshots`, which has the same shape as `/vms`.
     */
    public function snapshots(): static
    {
        $clone = clone $this;
        $clone->endpoint = 'vm-snapshots';

        return $clone;
    }

    /**
     * Switch to `/vm-templates`.
     */
    public function templates(): static
    {
        $clone = clone $this;
        $clone->endpoint = 'vm-templates';

        return $clone;
    }

    /**
     * Switch to `/vm-controllers` (dom0 control domains).
     */
    public function controllers(): static
    {
        $clone = clone $this;
        $clone->endpoint = 'vm-controllers';

        return $clone;
    }

    /**
     * A commonly useful default: name, power state, CPU and memory, which is
     * enough to render a VM list without a second round trip.
     */
    public function summary(): Collection
    {
        return $this->fields(['name_label', 'power_state', 'CPUs', 'memory', 'tags'])->get();
    }

    /** @return Collection<Model> */
    public function get(): Collection
    {
        return parent::get();
    }
}
