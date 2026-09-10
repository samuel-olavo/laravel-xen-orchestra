<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Client\Response;
use SamuelOlavo\XenOrchestra\Concerns\DeletesObject;
use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTags;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;

class VirtualDiskSnapshot extends Model
{
    use HasTags;
    use HasTasks;
    use HasMessages;
    use HasAlarms;
    use DeletesObject;
    public static function endpoint(): string
    {
        return 'vdi-snapshots';
    }

    /** The response body is streamed; use saveTo() or consume its PSR-7 stream. */
    public function export(string $format = 'vhd', array $query = []): Response
    {
        return $this->client->download($this->path().'.'.rawurlencode($format), $query);
    }
}
