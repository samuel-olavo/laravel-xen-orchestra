# Laravel Xen Orchestra

Laravel integration for the [Xen Orchestra](https://xen-orchestra.com) REST API: fluent access to virtual machines, hosts, pools, storage repositories and asynchronous tasks.

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

Built and tested against `@xen-orchestra/rest-api` **0.21.1**. The API is still versioned `/v0` and its authors reserve the right to change it, so pin a version you have tested.

## Installation

```bash
composer require samuelolavo/laravel-xen-orchestra
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

- Reads across VMs, hosts, pools, SRs, networks, tasks, snapshots, templates
- `fields`, `filter`, `limit`, `ndjson`
- The full VM power lifecycle, plus snapshots
- Pool-level actions: rolling update, rolling reboot, emergency shutdown, VM and network creation
- Tags, alarms, messages, per-object tasks
- Stats and dashboard endpoints
- Asynchronous task handling with server-side long-polling
- A generic escape hatch for everything else

**Not supported, because the REST API does not offer it**

- **Editing a VM.** There is no `PATCH /vms/<id>`. Renaming a VM or changing its CPU/RAM still requires the older JSON-RPC API.
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

Traps worth knowing before you spend an afternoon on them:

- `memory`, `name_description` and `auto_poweron` are **rejected** on creation — "excess property and therefore is not allowed" — even though they appear on VMs you read back. Set them afterwards.
- Property naming differs between GET responses and this payload (snake_case vs camelCase). A field you can read is not necessarily a field you can write under the same name.
- The authoritative field list for your version is the `create_vm` entry in your own appliance's `/rest/v0/docs/swagger.json`, not the GET response shape.

**Not supported yet, planned**

- Binary import/export of VMs and VDIs (XVA/VHD streaming)
- The `/events` SSE stream and its subscriptions
- Users, groups and server administration endpoints

## Architecture

The core has no dependency on Laravel. `Client/`, `Resources/`, `Models/` and `Concerns/` take a PSR-18 HTTP client and PSR-17 factories and nothing else; everything framework-specific lives under `Laravel/`.

That is primarily a testability decision rather than a portability one — injecting the transport is what lets the whole package be tested with plain PHPUnit and no application boot. It does mean the core will run outside Laravel, but that is a side effect and not yet a supported, tested configuration.

## Contributing

Issues and pull requests are welcome, particularly:

- Field names or response shapes that differ from what the models expect. These were inferred from a live instance and the OpenAPI page, and are the most likely thing to need correcting across XO versions.
- Endpoints you need that are not modelled yet.

## License

MIT. See [LICENSE](LICENSE).

This package is an independent client and is not affiliated with or endorsed by Vates SAS.
