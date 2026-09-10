<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Exceptions;

/**
 * HTTP 5xx — the XOA itself failed. Usually worth retrying.
 */
class ServerException extends RequestException
{
}
