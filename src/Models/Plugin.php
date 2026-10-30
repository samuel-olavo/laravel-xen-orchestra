<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Concerns\RunsActions;
use SamuelOlavo\XenOrchestra\Concerns\UpdatesObject;

class Plugin extends Model
{
    use RunsActions;
    use UpdatesObject;

    public static function endpoint(): string
    {
        return 'plugins';
    }

    /** Replace configuration using the plugin's configuration schema. */
    public function configure(array $configuration): static
    {
        $this->client->put($this->path('configuration'), $configuration);

        return $this;
    }

    public function deleteConfiguration(): static
    {
        $this->client->delete($this->path('configuration'));

        return $this;
    }

    public function load(bool $sync = false): Task
    {
        return $this->action('load', sync: $sync);
    }

    public function unload(bool $sync = false): Task
    {
        return $this->action('unload', sync: $sync);
    }

    /** Data must match the plugin's test schema; null omits the data field. */
    public function test(?array $data = null, bool $sync = false): Task
    {
        return $this->action('test', $data === null ? [] : ['data' => (object) $data], $sync);
    }
}
