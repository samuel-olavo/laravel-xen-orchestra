<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests\Unit;

use SamuelOlavo\XenOrchestra\Client\FilterBuilder;
use SamuelOlavo\XenOrchestra\Tests\TestCase;

class ResourceQueryTest extends TestCase
{
    public function test_a_raw_filter_string_passes_through_untouched(): void
    {
        $query = $this->client()->vms()->filter('power_state:Running !tags:test')->query();

        $this->assertSame('power_state:Running !tags:test', $query['filter']);
    }

    public function test_the_callback_form_produces_the_same_expression(): void
    {
        $query = $this->client()->vms()
            ->filter(fn (FilterBuilder $f) => $f->where('power_state', 'Running')->whereNot('tags', 'test'))
            ->query();

        $this->assertSame('power_state:Running !tags:test', $query['filter']);
    }

    public function test_where_ands_onto_an_existing_filter(): void
    {
        $query = $this->client()->vms()->filter('a:1')->where('b', 2)->query();

        $this->assertSame('a:1 b:2', $query['filter']);
    }

    public function test_scopes_map_to_filters(): void
    {
        $this->assertSame(
            'power_state:Running',
            $this->client()->vms()->running()->query()['filter'],
        );
    }

    /** Reusing a resource handle must not carry constraints over. */
    public function test_resources_are_immutable(): void
    {
        $vms = $this->client()->vms();
        $vms->running()->limit(5);

        $this->assertSame([], $vms->query());
    }

    public function test_it_can_report_the_url_it_would_request(): void
    {
        $this->assertSame(
            'https://xo.example.com/rest/v0/vms?fields=name_label&limit=2',
            $this->client()->vms()->fields(['name_label'])->limit(2)->toUrl(),
        );
    }

    public function test_snapshots_and_templates_switch_collection(): void
    {
        $this->assertStringContainsString('/vm-snapshots', $this->client()->vms()->snapshots()->toUrl());
        $this->assertStringContainsString('/vm-templates', $this->client()->vms()->templates()->toUrl());
    }
}
