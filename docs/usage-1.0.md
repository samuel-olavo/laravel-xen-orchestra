# Using version 1.0.0

All examples use `SamuelOlavo\XenOrchestra\Laravel\Facades\Xo` and API 0.39.0 field names. XO validates payloads and enforces permissions. A helper's availability does not grant the corresponding privilege.

## Hosts, storage and networking

```php
$host = Xo::hosts()->find($hostId);
$host->scanPifs()->wait();
$host->restartToolstack()->wait();
$host->reboot()->wait();
$inventory = $host->ipmi(); // ipmi-sensors plugin required
$host->logs()->saveTo('/absolute/path/host-logs.tgz');

$sr = Xo::srs()->find($srId);
$sr->scan()->wait();
$sr->reclaimSpace()->wait();

$vif = Xo::vifs()->create(['vmId' => $vmId, 'networkId' => $networkId]);
$vif->update(['allowedIpv4Addresses' => ['192.0.2.10'], 'lockingMode' => 'locked']);
$vif->connect()->wait();

Xo::pbds()->find($pbdId)->plug()->wait();
Xo::vdis()->find($diskId)->migrate(['srId' => $destinationSr])->wait();
```

Creation methods return partial models (`{id}`), not tasks, unless the endpoint is explicitly an asynchronous action. Use `fetch()` to retrieve the full created object. Updates do not optimistically alter local attributes; refresh explicitly. Disks cannot be shrunk via the VDI update endpoint.

## Backups and servers

```php
$repository = Xo::backupRepositories()->find($repositoryId);
$repository->update(['name' => 'Offsite', 'enabled' => true]);
$health = $repository->health();
$benchmark = $repository->benchmark()->wait()->result();
Xo::schedules()->find($scheduleId)->run()->wait();
Xo::backupJobs()->fields(['id', 'name', 'type'])->get();
Xo::backupArchives()->fields(['id'])->get();
Xo::backupLogs()->fields(['id'])->get();
Xo::restoreLogs()->fields(['id'])->get();

$server = Xo::servers()->create([
    'host' => '192.0.2.20', 'username' => 'root', 'password' => $password,
]);
$server->connect()->wait();
$server->disconnect()->wait();
```

Backup job and schedule creation/editing are not exposed by this revision's REST API. Schedule execution and repository administration are supported. `forget()` on a repository can be rejected by XO when jobs still reference it.

## Users and permissions

```php
$group = Xo::groups()->create(['name' => 'Operators']);
$user = Xo::users()->create(['name' => 'operator', 'password' => $password]);
$group->addUser($user->id());
$role = Xo::aclRoles()->create(['name' => 'VM operators']);
$role->addGroup($group->id());
$role->privileges(['id']);
// Xo::aclPrivileges()->create($privilegePayload) uses XO's privilege schema.

$me = Xo::users()->me();
$tokenResponse = $me->createAuthenticationToken(['description' => 'automation', 'expiresIn' => '1 day']);
$tokens = $me->authenticationTokens();
```

The token response contains credentials; store it appropriately. `users()->me()` resolves the current user's actual ID before calling the token endpoint. Synchronized users/groups and self-permission changes remain subject to server restrictions.

## SDN plugin

```php
$network = Xo::networks()->find($networkId);
$rule = ['allow' => true, 'direction' => 'to', 'ipRange' => '192.0.2.0/24', 'protocol' => 'tcp', 'port' => 443];
$network->addTrafficRule($rule)->wait();
$network->updateTrafficRule(['oldRule' => $rule, 'newRule' => ['port' => null]])->wait();
// The same helpers are available on a VirtualInterface.
```

These methods require the sdn-controller plugin. `oldRule` identifies the existing rule; null in a patch removes a field. Payloads are passed through unchanged.

## Binary transfers

```php
use GuzzleHttp\Psr7\Utils;

Xo::vm($vmId)->export('xva', ['compress' => 'zstd'])->saveTo('/absolute/path/vm.xva');
Xo::vdis()->find($diskId)->export('vhd')->saveTo('/absolute/path/disk.vhd');

$source = Utils::streamFor(fopen('/absolute/path/vm.xva', 'rb'));
try {
    $vm = Xo::pools()->find($poolId)->importVm($source, ['sr' => $srId]);
} finally {
    $source->close();
}
```

`$sr->importVdi($source, $query)` imports a new disk; query options include `name_label`, `name_description` and `raw`. `$disk->importContent($source, 'vhd')` replaces disk content (`raw` is also accepted). Imports return a partial model or no content, not an asynchronous task.

Uploads consume the stream from its current position and leave closing it to the caller. `saveTo()` consumes and closes the response stream, creates a new file exclusively, and removes that partial file if writing fails. It never overwrites an existing file. If you read `Response::body()` first, you consume/buffer the body instead of streaming it to disk.

The default transport streams downloads and events. A custom PSR-18 client must implement `StreamingClientInterface` for SSE. Binary downloads with other PSR-18 clients may be buffered by that client. Configure suitable connection/read timeouts for the chosen transport; long streams have no automatic reconnect/resume.

## Events

```php
$events = Xo::events();
$stream = $events->open();
try {
    foreach ($stream as $event) {
        if ($event->event === 'init') {
            $connectionId = $event->json();
            $events->subscribe($connectionId, 'VM', ['id', 'name_label', 'power_state']);
        } elseif ($event->event === 'update') {
            $updatedObject = $event->json();
            // Process the change; break when your consumer should stop.
        }
    }
} finally {
    $stream->close();
}
```

Subscriptions use XO collection types, such as `VM`, not REST paths such as `vms`. `unsubscribe($connectionId, 'VM')` removes one subscription. Each stream can be consumed once. A disconnect ends the stream or raises a read error; open a new connection and subscribe again explicitly. Raw event data remains available in `$event->data`.

## Other endpoints and migration

`Xo::openApi()`, `Xo::guiRoutes()` and `Xo::mcpStatus()` expose the corresponding JSON endpoints. `markdown()` returns a raw Markdown collection response. Shared `alarms()`, `messages()` and `tasks()` relations accept optional query parameters after `$fields`.

Two changes from 0.x are intentional: `Task::abort()` returns the cancellation Task, and VM snapshot/template/controller scopes now return dedicated models without unsupported VM operations. Empty JSON requests now send `{}`. See [CHANGELOG](../CHANGELOG.md) before upgrading.
