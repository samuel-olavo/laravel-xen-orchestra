<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase as BaseTestCase;
use SamuelOlavo\XenOrchestra\Client\XenOrchestraClient;
use SamuelOlavo\XenOrchestra\Testing\FakeHttpClient;

/**
 * Note there is no Laravel container here.
 *
 * That is the point of keeping the core framework-free: the entire package can
 * be tested with plain PHPUnit and a stub HTTP client, with no application to
 * boot. Only the tests that exercise src/Laravel/ need Testbench.
 */
abstract class TestCase extends BaseTestCase
{
    protected FakeHttpClient $fake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = new FakeHttpClient();
    }

    protected function client(string $url = 'https://xo.example.com'): XenOrchestraClient
    {
        $factory = new HttpFactory();

        return new XenOrchestraClient($url, 'secret-token', $this->fake, $factory, $factory);
    }
}
