<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

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
    public function rollingUpdate(bool $sync = false): Task
    {
        return $this->action('rolling_update', sync: $sync);
    }

    /** `POST /pools/<id>/actions/rolling_reboot` */
    public function rollingReboot(bool $sync = false): Task
    {
        return $this->action('rolling_reboot', sync: $sync);
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
     * Known traps, current as of rest-api 0.21.x:
     *
     *  - `template` (a template UUID) and `name_label` are the practical
     *    minimum.
     *  - `memory`, `name_description` and `auto_poweron` are **rejected** on
     *    creation with "excess property and therefore is not allowed", even
     *    though they appear on VMs returned by GET. Set them afterwards.
     *  - Property naming is not consistent between GET responses and this
     *    payload (snake_case vs camelCase). Do not assume a field you read back
     *    can be written with the same name.
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

    protected function action(string $action, array $body = [], bool $sync = false): Task
    {
        $payload = $this->client->post(
            $this->path('actions', $action),
            $body,
            $sync ? ['sync' => true] : [],
        );

        return Task::fromActionResponse($payload, $this->client);
    }
}
