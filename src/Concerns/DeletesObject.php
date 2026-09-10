<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Concerns;

trait DeletesObject
{
    /** Delete the remote object. XO responds with 204, not a task reference. */
    public function delete(): void
    {
        $this->client->delete($this->path());
    }
}
