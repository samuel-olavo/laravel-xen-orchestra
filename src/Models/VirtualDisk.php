<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use Psr\Http\Message\StreamInterface;
use SamuelOlavo\XenOrchestra\Client\Response;
use SamuelOlavo\XenOrchestra\Concerns\DeletesObject;
use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTags;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;
use SamuelOlavo\XenOrchestra\Concerns\RunsActions;
use SamuelOlavo\XenOrchestra\Concerns\UpdatesObject;

class VirtualDisk extends Model
{
    use RunsActions;
    use HasTags;
    use HasTasks;
    use HasMessages;
    use HasAlarms;
    use DeletesObject;
    use UpdatesObject;

    public static function endpoint(): string
    {
        return 'vdis';
    }

    public function migrate(array $attributes = [], bool $sync = false): Task
    {
        return $this->action('migrate', $attributes, $sync);
    }

    /** The response body is streamed; use saveTo() or consume its PSR-7 stream. */
    public function export(string $format = 'vhd', array $query = []): Response
    {
        return $this->client->download($this->path().'.'.rawurlencode($format), $query);
    }

    /** Upload from the caller-owned stream's current position (raw or vhd). */
    public function importContent(StreamInterface $source, string $format = 'vhd'): void
    {
        $this->client->upload('PUT', $this->path().'.'.rawurlencode($format), $source);
    }
}
