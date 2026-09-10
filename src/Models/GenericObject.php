<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\HydratesModels;

/**
 * Fallback model for collections this package does not model explicitly —
 * alarms, messages, VDIs, VIFs, PIFs, backup jobs, and so on.
 *
 * You still get attribute access, `href`, `fetch()` and reference following, so
 * an unmodelled endpoint degrades to something workable rather than to an
 * exception. This is what keeps the generic escape hatch honest.
 */
class GenericObject extends Model
{
    use HydratesModels;

    public static function endpoint(): string
    {
        return '';
    }

    /**
     * Which collection this object came from, derived from its href.
     */
    public function collection(): ?string
    {
        $href = $this->href();

        return $href === null ? null : ModelResolver::collectionFor($href);
    }

    /**
     * Re-resolve into the specific model class for its collection, when one
     * exists. Useful after following a reference of unknown type.
     */
    public function specialise(): Model
    {
        $href = $this->href();

        if ($href === null) {
            return $this;
        }

        $class = ModelResolver::forHref($href);

        return $class === self::class ? $this : new $class($this->attributes, $this->client);
    }
}
