# Laravel Xen Orchestra 1.1.0

Laravel client for the Xen Orchestra REST API: infrastructure, backups, identity/RBAC administration, asynchronous tasks, binary transfers and events.

```php
$vms = Xo::vms()
    ->fields(['name_label', 'power_state'])
    ->running()
    ->get();

Xo::vm($uuid)->start()->wait();
```

## Why

Laravel already ships an excellent HTTP client, so a package that only saves you typing `Http::get()` would not be worth installing. This one exists because the XO REST API has a handful of behaviours you otherwise have to learn, and re-implement, in every project.

Without it:

```php
$response = Http::withOptions(['verify' => false])
    ->withHeaders(['Accept' => 'application/json'])
    ->withCookies(
        ['authenticationToken' => config('services.xo.token')],
        parse_url(config('services.xo.url'), PHP_URL_HOST),
    )
    ->get(config('services.xo.url').'/rest/v0/vms', [
        'fields' => 'name_label,power_state',
        'limit' => 100,
    ]);

$vms = $response->throw()->json();
```

…and you still have to know that the token is a **cookie** rather than a Bearer header, that a collection requested without `fields` returns bare URLs instead of objects, that `filter` uses XO's own expression syntax, and that starting a VM hands you a task reference you then have to resolve.

With it:

```php
$vms = Xo::vms()->fields(['name_label', 'power_state'])->limit(100)->get();
```

## Requirements

- PHP 8.2+ (8.3+ for Laravel 13)
- Laravel 12 or 13
- A Xen Orchestra appliance with the REST API enabled

Laravel 11 is not supported: its security support ended on 12 March 2026, and Composer refuses to resolve `illuminate/*` 11.x against the advisory database.

Version **1.1.0** targets `@xen-orchestra/rest-api` **0.40.2**, shipped with XO **6.9.0** ([reference commit](https://github.com/vatesfr/xen-orchestra/commit/a2bd181d3528a61a45bbd4fe99686875fe8eebf8)). See the [coverage table](docs/rest-api-0.40.md), [1.1 additions](docs/usage-1.1.md), [base usage guide](docs/usage-1.0.md) and [migration notes](CHANGELOG.md). Contracts are tested with a fake transport; live-appliance integration has not been verified.


## Installation

```bash
composer require samuel-olavo/laravel-xen-orchestra:^1.0
```

The service provider is auto-discovered. Publish the config if you want to edit it:

```bash
php artisan vendor:publish --tag=xen-orchestra-config
```

## Configuration

```dotenv
XO_URL=https://xo.example.com
XO_TOKEN=your-authentication-token
XO_VERIFY_SSL=true
XO_TIMEOUT=30
XO_WAIT_TIMEOUT=300
```

Create a token in the XO web interface under your user's settings, or via `POST /rest/v0/users/<id>/authentication_tokens`.

**On `XO_VERIFY_SSL`.** Internal appliances very often use a self-signed certificate, and setting this to `false` is the quickest way past that — it also removes any protection against someone impersonating your appliance. Prefer pinning a CA bundle instead:

```php
'verify_ssl' => storage_path('certs/xo-ca.pem'),
```

Verify everything works:

```bash
php artisan xo:ping
```

## Usage

### Listing

```php
use SamuelOlavo\XenOrchestra\Laravel\Facades\Xo;

Xo::vms()->get();
Xo::hosts()->get();
Xo::pools()->get();
Xo::storageRepositories()->get();
Xo::networks()->get();
Xo::tasks()->get();
```

### `fields` is not optional in practice

This is the behaviour that catches everyone. A collection requested **without** `fields` comes back as a list of URLs, not objects:

```php
$vms = Xo::vms()->get();

$vms->first()->isPartial();   // true
$vms->first()->name();        // null — nothing was returned but the href
$vms->first()->fetch();       // one request per VM. Avoid.
```

Ask for what you need up front:

```php
$vms = Xo::vms()->fields(['name_label', 'power_state', 'CPUs', 'memory'])->get();

$vms->first()->name();        // 'web-01'
```

There is a shortcut for the common case:

```php
Xo::vms()->summary();   // name, power state, CPUs, memory, tags
```

### Filtering

XO's `filter` is a single expression in its own [complex-matcher](https://docs.xen-orchestra.com/xo5/restapi) syntax. Raw strings are the primary interface, because they are the only form that can express everything the API supports:

```php
Xo::vms()->filter('power_state:Running !tags:test')->get();
Xo::vms()->filter('name_label:/^prod-/')->get();
Xo::vms()->filter('|(power_state:Running power_state:Paused)')->get();
```

A builder is available for the simple cases, and produces exactly the same strings:

```php
Xo::vms()
    ->filter(fn ($f) => $f
        ->where('power_state', 'Running')
        ->whereNot('tags', 'test'))
    ->get();

// or, inline
Xo::vms()->where('power_state', 'Running')->whereNot('tags', 'test')->get();
```

Plus a few named scopes:

```php
Xo::vms()->running()->get();
Xo::vms()->halted()->get();
Xo::vms()->tagged('production')->get();
Xo::vms()->snapshots()->get();
Xo::vms()->templates()->get();
```

`limit` is the only other lever — the API has no offset and no cursor, so there is no pagination to model.

### Actions and tasks

Every action returns a `Task`. There are three ways to handle it, and they are genuinely different:

```php
// 1. Fire and forget — XO queues the work, you get a reference back immediately
$task = Xo::vm($uuid)->start();

// 2. Block until done — wait() long-polls XO's task endpoint
Xo::vm($uuid)->start()->wait();

// 3. Let XO block instead — ?sync=true holds the HTTP request open
Xo::vm($uuid)->start(sync: true);
```

`wait()` uses `GET /tasks/<id>?wait=true`, which blocks server-side and answers the instant the task finishes. It is not a polling loop: a task that completes costs exactly one request, with no polling-interval lag.

```php
$task = Xo::vm($uuid)->snapshot('before-upgrade');

$task->wait(timeout: 300);

if ($task->successful()) {
    // $task->result() holds the new snapshot's id
}
```

By default `wait()` throws `TaskFailedException` on failure. When failure is an expected outcome, branch on it instead:

```php
$task = Xo::vm($uuid)->shutdown()->wait(throw: false);

if ($task->failed()) {
    Log::warning('Shutdown failed', ['reason' => $task->errorMessage()]);
}
```

Available VM actions:

```php
$vm->start();
$vm->shutdown();              // clean_shutdown — needs guest tools
$vm->shutdown(force: true);   // hard_shutdown
$vm->reboot();                // clean_reboot
$vm->reboot(force: true);     // hard_reboot
$vm->suspend();
$vm->resume();
$vm->pause();
$vm->unpause();
$vm->snapshot('label');
$vm->delete();
```

And on pools:

```php
$pool->rollingUpdate();
$pool->rollingReboot();
$pool->emergencyShutdown();
$pool->createVm([...]);
$pool->createNetwork([...]);
```

### XO 6.7 / REST API 0.37 additions

```php
// Update fields using the API's spelling (VM updates use camelCase).
$vm->update(['nameLabel' => 'web-02', 'nameDescription' => 'Production']);
$vm->fetch(); // Explicit refresh; update() does not change cached attributes.

$vm->cloneVm(['name_label' => 'web-copy', 'fast' => true])->wait();
$vm->migrate(['hostId' => $destinationHost])->wait();
$vm->revertSnapshot($snapshotId, snapshotBefore: true)->wait();

// VDI updates use snake_case; size is in bytes and cannot shrink a disk.
Xo::vdis()->find($diskId)->update(['name_label' => 'data', 'size' => 10737418240]);

$sr = Xo::srs()->create([
    'hostId' => $hostId,
    'SR_type' => 'nfs',
    'name_label' => 'shared-data',
    'device_config' => ['server' => '10.0.0.2', 'serverpath' => '/data'],
]); // Partial StorageRepository, not a Task; use $sr->fetch() for full data.

$host->disable(['evacuate' => true, 'autoEnable' => true])->wait();
$host->enable()->wait();
$pool->rollingUpdate(shutdownPinnedVms: true)->wait();
$pool->rollingReboot(shutdownPinnedVms: true)->wait();
$pool->createVm([
    'template' => $templateId,
    'name_label' => 'web-03',
    'high_availability' => 'restart',
])->wait();

$pool->createInternalNetwork(['name' => 'private'])->wait();
$pool->createBondedNetwork([
    'name' => 'bond', 'pifIds' => [$pif1, $pif2], 'bondMode' => 'active-backup',
])->wait();
$pool->managementReconfigure(['network' => $networkId])->wait();

$role = Xo::aclRoles()->find($roleId);
$role->users(['id', 'email']);
$role->groups(['id', 'name'], ['limit' => 10]);
Xo::groups()->find($groupId)->aclRoles(['id', 'name']);
Xo::users()->fields(['id', 'email'])->get();
```

`shutdownPinnedVms` allows XO to stop VMs tied to host devices and restart them after maintenance. Omit it to keep the existing behavior. The original positional `sync` argument remains unchanged.

VM/VDI updates return the same model without an implicit GET. XO applies fields sequentially, so an error may occur after earlier fields were applied; use `fetch()` to read the actual state. Payload validation and RBAC enforcement remain on XO.

Synchronous actions return a completed `Task`, with the operation's entire response in `result()` (including created object IDs). Calling `wait()` on it makes no additional request.

The [1.0 guide](docs/usage-1.0.md) covers transfers and subscriptions. The [1.1 guide](docs/usage-1.1.md) adds live backup disks, space reclamation, pool recovery, QCOW2 exports and additional SSE collections.

### Sub-resources

`alarms`, `messages`, `tasks` and `tags` are available on every object that supports them:

```php
$vm->alarms();
$vm->messages();
$vm->tasks();

$vm->tags();               // already-loaded data, no request
$vm->addTag('critical');
$vm->removeTag('staging');

$host->missingPatches();
$host->stats();
$pool->dashboard();
$sr->usagePercentage();
```

### Following references

Objects carry `href` values, including on nested references, so relations resolve without any hardcoded route map:

```php
$alarm = Xo::get('alarms')[0];

$reference = $model->reference('object');   // e.g. the SR the alarm is about
$reference->collection();                   // 'srs' — no request made
$sr = $reference->fetch();                  // StorageRepository, one request
```

Note that **reading a property never performs HTTP**. Anything that touches the network is a method with a verb in its name. This is deliberate: implicit lazy loading reads well in a README and produces invisible N+1 storms in production.

### Escape hatch

Nothing here should stop you reaching an endpoint this package does not model:

```php
Xo::get('pgpus');
Xo::get('vdis', ['fields' => 'name_label,size', 'limit' => 20]);
Xo::post('servers/'.$id.'/actions/connect');
Xo::delete('tasks');
```

## Artisan commands

```bash
php artisan xo:ping                       # connectivity and authentication
php artisan xo:vms                        # list VMs
php artisan xo:vms --running --limit=20
php artisan xo:vms --tag=production
php artisan xo:vms --filter='power_state:Running !tags:test'
```

`xo:ping` distinguishes the three ways a connection fails — unreachable appliance, rejected certificate, rejected token — because they look identical in a stack trace and need completely different fixes.

## Testing

`Xo::fake()` swaps the client for one answering from stubs. No HTTP interception, no global state:

```php
use SamuelOlavo\XenOrchestra\Laravel\Facades\Xo;

public function test_it_lists_running_vms(): void
{
    $fake = Xo::fake([
        'vms' => [
            ['id' => 'abc', 'name_label' => 'web-01', 'power_state' => 'Running'],
        ],
    ]);

    $this->get('/vms')->assertSee('web-01');

    $fake->assertSent('vms', 'GET')
         ->assertQuery('filter', 'power_state:Running');
}
```

Stub patterns support `*` wildcards, and repeated stubs for the same pattern are returned in order — which is how you test a task that is pending on the first read and finished on the second:

```php
$fake = Xo::fake();
$fake->stub('vms/abc/actions/start', '/rest/v0/tasks/t1');
$fake->stub('tasks/t1', ['id' => 't1', 'status' => 'pending']);
$fake->stub('tasks/t1', ['id' => 't1', 'status' => 'success']);
```

## What this supports, and what it does not

The REST API is read-heavy, and this package does not pretend otherwise. Being explicit about the boundary is more useful than a promise of "full Xen Orchestra management".

**Supported**

- Fluent reads across all 32 current controller collections (see the coverage table)
- `fields`, `filter`, `limit`, `ndjson`, raw Markdown output
- The full VM power lifecycle, snapshots, snapshot restoration, cloning, migration and partial updates
- VDI/VBD/VIF creation, updates where offered, removal, connection actions and disk migration
- Storage repository creation and maintenance
- Backup repository administration, health/benchmark operations, schedules and logs
- Host lifecycle, maintenance, interface discovery and IPMI inventory
- Users, groups, servers, ACL roles and privileges, memberships and authentication tokens
- Pool-level actions: rolling update, rolling reboot, emergency shutdown, VM and network creation
- Tags, alarms, messages, per-object tasks
- Stats and dashboard endpoints
- Asynchronous task handling with server-side long-polling
- Binary VM/VDI imports/exports, host logs, SSE events and subscriptions
- SDN traffic rules, OpenAPI/GUI metadata and MCP status
- Generic HTTP methods for deprecated aliases and installation-specific routes

**Not supported, because the REST API does not offer it**

- **Creating or editing backup jobs.** `/backup-jobs` and `/schedules` are read-only; schedules can only be triggered.
- **Xen Orchestra's Self Service feature.** Resource sets — the quota sandboxes that back XO's own self-service portal — have no REST collection. They are managed through the XO web UI and the JSON-RPC API (`resourceSet.*`). Users and groups *are* exposed, so the identity half can be automated; the quota half cannot.

### Creating VMs

`POST /pools/<id>/actions/create_vm` is supported, and the payload is passed through untouched on purpose — XO validates it strictly and the accepted field set has changed between versions.

```php
$task = Xo::pools()->find($poolId)->createVm([
    'template' => $templateUuid,
    'name_label' => 'web-02',
])->wait();
```

Creation payloads are passed unchanged to XO. In 0.37.0, `high_availability` sets the HA restart priority. Field names differ between creation, updates and GET responses; consult the `create_vm` entry in your appliance's `/rest/v0/docs/swagger.json` for the complete contract.

**Outside the package's scope**

- Deprecated aliases have modern replacements; use generic HTTP methods only when an older path is specifically required.
- JSON-RPC-only features and the MCP protocol are not implemented by this REST client.
- Plugin helpers require the corresponding server plugin. Custom external routes depend on the installation.

## Architecture

The core has no dependency on Laravel. It uses PSR-18/PSR-17 HTTP interfaces with a Guzzle transport and stream utilities; everything framework-specific lives under `Laravel/`.

The test suite exercises the core without an application boot. The default transport implements `StreamingClientInterface`; custom transports need that interface for SSE. Attribute access never makes HTTP requests.

## Contributing

Issues and pull requests are welcome, particularly:

- Field names or response shapes that differ from what the models expect. These were inferred from a live instance and the OpenAPI page, and are the most likely thing to need correcting across XO versions.
- Endpoints you need that are not modelled yet.

## License

MIT. See [LICENSE](LICENSE).

This package is an independent client and is not affiliated with or endorsed by Vates SAS.
