<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTags;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;

/**
 * A network.
 *
 * Note the asymmetry in the API: networks are created from their pool
 * (`POST /pools/<id>/actions/create_network`, see {@see Pool::createNetwork()})
 * but deleted from themselves.
 */
class Network extends Model
{
    use HasAlarms;
    use HasMessages;
    use HasTags;
    use HasTasks;

    public static function endpoint(): string
    {
        return 'networks';
    }

    public function name(): ?string
    {
        $name = $this->get('name_label');

        return is_string($name) ? $name : null;
    }

    public function mtu(): ?int
    {
        $mtu = $this->get('MTU');

        return is_numeric($mtu) ? (int) $mtu : null;
    }

    /** `DELETE /networks/<id>` */
    public function delete(): Task
    {
        return Task::fromActionResponse(
            $this->client->delete((string) $this->href()),
            $this->client,
        );
    }

    /** Requires the sdn-controller plugin. Payload fields are passed unchanged. */
    public function addTrafficRule(array $attributes, bool $sync = false): Task
    {
        if ($this->id() === null) {
            throw new \LogicException('A traffic rule operation requires an object ID.');
        }
        $payload = $this->client->post(
            'plugins/sdn-controller/'.static::endpoint().'/'.rawurlencode($this->id()).'/actions/add_traffic_rule',
            $attributes,
            $sync ? ['sync' => true] : [],
        );

        return Task::fromActionResponse($payload, $this->client, $sync);
    }

    /** Requires the sdn-controller plugin. Payload fields are passed unchanged. */
    public function deleteTrafficRule(array $attributes, bool $sync = false): Task
    {
        if ($this->id() === null) {
            throw new \LogicException('A traffic rule operation requires an object ID.');
        }
        $payload = $this->client->post(
            'plugins/sdn-controller/'.static::endpoint().'/'.rawurlencode($this->id()).'/actions/delete_traffic_rule',
            $attributes,
            $sync ? ['sync' => true] : [],
        );

        return Task::fromActionResponse($payload, $this->client, $sync);
    }

    /** Requires the sdn-controller plugin. Payload fields are passed unchanged. */
    public function updateTrafficRule(array $attributes, bool $sync = false): Task
    {
        if ($this->id() === null) {
            throw new \LogicException('A traffic rule operation requires an object ID.');
        }
        $payload = $this->client->post(
            'plugins/sdn-controller/'.static::endpoint().'/'.rawurlencode($this->id()).'/actions/update_traffic_rule',
            $attributes,
            $sync ? ['sync' => true] : [],
        );

        return Task::fromActionResponse($payload, $this->client, $sync);
    }
}
