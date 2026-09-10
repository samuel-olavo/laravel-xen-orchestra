<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests\Feature;

use SamuelOlavo\XenOrchestra\Exceptions\RequestException;
use SamuelOlavo\XenOrchestra\Models\AclRole;
use SamuelOlavo\XenOrchestra\Models\Group;
use SamuelOlavo\XenOrchestra\Models\Host;
use SamuelOlavo\XenOrchestra\Models\ModelResolver;
use SamuelOlavo\XenOrchestra\Models\Pool;
use SamuelOlavo\XenOrchestra\Models\StorageRepository;
use SamuelOlavo\XenOrchestra\Models\User;
use SamuelOlavo\XenOrchestra\Models\VirtualDisk;
use SamuelOlavo\XenOrchestra\Models\VirtualMachine;
use SamuelOlavo\XenOrchestra\Tests\TestCase;

class RestApi037Test extends TestCase
{
    private function assertBody(array $expected): void
    {
        $this->assertSame($expected, json_decode((string) $this->fake->lastRequest()->getBody(), true));
    }

    public function test_disk_update_preserves_api_field_names_and_requires_explicit_refresh(): void
    {
        $this->fake->stub('vdis/disk', ['id' => 'disk', 'name_label' => 'old']);
        $this->fake->stubRaw('vdis/disk', '', 204);
        $this->fake->stub('vdis/disk', ['id' => 'disk', 'name_label' => 'new', 'size' => 4096]);
        $disk = $this->client()->vdis()->find('disk');

        $this->assertSame($disk, $disk->update(['name_label' => 'new', 'name_description' => '', 'size' => 4096]));
        $this->assertBody(['name_label' => 'new', 'name_description' => '', 'size' => 4096]);
        $this->fake->assertSent('vdis/disk', 'PATCH');
        $this->assertSame('old', $disk->name_label);
        $this->assertSame('new', $disk->fetch()->name_label);
    }

    public function test_vm_update_passes_camel_case_null_and_false_unchanged(): void
    {
        $this->fake->stubRaw('vms/vm', '', 204);
        $vm = new VirtualMachine(['id' => 'vm'], $this->client());
        $vm->update(['nameLabel' => 'web-02', 'resourceSet' => null, 'share' => false]);
        $this->assertBody(['nameLabel' => 'web-02', 'resourceSet' => null, 'share' => false]);
        $this->fake->assertSent('vms/vm', 'PATCH');
        $this->assertSame(1, $this->fake->requestCount());
    }

    public function test_failed_update_keeps_local_attributes(): void
    {
        $this->fake->stubError('vdis/disk', 422, ['message' => 'cannot shrink']);
        $disk = new VirtualDisk(['id' => 'disk', 'size' => 4096], $this->client());
        try {
            $disk->update(['size' => 1]);
            $this->fail('Expected the API error to propagate.');
        } catch (RequestException $e) {
            $this->assertSame(4096, $disk->size);
        }
    }

    public function test_sr_creation_returns_a_partial_sr_without_an_extra_request(): void
    {
        $this->fake->stub('srs', ['id' => 'sr-new'], 201);
        $body = ['hostId' => 'host', 'SR_type' => 'nfs', 'name_label' => 'data', 'device_config' => ['server' => '10.0.0.2', 'serverpath' => '/data']];
        $sr = $this->client()->srs()->create($body);
        $this->assertInstanceOf(StorageRepository::class, $sr);
        $this->assertSame('srs/sr-new', $sr->href());
        $this->assertTrue($sr->isPartial());
        $this->assertBody($body);
        $this->assertSame(1, $this->fake->requestCount());
        $this->fake->assertSent('srs', 'POST');
    }

    public function test_pool_maintenance_preserves_positional_sync_and_supports_pinned_vms(): void
    {
        $this->fake->stubRaw('pools/pool/actions/*', '', 204);
        $pool = new Pool(['id' => 'pool'], $this->client());
        foreach (['rollingUpdate', 'rollingReboot'] as $method) {
            $this->assertTrue($pool->$method(true)->successful());
            $this->assertBody([]);
            $pool->$method(sync: true, shutdownPinnedVms: true);
            $this->assertBody(['shutdownPinnedVms' => true]);
            $this->fake->assertQuery('sync', 'true');
            $pool->$method(shutdownPinnedVms: false);
            $this->assertBody(['shutdownPinnedVms' => false]);
            $this->assertSame('', $this->fake->lastRequest()->getUri()->getQuery());
        }
    }

    public function test_host_disable_sends_auto_enable_and_evacuation_options(): void
    {
        $this->fake->stub('hosts/host/actions/disable', '/rest/v0/tasks/task');
        $host = new Host(['id' => 'host'], $this->client());
        $body = ['autoEnable' => false, 'evacuate' => true, 'force' => false, 'vmIdsToForceMigrate' => ['vm']];
        $this->assertSame('task', $host->disable($body)->id());
        $this->assertBody($body);
        $this->fake->assertSent('hosts/host/actions/disable', 'POST');
        $this->fake->stubRaw('hosts/host/actions/enable', '', 204);
        $this->assertTrue($host->enable(sync: true)->successful());
    }

    public function test_synchronous_clone_keeps_created_id_as_result_and_does_not_wait_on_it(): void
    {
        $this->fake->stub('vms/vm/actions/clone', ['id' => 'new-vm'], 201);
        $vm = new VirtualMachine(['id' => 'vm'], $this->client());
        $task = $vm->cloneVm(['name_label' => 'copy', 'fast' => false], sync: true)->wait();
        $this->assertTrue($task->successful());
        $this->assertNull($task->id());
        $this->assertSame(['id' => 'new-vm'], $task->result());
        $this->assertBody(['name_label' => 'copy', 'fast' => false]);
        $this->assertSame(1, $this->fake->requestCount());
    }

    public function test_migration_and_snapshot_restore_return_asynchronous_tasks(): void
    {
        $this->fake->stub('vms/vm/actions/*', '/rest/v0/tasks/task');
        $vm = new VirtualMachine(['id' => 'vm'], $this->client());
        $body = ['hostId' => 'host', 'srIdByVdiId' => ['disk' => 'sr'], 'networkIdByVifId' => ['vif' => 'network']];
        $this->assertSame('task', $vm->migrate($body)->id());
        $this->assertBody($body);
        $this->fake->assertSent('vms/vm/actions/migrate', 'POST');
        $this->assertSame('task', $vm->revertSnapshot('snapshot', snapshotBefore: true)->id());
        $this->assertBody(['snapshotId' => 'snapshot', 'snapshotBefore' => true]);
        $this->fake->assertSent('vms/vm/actions/revert_snapshot', 'POST');
    }

    public function test_pool_creation_passes_ha_priority_and_preserves_sync_result(): void
    {
        $this->fake->stub('pools/pool/actions/create_vm', ['id' => 'vm'], 201);
        $pool = new Pool(['id' => 'pool'], $this->client());
        $body = ['template' => 'template', 'name_label' => 'web', 'high_availability' => 'restart'];
        $this->assertSame(['id' => 'vm'], $pool->createVm($body, sync: true)->wait()->result());
        $this->assertBody($body);
        $this->assertSame(1, $this->fake->requestCount());
    }

    public function test_pool_network_actions_use_the_actual_underscore_route(): void
    {
        $this->fake->stub('pools/pool/actions/*', '/rest/v0/tasks/task');
        $pool = new Pool(['id' => 'pool'], $this->client());
        foreach (['createBondedNetwork' => 'create_bonded_network', 'createInternalNetwork' => 'create_internal_network', 'managementReconfigure' => 'management_reconfigure'] as $method => $route) {
            $pool->$method(['network' => 'network']);
            $this->fake->assertSent('pools/pool/actions/'.$route, 'POST');
            $this->assertBody(['network' => 'network']);
        }
    }

    public function test_permission_relations_hydrate_their_own_resource_types(): void
    {
        $client = $this->client();
        $role = new AclRole(['id' => 'role'], $client);
        $this->fake->stub('acl-roles/role/users', ['/rest/v0/users/user']);
        $this->assertInstanceOf(User::class, $role->users()->first());
        $this->fake->stub('acl-roles/role/groups', [['id' => 'group', 'name' => 'admins']]);
        $group = $role->groups(['name'], ['filter' => 'name:admins', 'limit' => 1])->first();
        $this->assertInstanceOf(Group::class, $group);
        $this->fake->assertQuery('fields', 'name')->assertQuery('filter', 'name:admins')->assertQuery('limit', '1');
        $this->fake->stub('groups/group/acl-roles', ['/rest/v0/acl-roles/role']);
        $this->assertInstanceOf(AclRole::class, $group->aclRoles()->first());
    }

    public function test_new_collections_support_filters_and_reference_resolution(): void
    {
        foreach (['vdis' => VirtualDisk::class, 'aclRoles' => AclRole::class, 'groups' => Group::class, 'users' => User::class] as $method => $class) {
            $endpoint = $class::endpoint();
            $this->fake->stub($endpoint, ['/rest/v0/'.$endpoint.'/id']);
            $resource = $this->client()->$method();
            $model = $resource->fields('id')->limit(2)->get()->first();
            $this->assertInstanceOf($class, $model);
            $this->assertSame($class, ModelResolver::forHref((string) $model->href()));
            $this->assertSame([], $resource->query());
            $this->fake->assertQuery('fields', 'id')->assertQuery('limit', '2');
        }
    }

    public function test_vm_disks_are_editable_models(): void
    {
        $this->fake->stub('vms/vm/vdis', [['id' => 'disk', 'name_label' => 'data']]);
        $vm = new VirtualMachine(['id' => 'vm'], $this->client());
        $disk = $vm->disks(['name_label'])->first();
        $this->assertInstanceOf(VirtualDisk::class, $disk);
        $this->assertSame('vdis/disk', $disk->href());
    }
}
