<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests\Feature;

use SamuelOlavo\XenOrchestra\Models\BackupArchive;
use SamuelOlavo\XenOrchestra\Models\BackupRepository;
use SamuelOlavo\XenOrchestra\Models\Pool;
use SamuelOlavo\XenOrchestra\Models\VirtualDisk;
use SamuelOlavo\XenOrchestra\Models\VirtualDiskSnapshot;
use SamuelOlavo\XenOrchestra\Tests\TestCase;

class RestApi040Test extends TestCase
{
    public function test_live_mount_preserves_result_and_encodes_archive_and_mount_ids(): void
    {
        $archive = new BackupArchive(['id' => 'repo/backup.json'], $this->client());
        $result = ['id' => 'mount/1', 'srId' => 'sr'];
        $this->fake->stub('backup-archives/repo%2Fbackup.json/actions/mount_live_disk', $result, 201);
        $body = ['diskId' => '/vdis/disk.vhd', 'hostId' => 'host'];
        $task = $archive->mountLiveDisk($body, sync: true);
        $this->assertTrue($task->successful());
        $this->assertSame($result, $task->result());
        $this->assertSame($body, json_decode((string) $this->fake->lastRequest()->getBody(), true));
        $this->fake->stubRaw('backup-archives/repo%2Fbackup.json/live_disks/mount%2F1/actions/unmount', '', 204);
        $this->assertTrue($archive->unmountLiveDisk('mount/1', sync: true)->successful());
        $this->fake->assertSent('backup-archives/repo%2Fbackup.json/live_disks/mount%2F1/actions/unmount', 'POST');
        $this->fake->assertQuery('sync', 'true');
    }

    public function test_rolling_options_preserve_false_and_existing_positional_arguments(): void
    {
        $pool = new Pool(['id' => 'pool'], $this->client());
        $this->fake->stubRaw('pools/pool/actions/*', '', 204);
        $pool->rollingUpdate(true, false);
        $this->assertSame(['shutdownPinnedVms' => false], json_decode((string) $this->fake->lastRequest()->getBody(), true));
        $pool->rollingUpdate(bypassBackupCheck: false, acceptCurrentStateAsBaseline: true);
        $this->assertSame(['bypassBackupCheck' => false, 'acceptCurrentStateAsBaseline' => true], json_decode((string) $this->fake->lastRequest()->getBody(), true));
        $pool->rollingReboot(bypassBackupCheck: false);
        $this->assertSame(['bypassBackupCheck' => false], json_decode((string) $this->fake->lastRequest()->getBody(), true));
        $pool->finalizeRollingUpdate(sync: true, force: false);
        $this->assertSame(['force' => false], json_decode((string) $this->fake->lastRequest()->getBody(), true));
        $pool->rollingUpdate();
        $this->assertSame('{}', (string) $this->fake->lastRequest()->getBody());
    }

    public function test_reclaim_space_passes_options_and_returns_server_results(): void
    {
        $this->fake->stub('backup-repositories/repo/actions/reclaim-space', ['reclaimed' => 42]);
        $options = ['vmUuid' => 'vm', 'merge' => false, 'remove' => true];
        $task = (new BackupRepository(['id' => 'repo'], $this->client()))->reclaimSpace($options, sync: true);
        $this->assertSame(['reclaimed' => 42], $task->result());
        $this->assertSame($options, json_decode((string) $this->fake->lastRequest()->getBody(), true));
    }

    public function test_qcow2_exports_preserve_binary_body_and_download_size(): void
    {
        foreach ([VirtualDisk::class, VirtualDiskSnapshot::class] as $model) {
            $this->fake->stubRaw($model::endpoint().'/disk.qcow2', "QFI\xfb", headers: ['Content-Length' => '4']);
            $response = (new $model(['id' => 'disk'], $this->client()))->export('qcow2');
            $this->assertSame("QFI\xfb", $response->body());
            $this->assertSame('4', $response->header('Content-Length'));
        }
    }

    public function test_non_xapi_subscriptions_use_upstream_collection_names(): void
    {
        foreach (['user', 'group', 'acl-privilege', 'acl-role', 'proxy', 'server', 'backup-repository', 'backup-job', 'schedule'] as $collection) {
            $this->fake->stub('events/'.$collection.'/subscriptions', ['id' => $collection], 201);
            $this->assertSame($collection, $this->client()->events()->subscribe($collection, $collection, ['id']));
            $this->assertSame(['collection' => $collection, 'fields' => ['id']], json_decode((string) $this->fake->lastRequest()->getBody(), true));
        }
    }
}
