<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\DeletesObject;
use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;
use SamuelOlavo\XenOrchestra\Concerns\RunsActions;
use SamuelOlavo\XenOrchestra\Concerns\UpdatesObject;

class VirtualInterface extends Model
{
    use RunsActions;
    use HasTasks;
    use HasMessages;
    use HasAlarms;
    use DeletesObject;
    use UpdatesObject;
    public static function endpoint(): string
    {
        return 'vifs';
    }

    public function connect(bool $sync = false): Task
    {
        return $this->action('connect', sync: $sync);
    }

    public function disconnect(bool $sync = false): Task
    {
        return $this->action('disconnect', sync: $sync);
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
