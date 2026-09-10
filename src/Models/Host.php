<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTags;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;
use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;

/** A physical XCP-ng / XenServer host. */
class Host extends Model
{
    use HasAlarms;
    use HasMessages;
    use HasTags;
    use HasTasks;
    use HydratesModels;

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
}
