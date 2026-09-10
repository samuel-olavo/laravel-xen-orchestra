<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use Psr\Http\Message\StreamInterface;
use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTags;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;
use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;

/** A pool of hosts. Most cluster-wide actions hang off this object. */
class Pool extends Model
{
    use HasAlarms;
    use HasMessages;
    use HasTags;
    use HasTasks;
    use HydratesModels;

    public static function endpoint(): string
    {
        return 'pools';
    }

    public function name(): ?string
    {
        $name = $this->get('name_label');

        return is_string($name) ? $name : null;
    }

    /** `GET /pools/<id>/dashboard` — the per-pool overview. */
    public function dashboard(): array
    {
        return (array) $this->client->get($this->path('dashboard'));
    }

    /** `GET /pools/<id>/stats` */
    public function stats(array $query = []): array
    {
        return (array) $this->client->get($this->path('stats'), $query);
    }

    /** `GET /pools/<id>/missing_patches` */
    public function missingPatches(): Collection
    {
        $payload = $this->client->get($this->path('missing_patches'));

        return $this->hydrateMany($payload, GenericObject::class, $this->client);
    }

    // ---------------------------------------------------------------------
    // Actions
    // ---------------------------------------------------------------------

    /**
     * `POST /pools/<id>/actions/rolling_update` — update hosts one at a time,
     * migrating VMs around them. Long-running; expect to wait minutes.
     */
    public function rollingUpdate(bool $sync = false, ?bool $shutdownPinnedVms = null): Task
    {
        return $this->action('rolling_update', $shutdownPinnedVms === null ? [] : compact('shutdownPinnedVms'), $sync);
    }

    /** `POST /pools/<id>/actions/rolling_reboot` */
    public function rollingReboot(bool $sync = false, ?bool $shutdownPinnedVms = null): Task
    {
        return $this->action('rolling_reboot', $shutdownPinnedVms === null ? [] : compact('shutdownPinnedVms'), $sync);
    }

    /**
     * `POST /pools/<id>/actions/emergency_shutdown` — suspends every VM and
     * shuts down every host. Exactly as drastic as it sounds.
     */
    public function emergencyShutdown(bool $sync = false): Task
    {
        return $this->action('emergency_shutdown', sync: $sync);
    }

    /**
     * `POST /pools/<id>/actions/create_vm`
     *
     * The attribute array is passed through untouched, deliberately: XO
     * validates this payload strictly and the accepted field set has moved
     * between versions, so a hardcoded whitelist here would break instances it
     * was never tested against.
     *
     * REST API 0.37 accepts high_availability for the HA restart priority.
     * Creation and update payloads use different property names.
     *
     * The authoritative field list for *your* version is the create_vm entry in
     * your appliance's own OpenAPI document, at /rest/v0/docs/swagger.json.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createVm(array $attributes, bool $sync = false): Task
    {
        return $this->action('create_vm', $attributes, $sync);
    }

    /** `POST /pools/<id>/actions/create_network` */
    public function createNetwork(array $attributes, bool $sync = false): Task
    {
        return $this->action('create_network', $attributes, $sync);
    }

    public function createBondedNetwork(array $attributes, bool $sync = false): Task
    {
        return $this->action('create_bonded_network', $attributes, $sync);
    }

    public function createInternalNetwork(array $attributes, bool $sync = false): Task
    {
        return $this->action('create_internal_network', $attributes, $sync);
    }

    public function managementReconfigure(array $attributes, bool $sync = false): Task
    {
        return $this->action('management_reconfigure', $attributes, $sync);
    }

    protected function action(string $action, array $body = [], bool $sync = false): Task
    {
        $payload = $this->client->post(
            $this->path('actions', $action),
            $body,
            $sync ? ['sync' => true] : [],
        );

        return Task::fromActionResponse($payload, $this->client, $sync);
    }

    public function addHost(array $attributes = [], bool $sync = false): Task
    {
        return $this->action('add_host', $attributes, $sync);
    }

    public function importVm(StreamInterface $source, array $query = []): VirtualMachine
    {
        $response = $this->client->upload('POST', $this->path('vms'), $source, $query);

        return new VirtualMachine((array) $response->json(), $this->client);
    }
}
