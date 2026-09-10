<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Client;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\LimitStream;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use SamuelOlavo\XenOrchestra\Exceptions\ConnectionException;
use SamuelOlavo\XenOrchestra\Exceptions\RequestException;
use SamuelOlavo\XenOrchestra\Models\VirtualMachine;
use SamuelOlavo\XenOrchestra\Resources\AclPrivileges;
use SamuelOlavo\XenOrchestra\Resources\AclRoles;
use SamuelOlavo\XenOrchestra\Resources\Alarms;
use SamuelOlavo\XenOrchestra\Resources\BackupArchives;
use SamuelOlavo\XenOrchestra\Resources\BackupJobs;
use SamuelOlavo\XenOrchestra\Resources\BackupLogs;
use SamuelOlavo\XenOrchestra\Resources\BackupRepositories;
use SamuelOlavo\XenOrchestra\Resources\Events;
use SamuelOlavo\XenOrchestra\Resources\Groups;
use SamuelOlavo\XenOrchestra\Resources\Hosts;
use SamuelOlavo\XenOrchestra\Resources\Messages;
use SamuelOlavo\XenOrchestra\Resources\Networks;
use SamuelOlavo\XenOrchestra\Resources\PciDevices;
use SamuelOlavo\XenOrchestra\Resources\PhysicalBlockDevices;
use SamuelOlavo\XenOrchestra\Resources\PhysicalGpus;
use SamuelOlavo\XenOrchestra\Resources\PhysicalInterfaces;
use SamuelOlavo\XenOrchestra\Resources\Pools;
use SamuelOlavo\XenOrchestra\Resources\Proxies;
use SamuelOlavo\XenOrchestra\Resources\RestoreLogs;
use SamuelOlavo\XenOrchestra\Resources\Schedules;
use SamuelOlavo\XenOrchestra\Resources\Servers;
use SamuelOlavo\XenOrchestra\Resources\StorageManagers;
use SamuelOlavo\XenOrchestra\Resources\StorageRepositories;
use SamuelOlavo\XenOrchestra\Resources\Tasks;
use SamuelOlavo\XenOrchestra\Resources\Users;
use SamuelOlavo\XenOrchestra\Resources\VirtualBlockDevices;
use SamuelOlavo\XenOrchestra\Resources\VirtualDisks;
use SamuelOlavo\XenOrchestra\Resources\VirtualDiskSnapshots;
use SamuelOlavo\XenOrchestra\Resources\VirtualInterfaces;
use SamuelOlavo\XenOrchestra\Resources\VirtualMachineControllers;
use SamuelOlavo\XenOrchestra\Resources\VirtualMachines;
use SamuelOlavo\XenOrchestra\Resources\VirtualMachineSnapshots;
use SamuelOlavo\XenOrchestra\Resources\VirtualMachineTemplates;

/**
 * Entry point to a Xen Orchestra appliance.
 *
 * Deliberately knows nothing about Laravel: it takes a PSR-18 client and PSR-17
 * factories, and that is the whole of its transport dependency. The Laravel
 * layer in src/Laravel/ wires this up from config; everything else is optional
 * sugar on top.
 *
 * Two XO-specific details are handled here so nothing above has to know:
 *
 *  1. The token travels as a `authenticationToken` **cookie**, not a Bearer
 *     header. This trips up nearly everyone on their first attempt.
 *  2. `href` values in responses are absolute paths (`/rest/v0/vms/<uuid>`), so
 *     path normalisation accepts them verbatim — which is what makes following
 *     relations possible without a hardcoded route map.
 */
class XenOrchestraClient
{
    public const VERSION = '1.0.0';

    public const API_PREFIX = '/rest/v0';

    protected string $baseUrl;

    /**
     * Builds a transport for long-polling `?wait=true`, given a timeout in
     * seconds. A closure rather than an interface so the core still names no
     * concrete HTTP implementation; null simply means "reuse the main client",
     * which stays correct because Task::wait() reconnects on transport timeout.
     *
     * @var (\Closure(int): ClientInterface)|null
     */
    protected ?\Closure $longPollClientFactory = null;

    public function __construct(
        string $baseUrl,
        protected string $token,
        protected ClientInterface $httpClient,
        protected RequestFactoryInterface $requestFactory,
        protected StreamFactoryInterface $streamFactory,
    ) {
        $this->baseUrl = $this->normaliseBaseUrl($baseUrl);
    }

    /**
     * Convenience constructor for plain PHP usage, using Guzzle underneath.
     *
     * @param  bool|string  $verifySsl  false accepts self-signed certificates —
     *                                  common on internal XOA deployments, but
     *                                  do not reach for it out of habit.
     */
    public static function make(
        string $baseUrl,
        string $token,
        bool|string $verifySsl = true,
        int $timeout = 30,
        int $connectTimeout = 10,
    ): static {
        $factory = new HttpFactory();

        $client = new static(
            $baseUrl,
            $token,
            HttpClientFactory::make($verifySsl, $timeout, $connectTimeout),
            $factory,
            $factory,
        );

        return $client->withLongPollClientFactory(
            static fn (int $wait): ClientInterface => HttpClientFactory::makeForWaiting(
                $verifySsl,
                max($wait, $timeout),
                $connectTimeout,
            ),
        );
    }

    // ---------------------------------------------------------------------
    // Resources
    // ---------------------------------------------------------------------

    public function vdis(): VirtualDisks
    {
        return new VirtualDisks($this);
    }

    public function aclRoles(): AclRoles
    {
        return new AclRoles($this);
    }

    public function groups(): Groups
    {
        return new Groups($this);
    }

    public function users(): Users
    {
        return new Users($this);
    }

    public function vms(): VirtualMachines
    {
        return new VirtualMachines($this);
    }

    public function hosts(): Hosts
    {
        return new Hosts($this);
    }

    public function pools(): Pools
    {
        return new Pools($this);
    }

    public function storageRepositories(): StorageRepositories
    {
        return new StorageRepositories($this);
    }

    /** Alias for {@see storageRepositories()}. */
    public function srs(): StorageRepositories
    {
        return $this->storageRepositories();
    }

    public function networks(): Networks
    {
        return new Networks($this);
    }

    public function tasks(): Tasks
    {
        return new Tasks($this);
    }

    /**
     * Shortcut for the most common operation of all.
     *
     * Xo::vm($uuid)->start() reads better than Xo::vms()->find($uuid)->start().
     */
    public function vm(string $id, array $fields = []): VirtualMachine
    {
        return $this->vms()->find($id, $fields);
    }

    // ---------------------------------------------------------------------
    // Health
    // ---------------------------------------------------------------------

    /**
     * Hit `/rest/v0/ping`. Returns true when the XOA answers and the token is
     * accepted; throws for anything else, so failures stay diagnosable.
     */
    public function ping(): bool
    {
        $this->request('GET', 'ping');

        return true;
    }

    /** The cross-pool overview backing the XO dashboard. */
    public function dashboard(): array
    {
        return (array) $this->get('dashboard');
    }

    // ---------------------------------------------------------------------
    // Generic verbs — the escape hatch
    // ---------------------------------------------------------------------

    /**
     * Nothing in this package should ever block you from reaching an endpoint
     * it does not model yet. These four are that guarantee.
     */
    public function get(string $path, array $query = []): mixed
    {
        return $this->request('GET', $path, query: $query)->json();
    }

    public function post(string $path, array $body = [], array $query = []): mixed
    {
        return $this->request('POST', $path, $body, $query)->json();
    }

    public function put(string $path, array $body = [], array $query = []): mixed
    {
        return $this->request('PUT', $path, $body, $query)->json();
    }

    public function patch(string $path, array $body = [], array $query = []): mixed
    {
        return $this->request('PATCH', $path, $body, $query)->json();
    }

    public function delete(string $path, array $query = []): mixed
    {
        return $this->request('DELETE', $path, query: $query)->json();
    }

    // ---------------------------------------------------------------------
    // Transport
    // ---------------------------------------------------------------------

    /**
     * Issue a request and return the wrapped response, throwing on non-2xx.
     *
     * @param  array<string, mixed>|null  $body   JSON-encoded when not null.
     * @param  array<string, mixed>       $query  Nulls and false are dropped;
     *                                            true becomes `true`, arrays are
     *                                            comma-joined (that is how XO
     *                                            expects `fields`).
     *
     * @throws RequestException     on a non-2xx status
     * @throws ConnectionException  when no response arrives at all
     */
    public function request(string $method, string $path, array|StreamInterface|null $body = null, array $query = [], array $headers = [], bool $stream = false): Response
    {
        $uri = $this->uriFor($path, $query);

        $request = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Cookie', 'authenticationToken='.$this->token);

        if ($body instanceof StreamInterface) {
            if ($body->isSeekable() && $body->tell() > 0) {
                // Some transports rewind request bodies. Keep that rewind within
                // the portion the caller selected, rather than uploading a prefix.
                $body = new LimitStream($body, -1, $body->tell());
            }
            $request = $request->withHeader('Content-Type', 'application/octet-stream')->withBody($body);
            if ($body->getSize() !== null && $body->isSeekable()) {
                $request = $request->withHeader('Content-Length', (string) ($body->getSize() - $body->tell()));
            }
        } elseif ($body !== null) {
            $encoded = json_encode($body === [] ? (object) [] : $body, JSON_THROW_ON_ERROR);

            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($encoded));
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        try {
            $psr = $stream && $this->httpClient instanceof StreamingClientInterface
                ? $this->httpClient->sendStreamingRequest($request)
                : $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ConnectionException(
                sprintf('Could not reach Xen Orchestra at %s: %s', $uri, $e->getMessage()),
                previous: $e,
            );
        }

        $response = new Response($psr, $method, $uri);

        if (! $response->successful()) {
            throw RequestException::fromResponse($method, $uri, $psr);
        }

        return $response;
    }

    /** Upload from the stream's current position; the caller owns the stream. */
    public function upload(string $method, string $path, StreamInterface $source, array $query = []): Response
    {
        return $this->request($method, $path, $source, $query);
    }

    public function download(string $path, array $query = []): Response
    {
        return $this->request('GET', $path, query: $query, headers: ['Accept' => 'application/octet-stream'], stream: true);
    }

    public function events(): Events
    {
        return new Events($this);
    }

    public function openApi(): array
    {
        return (array) $this->get('docs/swagger.json');
    }

    public function guiRoutes(): array
    {
        return (array) $this->get('gui-routes');
    }

    public function mcpStatus(): array
    {
        return (array) $this->get('mcp/status');
    }

    /** Build the absolute URI for a path plus query parameters. */
    public function uriFor(string $path, array $query = []): string
    {
        $uri = $this->baseUrl.'/'.$this->normalisePath($path);
        $encoded = $this->buildQuery($query);

        return $encoded === '' ? $uri : $uri.'?'.$encoded;
    }

    /**
     * Accepts `vms`, `/vms`, or a raw href such as `/rest/v0/vms/<uuid>` — the
     * last form is what lets models follow their own `href` fields.
     */
    protected function normalisePath(string $path): string
    {
        $path = ltrim(trim($path), '/');

        $prefix = ltrim(self::API_PREFIX, '/').'/';

        if (str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix));
        }

        return $path;
    }

    protected function normaliseBaseUrl(string $baseUrl): string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');

        if (str_ends_with($baseUrl, self::API_PREFIX)) {
            return $baseUrl;
        }

        return $baseUrl.self::API_PREFIX;
    }

    /**
     * XO wants `fields=a,b,c` (not `fields[]=a&fields[]=b`) and bare `true` for
     * boolean flags, so the default http_build_query behaviour is wrong here.
     */
    protected function buildQuery(array $query): string
    {
        $parts = [];

        foreach ($query as $key => $value) {
            if ($value === null || $value === false || $value === []) {
                continue;
            }

            $parts[$key] = match (true) {
                $value === true => 'true',
                is_array($value) => implode(',', $value),
                default => (string) $value,
            };
        }

        return http_build_query($parts, '', '&', PHP_QUERY_RFC3986);
    }

    // ---------------------------------------------------------------------
    // Accessors
    // ---------------------------------------------------------------------

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function httpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    /**
     * Clone the client with a different transport — used by Task::wait() to
     * long-poll with a longer timeout without disturbing the shared client.
     */
    public function withHttpClient(ClientInterface $httpClient): static
    {
        $clone = clone $this;
        $clone->httpClient = $httpClient;

        return $clone;
    }

    /** @param (\Closure(int): ClientInterface)|null $factory */
    public function withLongPollClientFactory(?\Closure $factory): static
    {
        $clone = clone $this;
        $clone->longPollClientFactory = $factory;

        return $clone;
    }

    /**
     * A client suited to holding a `?wait=true` request open for $timeout
     * seconds. Falls back to the current transport when no factory is
     * configured — for instance when a PSR-18 client was injected by hand.
     */
    public function forLongPolling(int $timeout): static
    {
        if ($this->longPollClientFactory === null) {
            return $this;
        }

        return $this->withHttpClient(($this->longPollClientFactory)($timeout));
    }

    public function aclPrivileges(): AclPrivileges
    {
        return new AclPrivileges($this);
    }

    public function alarms(): Alarms
    {
        return new Alarms($this);
    }

    public function messages(): Messages
    {
        return new Messages($this);
    }

    public function backupArchives(): BackupArchives
    {
        return new BackupArchives($this);
    }

    public function backupJobs(): BackupJobs
    {
        return new BackupJobs($this);
    }

    public function backupLogs(): BackupLogs
    {
        return new BackupLogs($this);
    }

    public function backupRepositories(): BackupRepositories
    {
        return new BackupRepositories($this);
    }

    public function restoreLogs(): RestoreLogs
    {
        return new RestoreLogs($this);
    }

    public function schedules(): Schedules
    {
        return new Schedules($this);
    }

    public function servers(): Servers
    {
        return new Servers($this);
    }

    public function pbds(): PhysicalBlockDevices
    {
        return new PhysicalBlockDevices($this);
    }

    public function pcis(): PciDevices
    {
        return new PciDevices($this);
    }

    public function pgpus(): PhysicalGpus
    {
        return new PhysicalGpus($this);
    }

    public function pifs(): PhysicalInterfaces
    {
        return new PhysicalInterfaces($this);
    }

    public function proxies(): Proxies
    {
        return new Proxies($this);
    }

    public function sms(): StorageManagers
    {
        return new StorageManagers($this);
    }

    public function vbds(): VirtualBlockDevices
    {
        return new VirtualBlockDevices($this);
    }

    public function vifs(): VirtualInterfaces
    {
        return new VirtualInterfaces($this);
    }

    public function vdiSnapshots(): VirtualDiskSnapshots
    {
        return new VirtualDiskSnapshots($this);
    }

    public function vmSnapshots(): VirtualMachineSnapshots
    {
        return new VirtualMachineSnapshots($this);
    }

    public function vmTemplates(): VirtualMachineTemplates
    {
        return new VirtualMachineTemplates($this);
    }

    public function vmControllers(): VirtualMachineControllers
    {
        return new VirtualMachineControllers($this);
    }
}
