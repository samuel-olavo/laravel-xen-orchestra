<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

/**
 * Maps an href back to the model class that represents it.
 *
 * Only the collections v1 models explicitly are listed; everything else
 * resolves to GenericObject, which still gives you attribute access and
 * `fetch()`. That is on purpose — an unmodelled collection should degrade to
 * something usable, not to an exception.
 */
final class ModelResolver
{
    /** @var array<string, class-string<Model>> */
    private const MAP = [
        'vm-controllers' => VirtualMachineController::class,
        'vdi-snapshots' => VirtualDiskSnapshot::class,
        'vifs' => VirtualInterface::class,
        'vbds' => VirtualBlockDevice::class,
        'sms' => StorageManager::class,
        'proxies' => Proxy::class,
        'pifs' => PhysicalInterface::class,
        'pgpus' => PhysicalGpu::class,
        'pcis' => PciDevice::class,
        'pbds' => PhysicalBlockDevice::class,
        'servers' => Server::class,
        'schedules' => Schedule::class,
        'restore-logs' => RestoreLog::class,
        'backup-repositories' => BackupRepository::class,
        'backup-logs' => BackupLog::class,
        'backup-jobs' => BackupJob::class,
        'backup-archives' => BackupArchive::class,
        'messages' => Message::class,
        'alarms' => Alarm::class,
        'acl-privileges' => AclPrivilege::class,
        'vms' => VirtualMachine::class,
        'vm-templates' => VirtualMachineTemplate::class,
        'vm-snapshots' => VirtualMachineSnapshot::class,
        'hosts' => Host::class,
        'pools' => Pool::class,
        'srs' => StorageRepository::class,
        'networks' => Network::class,
        'tasks' => Task::class,
        'vdis' => VirtualDisk::class,
        'acl-roles' => AclRole::class,
        'groups' => Group::class,
        'users' => User::class,

    ];

    /** @return class-string<Model> */
    public static function forHref(string $href): string
    {
        $collection = self::collectionFor($href);

        return self::MAP[$collection] ?? GenericObject::class;
    }

    /**
     * Pull the collection segment out of `/rest/v0/<collection>/<uuid>`.
     */
    public static function collectionFor(string $href): ?string
    {
        $path = parse_url($href, PHP_URL_PATH) ?: $href;
        $segments = array_values(array_filter(explode('/', $path), static fn ($s) => $s !== ''));

        // Drop the API prefix when the href is absolute.
        if (($segments[0] ?? null) === 'rest' && str_starts_with((string) ($segments[1] ?? ''), 'v')) {
            $segments = array_slice($segments, 2);
        }

        return $segments[0] ?? null;
    }

    /** @return array<string, class-string<Model>> */
    public static function map(): array
    {
        return self::MAP;
    }
}
