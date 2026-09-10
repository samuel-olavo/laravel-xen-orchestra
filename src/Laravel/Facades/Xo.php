<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Laravel\Facades;

use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\Facades\Facade;
use SamuelOlavo\XenOrchestra\Client\XenOrchestraClient;
use SamuelOlavo\XenOrchestra\Testing\FakeHttpClient;

/**
 * @method static \SamuelOlavo\XenOrchestra\Resources\VirtualMachines vms()
 * @method static \SamuelOlavo\XenOrchestra\Models\VirtualMachine vm(string $id, array $fields = [])
 * @method static \SamuelOlavo\XenOrchestra\Resources\Hosts hosts()
 * @method static \SamuelOlavo\XenOrchestra\Resources\Pools pools()
 * @method static \SamuelOlavo\XenOrchestra\Resources\StorageRepositories storageRepositories()
 * @method static \SamuelOlavo\XenOrchestra\Resources\StorageRepositories srs()
 * @method static \SamuelOlavo\XenOrchestra\Resources\Networks networks()
 * @method static \SamuelOlavo\XenOrchestra\Resources\Tasks tasks()
 * @method static \SamuelOlavo\XenOrchestra\Resources\VirtualDisks vdis()
 * @method static \SamuelOlavo\XenOrchestra\Resources\AclRoles aclRoles()
 * @method static \SamuelOlavo\XenOrchestra\Resources\Groups groups()
 * @method static \SamuelOlavo\XenOrchestra\Resources\Users users()
 * @method static bool ping()
 * @method static array dashboard()
 * @method static mixed get(string $path, array $query = [])
 * @method static mixed post(string $path, array $body = [], array $query = [])
 * @method static mixed put(string $path, array $body = [], array $query = [])
 * @method static mixed patch(string $path, array $body = [], array $query = [])
 * @method static mixed delete(string $path, array $query = [])
 * @method static \SamuelOlavo\XenOrchestra\Client\Response request(string $method, string $path, array|\Psr\Http\Message\StreamInterface|null $body = null, array $query = [], array $headers = [], bool $stream = false)
 * @method static string uriFor(string $path, array $query = [])
 * @method static string baseUrl()
 *
 * @method static \SamuelOlavo\XenOrchestra\Resources\AclPrivileges aclPrivileges()
 * @method static \SamuelOlavo\XenOrchestra\Resources\Alarms alarms()
 * @method static \SamuelOlavo\XenOrchestra\Resources\Messages messages()
 * @method static \SamuelOlavo\XenOrchestra\Resources\BackupArchives backupArchives()
 * @method static \SamuelOlavo\XenOrchestra\Resources\BackupJobs backupJobs()
 * @method static \SamuelOlavo\XenOrchestra\Resources\BackupLogs backupLogs()
 * @method static \SamuelOlavo\XenOrchestra\Resources\BackupRepositories backupRepositories()
 * @method static \SamuelOlavo\XenOrchestra\Resources\RestoreLogs restoreLogs()
 * @method static \SamuelOlavo\XenOrchestra\Resources\Schedules schedules()
 * @method static \SamuelOlavo\XenOrchestra\Resources\Servers servers()
 * @method static \SamuelOlavo\XenOrchestra\Resources\PhysicalBlockDevices pbds()
 * @method static \SamuelOlavo\XenOrchestra\Resources\PciDevices pcis()
 * @method static \SamuelOlavo\XenOrchestra\Resources\PhysicalGpus pgpus()
 * @method static \SamuelOlavo\XenOrchestra\Resources\PhysicalInterfaces pifs()
 * @method static \SamuelOlavo\XenOrchestra\Resources\Proxies proxies()
 * @method static \SamuelOlavo\XenOrchestra\Resources\StorageManagers sms()
 * @method static \SamuelOlavo\XenOrchestra\Resources\VirtualBlockDevices vbds()
 * @method static \SamuelOlavo\XenOrchestra\Resources\VirtualInterfaces vifs()
 * @method static \SamuelOlavo\XenOrchestra\Resources\VirtualDiskSnapshots vdiSnapshots()
 * @method static \SamuelOlavo\XenOrchestra\Resources\VirtualMachineSnapshots vmSnapshots()
 * @method static \SamuelOlavo\XenOrchestra\Resources\VirtualMachineTemplates vmTemplates()
 * @method static \SamuelOlavo\XenOrchestra\Resources\VirtualMachineControllers vmControllers()
 * @method static \SamuelOlavo\XenOrchestra\Resources\Events events()
 * @method static \SamuelOlavo\XenOrchestra\Client\Response download(string $path, array $query = [])
 * @method static \SamuelOlavo\XenOrchestra\Client\Response upload(string $method, string $path, \Psr\Http\Message\StreamInterface $source, array $query = [])
 * @method static array openApi()
 * @method static array guiRoutes()
 * @method static array mcpStatus()
 * @see XenOrchestraClient
 */
class Xo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return XenOrchestraClient::class;
    }

    /**
     * Swap the bound client for one backed by stubbed responses.
     *
     * Returns the fake so you can assert against it:
     *
     *     $fake = Xo::fake([
     *         'vms' => [['id' => 'abc', 'name_label' => 'web-01']],
     *     ]);
     *
     *     Xo::vms()->fields(['name_label'])->get();
     *
     *     $fake->assertSent('vms', 'GET')->assertQuery('fields', 'name_label');
     *
     * @param  array<string, mixed>  $stubs  path pattern => payload
     */
    public static function fake(array $stubs = []): FakeHttpClient
    {
        $fake = new FakeHttpClient($stubs);
        $factory = new HttpFactory();

        $client = new XenOrchestraClient(
            'https://xo.test',
            'fake-token',
            $fake,
            $factory,
            $factory,
        );

        static::swap($client);

        return $fake;
    }
}
