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
}
