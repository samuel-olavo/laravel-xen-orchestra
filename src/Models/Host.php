<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Client\Response;
use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTags;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;
use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;
use SamuelOlavo\XenOrchestra\Concerns\RunsActions;

/** A physical XCP-ng / XenServer host. */
class Host extends Model
{
    use HasAlarms;
    use HasMessages;
    use HasTags;
    use HasTasks;
    use HydratesModels;
    use RunsActions;

    /** Options include evacuate, autoEnable, force and vmIdsToForceMigrate. */
    public function disable(array $options = [], bool $sync = false): Task
    {
        return $this->action('disable', $options, $sync);
    }

    public function enable(bool $sync = false): Task
    {
        return $this->action('enable', sync: $sync);
    }

    public static function endpoint(): string
    {
        return 'hosts';
    }

    public function name(): ?string
    {
        $name = $this->get('name_label');

        return is_string($name) ? $name : null;
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

    /** `GET /hosts/<id>/stats` */
    public function stats(array $query = []): array
    {
        return (array) $this->client->get($this->path('stats'), $query);
    }

    /** `GET /hosts/<id>/missing_patches` — pending updates for this host. */
    public function missingPatches(): Collection
    {
        $payload = $this->client->get($this->path('missing_patches'));

        return $this->hydrateMany($payload, GenericObject::class, $this->client);
    }

    /** `GET /hosts/<id>/smt` — simultaneous multithreading status. */
    public function smt(): mixed
    {
        return $this->client->get($this->path('smt'));
    }

    /**
     * `GET /hosts/<id>/audit.txt` — plain text, not JSON.
     */
    public function auditLog(): string
    {
        return $this->client->request('GET', $this->path('audit.txt'))->body();
    }

    public function start(bool $sync = false): Task
    {
        return $this->action('start', sync: $sync);
    }

    public function shutdown(array $attributes = [], bool $sync = false): Task
    {
        return $this->action('clean_shutdown', $attributes, $sync);
    }

    public function reboot(array $attributes = [], bool $sync = false): Task
    {
        return $this->action('clean_reboot', $attributes, $sync);
    }

    public function smartReboot(array $attributes = [], bool $sync = false): Task
    {
        return $this->action('smart_reboot', $attributes, $sync);
    }

    public function restartToolstack(array $attributes = [], bool $sync = false): Task
    {
        return $this->action('restart_toolstack', $attributes, $sync);
    }

    public function emergencyShutdown(bool $sync = false): Task
    {
        return $this->action('emergency_shutdown', sync: $sync);
    }

    public function detach(bool $sync = false): Task
    {
        return $this->action('detach', sync: $sync);
    }

    public function forget(bool $sync = false): Task
    {
        return $this->action('forget', sync: $sync);
    }

    public function scanPifs(bool $sync = false): Task
    {
        return $this->action('scan_pifs', sync: $sync);
    }

    public function managementReconfigure(array $attributes = [], bool $sync = false): Task
    {
        return $this->action('management_reconfigure', $attributes, $sync);
    }

    /** Requires the ipmi-sensors plugin and an available IPMI device. */
    public function ipmi(): array
    {
        if ($this->id() === null) {
            throw new \LogicException('An IPMI request requires a host ID.');
        }

        return (array) $this->client->get('plugins/ipmi-sensors/hosts/'.rawurlencode($this->id()).'/ipmi');
    }

    public function logs(): Response
    {
        return $this->client->download($this->path('logs.tgz'));
    }
}
