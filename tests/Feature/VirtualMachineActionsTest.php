<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests\Feature;

use SamuelOlavo\XenOrchestra\Models\Task;
use SamuelOlavo\XenOrchestra\Tests\TestCase;

class VirtualMachineActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fake->stub('vms/abc', ['id' => 'abc', 'name_label' => 'web-01', 'tags' => ['prod']]);
    }

    public function test_start_posts_to_the_action_endpoint(): void
    {
        // XO answers an action with the task href, encoded as a JSON string.
        $this->fake->stub('vms/abc/actions/start', '/rest/v0/tasks/task-1');

        $task = $this->client()->vm('abc')->start();

        $this->assertInstanceOf(Task::class, $task);
        $this->assertSame('task-1', $task->id());

        $request = $this->fake->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/rest/v0/vms/abc/actions/start', $request->getUri()->getPath());
        $this->assertSame('', $request->getUri()->getQuery());
    }

    public function test_an_action_answering_with_a_bare_id_also_works(): void
    {
        $this->fake->stub('vms/abc/actions/start', 'task-77');

        $this->assertSame('task-77', $this->client()->vm('abc')->start()->id());
    }

    /**
     * sync=true and wait=true are different mechanisms and easy to confuse:
     * sync blocks the original request, wait long-polls the task afterwards.
     */
    public function test_sync_true_adds_the_query_flag_and_needs_no_waiting(): void
    {
        $this->fake->stub('vms/abc/actions/start', ['status' => 'success']);

        $task = $this->client()->vm('abc')->start(sync: true);

        $this->assertSame('sync=true', $this->fake->lastRequest()->getUri()->getQuery());
        $this->assertTrue($task->finished());
    }

    public function test_force_maps_to_the_hard_variants(): void
    {
        $this->fake->stub('vms/abc/actions/*', '/rest/v0/tasks/t');

        $this->client()->vm('abc')->reboot(force: true);
        $this->assertSame('/rest/v0/vms/abc/actions/hard_reboot', $this->fake->lastRequest()->getUri()->getPath());

        $this->client()->vm('abc')->shutdown(force: true);
        $this->assertSame('/rest/v0/vms/abc/actions/hard_shutdown', $this->fake->lastRequest()->getUri()->getPath());
    }

    public function test_the_default_variants_are_clean(): void
    {
        $this->fake->stub('vms/abc/actions/*', '/rest/v0/tasks/t');

        $this->client()->vm('abc')->shutdown();
        $this->assertSame('/rest/v0/vms/abc/actions/clean_shutdown', $this->fake->lastRequest()->getUri()->getPath());

        $this->client()->vm('abc')->reboot();
        $this->assertSame('/rest/v0/vms/abc/actions/clean_reboot', $this->fake->lastRequest()->getUri()->getPath());
    }

    public function test_snapshot_sends_the_label_in_the_body(): void
    {
        $this->fake->stub('vms/abc/actions/snapshot', '/rest/v0/tasks/task-4');

        $this->client()->vm('abc')->snapshot('before-upgrade');

        $this->assertSame(
            '{"name_label":"before-upgrade"}',
            (string) $this->fake->lastRequest()->getBody(),
        );
    }

    public function test_add_tag_puts_and_updates_local_state(): void
    {
        $this->fake->stub('vms/abc/tags/*', null);

        $vm = $this->client()->vm('abc')->addTag('critical');

        $this->assertSame('PUT', $this->fake->lastRequest()->getMethod());
        $this->assertSame('/rest/v0/vms/abc/tags/critical', $this->fake->lastRequest()->getUri()->getPath());
        $this->assertSame(['prod', 'critical'], $vm->tags());
        $this->assertTrue($vm->hasTag('critical'));
    }

    public function test_remove_tag_deletes_and_updates_local_state(): void
    {
        $this->fake->stub('vms/abc/tags/*', null);

        $vm = $this->client()->vm('abc')->removeTag('prod');

        $this->assertSame('DELETE', $this->fake->lastRequest()->getMethod());
        $this->assertSame([], $vm->tags());
    }
}
