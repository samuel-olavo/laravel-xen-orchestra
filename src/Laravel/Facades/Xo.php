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
 * @method static bool ping()
 * @method static array dashboard()
 * @method static mixed get(string $path, array $query = [])
 * @method static mixed post(string $path, array $body = [], array $query = [])
 * @method static mixed put(string $path, array $body = [], array $query = [])
 * @method static mixed patch(string $path, array $body = [], array $query = [])
 * @method static mixed delete(string $path, array $query = [])
 * @method static \SamuelOlavo\XenOrchestra\Client\Response request(string $method, string $path, ?array $body = null, array $query = [])
 * @method static string uriFor(string $path, array $query = [])
 * @method static string baseUrl()
 *
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
