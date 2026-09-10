<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests\Feature;

use SamuelOlavo\XenOrchestra\Models\GenericObject;
use SamuelOlavo\XenOrchestra\Models\StorageRepository;
use SamuelOlavo\XenOrchestra\Models\VirtualMachine;
use SamuelOlavo\XenOrchestra\Tests\TestCase;

class HydrationTest extends TestCase
{
    public function test_a_collection_with_fields_hydrates_full_models(): void
    {
        $this->fake->stub('vms', [
            ['id' => 'abc', 'name_label' => 'web-01', 'power_state' => 'Running'],
            ['id' => 'def', 'name_label' => 'db-01', 'power_state' => 'Halted'],
        ]);

        $vms = $this->client()->vms()->fields(['name_label', 'power_state'])->get();

        $this->assertCount(2, $vms);
        $this->assertInstanceOf(VirtualMachine::class, $vms[0]);
        $this->assertSame('web-01', $vms[0]->name());
        $this->assertTrue($vms[0]->isRunning());
        $this->assertTrue($vms[1]->isHalted());
        $this->assertFalse($vms[0]->isPartial());
    }

    /**
     * The behaviour that surprises everyone: no `fields` means XO returns a
     * list of href strings rather than objects.
     */
    public function test_a_collection_without_fields_yields_partial_models(): void
    {
        $this->fake->stub('vms', [
            '/rest/v0/vms/0c98c71c-2f9c-d5c2-b9b6-2c8371730eab',
            '/rest/v0/vms/6a4cc401-6cba-0d41-3b02-b848c5017343',
        ]);

        $vms = $this->client()->vms()->get();

        $this->assertCount(2, $vms);
        $this->assertTrue($vms[0]->isPartial());
        $this->assertSame('0c98c71c-2f9c-d5c2-b9b6-2c8371730eab', $vms[0]->id());
        $this->assertNull($vms[0]->name());
    }

    public function test_a_partial_model_fills_itself_in_via_fetch(): void
    {
        $this->fake->stub('vms', ['/rest/v0/vms/abc']);
        $this->fake->stub('vms/abc', ['id' => 'abc', 'name_label' => 'web-01']);

        $vm = $this->client()->vms()->get()->first();

        $this->assertNull($vm->name());

        $vm->fetch();

        $this->assertSame('web-01', $vm->name());
        $this->assertFalse($vm->isPartial());
    }

    /**
     * The rule that keeps this package out of N+1 territory: only methods with
     * verbs touch the network.
     */
    public function test_property_access_never_triggers_a_request(): void
    {
        $this->fake->stub('vms', [['id' => 'abc', 'name_label' => 'web-01']]);

        $vm = $this->client()->vms()->fields(['name_label'])->get()->first();
        $before = $this->fake->requestCount();

        $this->assertSame('web-01', $vm->name_label);
        $this->assertNull($vm->does_not_exist);
        $this->assertSame($before, $this->fake->requestCount());
    }

    public function test_nested_hrefs_resolve_to_the_right_model_class(): void
    {
        $this->fake->stub('srs/8aa2fb4a', [
            'id' => '8aa2fb4a',
            'name_label' => 'Local storage',
            'size' => 1000,
            'physical_usage' => 250,
        ]);

        $client = $this->client();

        $alarm = new GenericObject([
            'id' => 'alarm-1',
            'object' => ['type' => 'SR', 'uuid' => '8aa2fb4a', 'href' => '/rest/v0/srs/8aa2fb4a'],
            'href' => '/rest/v0/alarms/alarm-1',
        ], $client);

        $reference = $alarm->reference('object');

        $this->assertNotNull($reference);
        $this->assertSame('srs', $reference->collection());

        $sr = $reference->fetch();

        $this->assertInstanceOf(StorageRepository::class, $sr);
        $this->assertSame('Local storage', $sr->name());
        $this->assertSame(25.0, $sr->usagePercentage());
    }

    public function test_ndjson_responses_parse_line_by_line(): void
    {
        $this->fake->stubRaw('vms', "{\"id\":\"a\"}\n{\"id\":\"b\"}\n");

        $vms = $this->client()->vms()->ndjson();

        $this->assertCount(2, $vms);
        $this->assertSame('b', $vms[1]->id());
    }

    public function test_the_escape_hatch_reaches_unmodelled_endpoints(): void
    {
        $this->fake->stub('pgpus', [['id' => 'gpu-1']]);

        $this->assertSame([['id' => 'gpu-1']], $this->client()->get('pgpus'));
    }
}
