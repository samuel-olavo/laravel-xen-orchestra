<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests\Feature;

use SamuelOlavo\XenOrchestra\Exceptions\AuthenticationException;
use SamuelOlavo\XenOrchestra\Exceptions\NotFoundException;
use SamuelOlavo\XenOrchestra\Exceptions\RequestException;
use SamuelOlavo\XenOrchestra\Exceptions\XenOrchestraException;
use SamuelOlavo\XenOrchestra\Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    public function test_401_becomes_an_authentication_exception(): void
    {
        $this->fake->stubError('vms', 401, ['message' => 'authentication required']);

        try {
            $this->client()->vms()->get();
            $this->fail('Expected an AuthenticationException.');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->status);
            $this->assertStringContainsString('authentication required', $e->getMessage());
        }
    }

    public function test_404_becomes_a_not_found_exception(): void
    {
        $this->fake->stubError('vms/nope', 404);

        $this->expectException(NotFoundException::class);

        $this->client()->vms()->find('nope');
    }

    public function test_a_400_keeps_the_decoded_body_for_inspection(): void
    {
        $this->fake->stubError('vms', 400, ['message' => 'invalid filter', 'detail' => 'bad syntax']);

        try {
            $this->client()->vms()->filter('nonsense::')->get();
            $this->fail('Expected a RequestException.');
        } catch (RequestException $e) {
            $this->assertSame(400, $e->status);
            $this->assertSame('bad syntax', $e->body['detail']);
            $this->assertSame('GET', $e->method);
        }
    }

    public function test_every_exception_shares_one_catchable_base(): void
    {
        $this->fake->stubError('vms', 500);

        $this->expectException(XenOrchestraException::class);

        $this->client()->vms()->get();
    }

    /**
     * A reverse proxy or SSO portal answering with HTML instead of JSON is a
     * common deployment mistake; the message should say so rather than reading
     * "Syntax error".
     */
    public function test_a_non_json_body_produces_a_diagnosable_error(): void
    {
        $this->fake->stubRaw('vms', '<html><body>401 Authorization Required</body></html>', 200, [
            'Content-Type' => 'text/html',
        ]);

        try {
            $this->client()->vms()->get();
            $this->fail('Expected a XenOrchestraException.');
        } catch (XenOrchestraException $e) {
            $this->assertStringContainsString('Expected JSON', $e->getMessage());
            $this->assertStringContainsString('text/html', $e->getMessage());
        }
    }
}
