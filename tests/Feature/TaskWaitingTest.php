<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests\Feature;

use SamuelOlavo\XenOrchestra\Exceptions\TaskFailedException;
use SamuelOlavo\XenOrchestra\Models\Task;
use SamuelOlavo\XenOrchestra\Tests\TestCase;

class TaskWaitingTest extends TestCase
{
    /**
     * The point of this test is the request *count*.
     *
     * A naive `while (true) { sleep(2); get(); }` implementation would issue
     * many requests here. Because wait() uses XO's server-side blocking
     * endpoint, one finished task costs exactly one request.
     */
    public function test_wait_long_polls_rather_than_busy_polling(): void
    {
        $this->fake->stub('vms/abc', ['id' => 'abc']);
        $this->fake->stub('vms/abc/actions/start', '/rest/v0/tasks/task-1');
        $this->fake->stub('tasks/task-1', ['id' => 'task-1', 'status' => 'success', 'result' => 'done']);

        $task = $this->client()->vm('abc')->start();
        $afterStart = $this->fake->requestCount();

        $task->wait();

        $this->assertSame($afterStart + 1, $this->fake->requestCount());
        $this->assertSame('wait=true', $this->fake->lastRequest()->getUri()->getQuery());
        $this->assertTrue($task->successful());
        $this->assertSame('done', $task->result());
    }

    public function test_wait_reconnects_while_the_task_is_still_pending(): void
    {
        $this->fake->stub('tasks/t9', ['id' => 't9', 'status' => 'pending']);
        $this->fake->stub('tasks/t9', ['id' => 't9', 'status' => 'pending']);
        $this->fake->stub('tasks/t9', ['id' => 't9', 'status' => 'success']);

        $task = new Task(['id' => 't9', 'status' => 'pending'], $this->client());
        $task->wait();

        $this->assertTrue($task->successful());
        $this->assertSame(3, $this->fake->requestCount());
    }

    public function test_wait_throws_on_a_failed_task(): void
    {
        $this->fake->stub('tasks/t1', ['id' => 't1', 'status' => 'failure', 'result' => 'VM_BAD_POWER_STATE']);

        $task = new Task(['id' => 't1', 'status' => 'pending'], $this->client());

        $this->expectException(TaskFailedException::class);
        $this->expectExceptionMessageMatches('/VM_BAD_POWER_STATE/');

        $task->wait();
    }

    public function test_wait_can_return_the_failure_instead_of_throwing(): void
    {
        $this->fake->stub('tasks/t2', ['id' => 't2', 'status' => 'failure']);

        $task = (new Task(['id' => 't2', 'status' => 'pending'], $this->client()))->wait(throw: false);

        $this->assertTrue($task->failed());
        $this->assertFalse($task->successful());
    }

    public function test_an_already_finished_task_makes_no_request(): void
    {
        $task = new Task(['id' => 't3', 'status' => 'success'], $this->client());
        $task->wait();

        $this->assertSame(0, $this->fake->requestCount());
    }
}
