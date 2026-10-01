# XO 6.9 / REST API 0.40.2

Version 1.1.0 adds to the [existing usage guide](usage-1.0.md). Existing calls remain compatible; new endpoints and options require the corresponding server support.

## Live backup disks

These operations require an XO administrator. Mounting exposes one backup disk as a read-only SR on the selected host; it does not restore a complete VM.

```php
$archive = Xo::backupArchives()->find($archiveId);
$mount = $archive->mountLiveDisk([
    'diskId' => $diskId,
    'hostId' => $hostId,
], sync: true)->result();

// Use the mount ID returned by XO, not the original disk ID.
$archive->unmountLiveDisk($mount['id'])->wait();
```

Archive IDs containing slashes are encoded as a single path parameter. XO manages iSCSI connectivity; its advertised address must be reachable from the host.

## Reclaim backup repository space

```php
$task = Xo::backupRepositories()->find($repositoryId)->reclaimSpace([
    'vmUuid' => $vmUuid,
    'merge' => true,
    'remove' => true,
]);
$result = $task->wait()->result();
```

Omit `vmUuid` to target the repository. The `merge` and `remove` options control server-side cleanup; choose them deliberately. Omitted options retain XO defaults.

## Rolling pool updates

```php
$pool = Xo::pools()->find($poolId);
$pool->rollingUpdate(
    shutdownPinnedVms: true,
    bypassBackupCheck: false,
    acceptCurrentStateAsBaseline: false,
);
$pool->rollingReboot(bypassBackupCheck: false);
$recovery = $pool->rollingUpdateRecovery();

// After reviewing the pool and restoring any outstanding items:
$pool->finalizeRollingUpdate(force: false)->wait();
```

The original positional `sync` and `shutdownPinnedVms` arguments are unchanged. Unspecified options are omitted, and explicit `false` is preserved. Recovery returns the server's record as an array; XO returns 404 when no record exists. `force: true` abandons unresolved recovery items and does not restore them. The REST schema at this revision does not expose the JSON-RPC option to skip returning VMs to their original hosts.

## QCOW2 exports

```php
Xo::vdis()->find($diskId)->export('qcow2')->saveTo('/backups/disk.qcow2');
Xo::vdiSnapshots()->find($snapshotId)->export('qcow2')->saveTo('/backups/snapshot.qcow2');
```

Existing streaming helpers support this format without buffering the whole disk. Response headers, including `Content-Length` when supplied by XO, remain available. `saveTo()` refuses to overwrite an existing file.

## Additional SSE collections

The existing subscription helper also accepts `user`, `group`, `acl-privilege`, `acl-role`, `proxy`, `server`, `backup-repository`, `backup-job` and `schedule`:

```php
$subscription = Xo::events()->subscribe($connectionId, 'backup-job', ['id', 'name']);
```

Use the connection ID from an open stream's `init` event. Keep consuming that stream, and reconnect/resubscribe explicitly after disconnection.
