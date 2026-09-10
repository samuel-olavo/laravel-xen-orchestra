<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests\Unit;

use SamuelOlavo\XenOrchestra\Tests\TestCase;

class UrlBuildingTest extends TestCase
{
    public function test_it_appends_the_api_prefix_to_a_bare_base_url(): void
    {
        $this->assertSame(
            'https://xo.example.com/rest/v0/vms',
            $this->client()->uriFor('vms'),
        );
    }

    public function test_it_does_not_double_the_prefix(): void
    {
        $this->assertSame(
            'https://xo.example.com/rest/v0/vms',
            $this->client('https://xo.example.com/rest/v0')->uriFor('vms'),
        );
    }

    public function test_it_tolerates_a_trailing_slash(): void
    {
        $this->assertSame(
            'https://xo.example.com/rest/v0/vms',
            $this->client('https://xo.example.com/')->uriFor('vms'),
        );
    }

    /**
     * Accepting an absolute href verbatim is what allows a model to follow its
     * own `href` without the package holding a map of routes.
     */
    public function test_it_accepts_a_raw_href_as_a_path(): void
    {
        $this->assertSame(
            'https://xo.example.com/rest/v0/srs/8aa2fb4a',
            $this->client()->uriFor('/rest/v0/srs/8aa2fb4a'),
        );
    }

    public function test_it_joins_fields_with_commas(): void
    {
        $this->assertSame(
            'https://xo.example.com/rest/v0/vms?fields=name_label%2Cpower_state',
            $this->client()->uriFor('vms', ['fields' => ['name_label', 'power_state']]),
        );
    }

    public function test_it_renders_boolean_flags_as_bare_true(): void
    {
        $this->assertSame(
            'https://xo.example.com/rest/v0/tasks/abc?wait=true',
            $this->client()->uriFor('tasks/abc', ['wait' => true]),
        );
    }

    public function test_it_drops_empty_parameters(): void
    {
        $this->assertSame(
            'https://xo.example.com/rest/v0/vms',
            $this->client()->uriFor('vms', ['limit' => null, 'ndjson' => false, 'fields' => []]),
        );
    }

    /**
     * The single most common first-attempt mistake against this API.
     */
    public function test_it_sends_the_token_as_a_cookie_not_a_bearer_header(): void
    {
        $this->fake->stub('ping', ['ok' => true]);

        $this->client()->ping();

        $request = $this->fake->lastRequest();

        $this->assertSame(['authenticationToken=secret-token'], $request->getHeader('Cookie'));
        $this->assertSame([], $request->getHeader('Authorization'));
    }
}
