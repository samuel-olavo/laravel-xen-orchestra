<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Laravel\Commands;

use Illuminate\Console\Command;
use SamuelOlavo\XenOrchestra\Client\XenOrchestraClient;
use SamuelOlavo\XenOrchestra\Exceptions\XenOrchestraException;
use SamuelOlavo\XenOrchestra\Models\VirtualMachine;

class ListVmsCommand extends Command
{
    protected $signature = 'xo:vms
        {--running : Only running VMs}
        {--halted : Only halted VMs}
        {--tag= : Only VMs carrying this tag}
        {--filter= : Raw Xen Orchestra filter expression}
        {--limit=50 : Maximum number of VMs to list}';

    protected $description = 'List virtual machines from Xen Orchestra';

    public function handle(XenOrchestraClient $client): int
    {
        $query = $client->vms()
            ->fields(['name_label', 'power_state', 'CPUs', 'memory'])
            ->limit((int) $this->option('limit'));

        if ($this->option('running')) {
            $query = $query->running();
        }

        if ($this->option('halted')) {
            $query = $query->halted();
        }

        if ($tag = $this->option('tag')) {
            $query = $query->tagged((string) $tag);
        }

        if ($filter = $this->option('filter')) {
            $query = $query->filter((string) $filter);
        }

        try {
            $vms = $query->get();
        } catch (XenOrchestraException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($vms->isEmpty()) {
            $this->components->warn('No virtual machines matched.');

            return self::SUCCESS;
        }

        $this->table(
            ['Name', 'State', 'vCPUs', 'RAM'],
            array_map(static function (VirtualMachine $vm): array {
                $cpus = $vm->get('CPUs');
                $memory = $vm->get('memory');

                return [
                    $vm->name() ?? $vm->id() ?? '—',
                    $vm->powerState() ?? '—',
                    is_array($cpus) ? ($cpus['number'] ?? '—') : ($cpus ?? '—'),
                    self::formatBytes(is_array($memory) ? ($memory['size'] ?? null) : $memory),
                ];
            }, $vms->all()),
        );

        $this->newLine();
        $this->line(sprintf('  <info>%d</info> virtual machine(s).', $vms->count()));

        return self::SUCCESS;
    }

    protected static function formatBytes(mixed $bytes): string
    {
        if (! is_numeric($bytes)) {
            return '—';
        }

        $bytes = (float) $bytes;
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 1).' '.$units[$index];
    }
}
