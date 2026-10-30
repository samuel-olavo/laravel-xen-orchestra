# XO 6.10 / REST API 0.41.1

Version 1.2.0 targets commit `b477167b41f2ac401e7d8c852c139566b9fe756e`. Existing calls remain compatible. New routes and fields require the corresponding server support.

## Plugins

```php
$plugins = Xo::plugins()->fields(['id', 'name', 'loaded'])->get();
$plugin = Xo::plugins()->find($pluginId);
$plugin->update(['autoload' => false]);
$plugin->configure($configuration);
$plugin->load()->wait();
$plugin->test($testData)->wait();
$plugin->unload()->wait();
$plugin->deleteConfiguration();
```

Configuration is sent directly as the request body; `test()` wraps its argument in `data`. Calling `test()` without arguments omits that field. Configuration and test data must match the schemas exposed by the individual plugin. XO enforces privileges, loaded state and whether the plugin supports unloading/testing. These helpers do not automatically unload a plugin or refresh its attributes; call `fetch()` explicitly when needed. Do not reuse obfuscated secret values from a GET response as configuration credentials.

## Discover storage from a host

```php
$host = Xo::hosts()->find($hostId);
$exports = $host->probeNfs(['server' => '192.0.2.10', 'nfsVersion' => '4'], sync: true)->result();
$zfs = $host->probeZfs(sync: true)->result();
$hba = $host->probeHba(sync: true)->result();
$iqns = $host->probeIscsiIqns(['targetIp' => '192.0.2.20'], sync: true)->result();
$luns = $host->probeIscsiLuns([
    'targetIp' => '192.0.2.20',
    'targetIqn' => $targetIqn,
], sync: true)->result();
```

All eight probes return a `Task`; use `sync: true` for immediate results or `wait()->result()` for asynchronous execution.

| Helper | Required fields | Optional fields |
| --- | --- | --- |
| `probeNfs()` | `server` | `nfsVersion` |
| `probeZfs()` / `probeHba()` | No body | — |
| `probeIscsiIqns()` | `targetIp` | `port`, `chapUser`, `chapPassword` |
| `probeIscsiLuns()` | `targetIp`, `targetIqn` | `port`, `chapUser`, `chapPassword` |
| `probeIscsiExists()` | `targetIp`, `targetIqn`, `scsiId` | `port`, `chapUser`, `chapPassword` |
| `probeHbaExists()` | `scsiId` | — |
| `probeNfsExists()` | `server`, `serverPath` | `nfsVersion` |

The `Exists` operations return existing SR UUIDs, not a boolean.

## Existing helpers with new server capabilities

- `VirtualDisk::export('vmdk')` and the snapshot equivalent stream VMDK downloads through the existing response helpers.
- `VirtualMachine::migrate()` accepts `bypassCpuCheck` in its attributes. Omit it or use `false` to retain the CPU check.
- Network creation accepts `insecureNbd`; this enables unencrypted NBD and should be chosen deliberately for the intended network.
- `aclRoles()->create()` accepts `privileges` when creating the role.
- VM creation accepts disk entries with `destroy: true` and `userdevice` to remove template disks.
- SDN traffic rule helpers preserve `priority`. When identifying an existing rule, include its priority in deletion parameters or the update's `oldRule` as required by XO.

## Events

The existing `events()->subscribe()` helper accepts four additional collection names: `plugin`, `backup-archive`, `authentication_token` and `backup-log` (singular). Token events are restricted by XO to the authenticated user's tokens. Backup archive changes from other sources can arrive after a delay. Keep consuming the open connection and reconnect/resubscribe explicitly after disconnection.

## Scope

Console clipboard support belongs to the XO interface and is not a new REST helper. Although the server introduces writable live restore, the pinned REST live-mount request still exposes only `diskId` and `hostId`; this package does not claim writable live restore support through that endpoint. Server-side backup fixes and compression changes are not reimplemented by this client.
