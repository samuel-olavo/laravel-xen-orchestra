<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Exceptions;

use RuntimeException;

/**
 * Base class for every exception thrown by this package.
 *
 * Catching this one type is enough to catch anything the client can throw.
 */
class XenOrchestraException extends RuntimeException
{
}
