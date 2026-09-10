<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Exceptions;

use SamuelOlavo\XenOrchestra\Models\Task;

/**
 * A task reached a terminal state that is not success.
 *
 * Only thrown by Task::wait() when $throw is left on; use wait(throw: false)
 * and inspect the task yourself if failure is an expected outcome.
 */
class TaskFailedException extends XenOrchestraException
{
    public function __construct(public readonly Task $task)
    {
        parent::__construct(sprintf(
            'XO task %s finished with status "%s"%s',
            $task->id(),
            $task->status(),
            $task->errorMessage() !== null ? ': '.$task->errorMessage() : ''
        ));
    }
}
