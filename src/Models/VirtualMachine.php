<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\UpdatesObject;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTags;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;
use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;

/**
 * A virtual machine.
 *
 * REST API 0.37 supports partial updates, cloning and migration.
 */
class VirtualMachine extends Model
{
    use HasAlarms;
    use HasMessages;
    use HasTags;
    use HasTasks;
    use HydratesModels;
    use UpdatesObject;

    public function cloneVm(array $attributes = [], bool $sync = false): Task
    {
        return $this->action('clone', $attributes, $sync);
    }

    public function migrate(array $attributes, bool $sync = false): Task
    {
        return $this->action('migrate', $attributes, $sync);
    }

    public function revertSnapshot(string $snapshotId, bool $snapshotBefore = false, bool $sync = false): Task
    {
        return $this->action('revert_snapshot', compact('snapshotId', 'snapshotBefore'), $sync);
    }

    public static function endpoint(): string
    {
        return 'vms';
    }

    // ---------------------------------------------------------------------
    // Convenience accessors — data already held, no requests
    // ---------------------------------------------------------------------

    public function name(): ?string
    {
        $name = $this->get('name_label');

        return is_string($name) ? $name : null;
    }

    public function description(): ?string
    {
        $description = $this->get('name_description');

        return is_string($description) ? $description : null;
    }

    public function powerState(): ?string
    {
        $state = $this->get('power_state');

        return is_string($state) ? $state : null;
    }

    public function isRunning(): bool
    {
        return $this->powerState() === 'Running';
    }

    public function isHalted(): bool
    {
        return $this->powerState() === 'Halted';
    }

    public function isSuspended(): bool
    {
        return $this->powerState() === 'Suspended';
    }

    public function isPaused(): bool
    {
        return $this->powerState() === 'Paused';
    }

    // ---------------------------------------------------------------------
    // Power lifecycle
    // ---------------------------------------------------------------------

    public function start(bool $sync = false): Task
    {
        return $this->action('start', sync: $sync);
    }

    /**
     * @param  bool  $force  Use `hard_reboot` (pull the plug) instead of asking
     *                       the guest to reboot cleanly.
     */
    public function reboot(bool $force = false, bool $sync = false): Task
    {
        return $this->action($force ? 'hard_reboot' : 'clean_reboot', sync: $sync);
    }

    /**
     * @param  bool  $force  Use `hard_shutdown`. A clean shutdown needs guest
     *                       tools and will fail on a VM without them.
     */
    public function shutdown(bool $force = false, bool $sync = false): Task
    {
        return $this->action($force ? 'hard_shutdown' : 'clean_shutdown', sync: $sync);
    }

    /** Alias of {@see shutdown()} for readability. */
    public function stop(bool $force = false, bool $sync = false): Task
    {
        return $this->shutdown($force, $sync);
    }

    public function suspend(bool $sync = false): Task
    {
        return $this->action('suspend', sync: $sync);
    }

    public function resume(bool $sync = false): Task
    {
        return $this->action('resume', sync: $sync);
    }

    public function pause(bool $sync = false): Task
    {
        return $this->action('pause', sync: $sync);
    }

    public function unpause(bool $sync = false): Task
    {
        return $this->action('unpause', sync: $sync);
    }

    /**
     * `POST /vms/<id>/actions/snapshot`
     *
     * @param  string|null  $name  Snapshot label; XO generates one if omitted.
     */
    public function snapshot(?string $name = null, bool $sync = false): Task
    {
        return $this->action(
            'snapshot',
            $name === null ? [] : ['name_label' => $name],
            $sync,
        );
    }

    /** `DELETE /vms/<id>` — destroys the VM and, by default, its disks. */
    public function delete(): Task
    {
        return Task::fromActionResponse(
            $this->client->delete((string) $this->href()),
            $this->client,
        );
    }

    // ---------------------------------------------------------------------
    // Related objects
    // ---------------------------------------------------------------------

    /** `GET /vms/<id>/vdis` — the virtual disks attached to this VM. */
    public function disks(array $fields = []): Collection
    {
        $payload = $this->client->get(
            $this->path('vdis'),
            $fields === [] ? [] : ['fields' => $fields],
        );

        return $this->hydrateMany($payload, VirtualDisk::class, $this->client);
    }

    /** `GET /vms/<id>/stats` — RRD performance data. */
    public function stats(array $query = []): array
    {
        return (array) $this->client->get($this->path('stats'), $query);
    }

    /** `GET /vms/<id>/dashboard` */
    public function dashboard(): array
    {
        return (array) $this->client->get($this->path('dashboard'));
    }

    /** `GET /vms/<id>/backup-jobs` — read-only; the REST API cannot create them. */
    public function backupJobs(array $fields = []): Collection
    {
        $payload = $this->client->get(
            $this->path('backup-jobs'),
            $fields === [] ? [] : ['fields' => $fields],
        );

        return $this->hydrateMany($payload, GenericObject::class, $this->client);
    }

    // ---------------------------------------------------------------------

    /**
     * Fire an action, returning a Task in every case.
     *
     * The two modes are genuinely different and easy to confuse:
     *
     *   $sync = false  →  XO queues the work and answers immediately with a
     *                     task reference. Call ->wait() when you want to block.
     *   $sync = true   →  `?sync=true`; XO holds the HTTP request open until the
     *                     work is done. There is nothing left to wait for, so
     *                     the returned Task is already marked successful.
     */
    protected function action(string $action, array $body = [], bool $sync = false): Task
    {
        $payload = $this->client->post(
            $this->path('actions', $action),
            $body,
            $sync ? ['sync' => true] : [],
        );

        return Task::fromActionResponse($payload, $this->client, $sync);
    }
}
