<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests\Unit;

use SamuelOlavo\XenOrchestra\Client\FilterBuilder;
use SamuelOlavo\XenOrchestra\Tests\TestCase;

/**
 * These tests are really documentation of XO's complex-matcher syntax, and the
 * reason `filter()` takes a string rather than a field/value pair: every case
 * below except the first is inexpressible in a two-argument signature.
 */
class FilterBuilderTest extends TestCase
{
    public function test_simple_equality(): void
    {
        $this->assertSame(
            'power_state:Running',
            FilterBuilder::make()->where('power_state', 'Running')->toString(),
        );
    }

    public function test_terms_are_anded_with_a_space(): void
    {
        $this->assertSame(
            'power_state:Running tags:prod',
            FilterBuilder::make()->where('power_state', 'Running')->where('tags', 'prod')->toString(),
        );
    }

    public function test_negation(): void
    {
        $this->assertSame(
            '!power_state:Halted',
            FilterBuilder::make()->whereNot('power_state', 'Halted')->toString(),
        );
    }

    /** Matches the `body:name:physical_utilisation` example XO ships. */
    public function test_nested_property_paths(): void
    {
        $this->assertSame(
            'body:name:physical_utilisation',
            FilterBuilder::make()->where(['body', 'name'], 'physical_utilisation')->toString(),
        );
    }

    public function test_values_with_spaces_are_quoted(): void
    {
        $this->assertSame(
            'name_label:"web server 01"',
            FilterBuilder::make()->where('name_label', 'web server 01')->toString(),
        );
    }

    public function test_regular_expressions(): void
    {
        $this->assertSame(
            'name_label:/^prod/',
            FilterBuilder::make()->whereMatches('name_label', '^prod')->toString(),
        );
    }

    public function test_alternation(): void
    {
        $this->assertSame(
            '|(power_state:Running power_state:Paused)',
            FilterBuilder::make()->whereIn('power_state', ['Running', 'Paused'])->toString(),
        );
    }

    public function test_it_is_immutable(): void
    {
        $base = FilterBuilder::make()->where('a', 1);
        $base->where('b', 2);

        $this->assertSame('a:1', $base->toString());
    }
}
