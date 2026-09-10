<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Exceptions;

/**
 * A task did not reach a terminal state within the allotted time.
 *
 * The task itself is still running on the XOA — this only means we stopped
 * waiting for it. The id is kept so callers can pick it up again later.
 */
class TaskTimeoutException extends XenOrchestraException
{
    public function __construct(
        public readonly string $taskId,
        public readonly int $timeout,
    ) {
        parent::__construct(
            sprintf('Timed out after %ds waiting for XO task %s. The task may still be running.', $timeout, $taskId)
        );
    }
}
