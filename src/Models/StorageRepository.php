<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\HasAlarms;
use SamuelOlavo\XenOrchestra\Concerns\HasMessages;
use SamuelOlavo\XenOrchestra\Concerns\HasTags;
use SamuelOlavo\XenOrchestra\Concerns\HasTasks;

/** A storage repository (SR). */
class StorageRepository extends Model
{
    use HasAlarms;
    use HasMessages;
    use HasTags;
    use HasTasks;

    public static function endpoint(): string
    {
        return 'srs';
    }

    public function name(): ?string
    {
        $name = $this->get('name_label');

        return is_string($name) ? $name : null;
    }

    public function size(): ?int
    {
        $size = $this->get('size');

        return is_numeric($size) ? (int) $size : null;
    }

    public function used(): ?int
    {
        $used = $this->get('physical_usage');

        return is_numeric($used) ? (int) $used : null;
    }

    /** Percentage of the SR in use, or null when either figure is missing. */
    public function usagePercentage(): ?float
    {
        $size = $this->size();
        $used = $this->used();

        if ($size === null || $used === null || $size === 0) {
            return null;
        }

        return round($used / $size * 100, 2);
    }
}
