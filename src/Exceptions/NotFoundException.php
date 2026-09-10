<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Exceptions;

/**
 * HTTP 404 — no object with that UUID, or the endpoint does not exist in this
 * version of @xen-orchestra/rest-api.
 */
class NotFoundException extends RequestException
{
}
