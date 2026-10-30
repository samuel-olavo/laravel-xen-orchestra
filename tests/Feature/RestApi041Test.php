<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests\Feature;

use SamuelOlavo\XenOrchestra\Models\Host;
use SamuelOlavo\XenOrchestra\Models\ModelResolver;
use SamuelOlavo\XenOrchestra\Models\Network;
use SamuelOlavo\XenOrchestra\Models\Plugin;
use SamuelOlavo\XenOrchestra\Models\Pool;
use SamuelOlavo\XenOrchestra\Models\VirtualDisk;
use SamuelOlavo\XenOrchestra\Models\VirtualDiskSnapshot;
use SamuelOlavo\XenOrchestra\Models\VirtualInterface;
use SamuelOlavo\XenOrchestra\Models\VirtualMachine;
use SamuelOlavo\XenOrchestra\Tests\TestCase;

class RestApi041Test extends TestCase
{
    public function test_plugin_configuration_is_not_wrapped_and_does_not_refresh(): void
    {
        $plugin = new Plugin(['id' => 'plugin/name'], $this->client());
        $this->fake->stubRaw('plugins/plugin%2Fname/configuration', '', 204);
        $configuration = ['enabled' => false, 'server' => 'example.org'];
        $this->assertSame($plugin, $plugin->configure($configuration));
        $this->assertSame($configuration, $this->body());
        $this->assertSame(1, $this->fake->requestCount());
        $this->assertSame($plugin, $plugin->deleteConfiguration());
        $this->fake->assertSent('plugins/plugin%2Fname/configuration', 'DELETE');
        $this->assertSame(Plugin::class, ModelResolver::forHref('/rest/v0/plugins/example'));
    }

    public function test_plugin_test_wraps_data_and_preserves_sync_result(): void
    {
        $this->fake->stub('plugins/example/actions/test', ['success' => true]);
        $plugin = new Plugin(['id' => 'example'], $this->client());
        $task = $plugin->test(['recipient' => 'test@example.org'], sync: true);
        $this->assertSame(['data' => ['recipient' => 'test@example.org']], $this->body());
        $this->assertSame(['success' => true], $task->result());
        $this->fake->assertQuery('sync', 'true');
        $plugin->test();
        $this->assertSame('{}', (string) $this->fake->lastRequest()->getBody());
        $plugin->test([]);
        $this->assertSame('{"data":{}}', (string) $this->fake->lastRequest()->getBody());
    }

    public function test_probe_preserves_credentials_and_array_results(): void
    {
        $result = [['iqn' => 'iqn.example', 'ip' => '192.0.2.1']];
        $this->fake->stub('hosts/host/actions/probe_iscsi_iqns', $result);
        $body = ['targetIp' => '192.0.2.1', 'port' => 3260, 'chapUser' => 'example', 'chapPassword' => 'test-only'];
        $task = (new Host(['id' => 'host'], $this->client()))->probeIscsiIqns($body, sync: true);
        $this->assertSame($body, $this->body());
        $this->assertSame($result, $task->result());
    }

    public function test_new_fields_pass_through_existing_helpers(): void
    {
        $this->fake->stubRaw('*', '', 204);
        (new VirtualMachine(['id' => 'vm'], $this->client()))->migrate(['bypassCpuCheck' => false]);
        $this->assertSame(['bypassCpuCheck' => false], $this->body());
        $pool = new Pool(['id' => 'pool'], $this->client());
        $pool->createNetwork(['name' => 'backup', 'pif' => 'pif', 'insecureNbd' => true]);
        $this->assertTrue($this->body()['insecureNbd']);
        $pool->createVm(['vdis' => [['destroy' => true, 'userdevice' => '1']]]);
        $this->assertSame([['destroy' => true, 'userdevice' => '1']], $this->body()['vdis']);
        $this->client()->aclRoles()->create(['name' => 'reader', 'privileges' => ['privilege']]);
        $this->assertSame(['privilege'], $this->body()['privileges']);
    }

    public function test_vmdk_export_remains_binary(): void
    {
        foreach ([VirtualDisk::class, VirtualDiskSnapshot::class] as $model) {
            $this->fake->stubRaw($model::endpoint().'/disk.vmdk', "KDMV\0");
            $this->assertSame("KDMV\0", (new $model(['id' => 'disk'], $this->client()))->export('vmdk')->body());
        }
    }

    public function test_new_sse_collections_use_exact_names(): void
    {
        foreach (['plugin', 'backup-archive', 'authentication_token', 'backup-log'] as $collection) {
            $this->fake->stub('events/'.$collection.'/subscriptions', ['id' => 'subscription']);
            $this->client()->events()->subscribe($collection, $collection);
            $this->assertSame(['collection' => $collection], $this->body());
        }
    }

    public function test_sdn_priority_survives_add_update_and_delete(): void
    {
        $this->fake->stubRaw('*', '', 204);
        $rule = ['priority' => 100, 'action' => 'allow'];
        foreach ([Network::class, VirtualInterface::class] as $class) {
            $model = new $class(['id' => 'object'], $this->client());
            $model->addTrafficRule($rule, sync: true);
            $this->assertSame($rule, $this->body());
            $model->deleteTrafficRule($rule, sync: true);
            $this->assertSame($rule, $this->body());
            $update = ['oldRule' => $rule, 'newRule' => ['priority' => 101, 'action' => 'allow']];
            $model->updateTrafficRule($update, sync: true);
            $this->assertSame($update, $this->body());
        }
    }

    public function test_missing_identity_does_not_send_plugin_mutations(): void
    {
        try {
            (new Plugin([], $this->client()))->configure([]);
            $this->fail('Expected an identity check.');
        } catch (\LogicException) {
            $this->fake->assertNothingSent();
        }
    }

    private function body(): array
    {
        return json_decode((string) $this->fake->lastRequest()->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }
}
