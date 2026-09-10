<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Laravel;

use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface;
use SamuelOlavo\XenOrchestra\Client\HttpClientFactory;
use SamuelOlavo\XenOrchestra\Client\XenOrchestraClient;
use SamuelOlavo\XenOrchestra\Exceptions\XenOrchestraException;
use SamuelOlavo\XenOrchestra\Laravel\Commands\ListVmsCommand;
use SamuelOlavo\XenOrchestra\Laravel\Commands\PingCommand;

/**
 * The adapter between the framework-free core and Laravel.
 *
 * Everything Laravel-specific lives under this namespace on purpose: if a class
 * outside src/Laravel/ ever needs `config()` or a facade, the wrong thing has
 * happened and the directory layout makes it obvious.
 */
class XenOrchestraServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/xen-orchestra.php', 'xen-orchestra');

        $this->app->singleton(XenOrchestraClient::class, function ($app): XenOrchestraClient {
            /** @var Config $config */
            $config = $app['config'];

            $url = $config->get('xen-orchestra.url');
            $token = $config->get('xen-orchestra.token');

            if (! is_string($url) || $url === '') {
                throw new XenOrchestraException(
                    'Xen Orchestra URL is not configured. Set XO_URL in your .env file.'
                );
            }

            if (! is_string($token) || $token === '') {
                throw new XenOrchestraException(
                    'Xen Orchestra token is not configured. Set XO_TOKEN in your .env file.'
                );
            }

            $verify = $this->normaliseVerify($config->get('xen-orchestra.verify_ssl', true));
            $timeout = (int) $config->get('xen-orchestra.timeout', 30);
            $connectTimeout = (int) $config->get('xen-orchestra.connect_timeout', 10);

            $factory = new HttpFactory();

            $client = new XenOrchestraClient(
                $url,
                $token,
                HttpClientFactory::make($verify, $timeout, $connectTimeout),
                $factory,
                $factory,
            );

            return $client->withLongPollClientFactory(
                static fn (int $wait): ClientInterface => HttpClientFactory::makeForWaiting(
                    $verify,
                    max($wait, $timeout),
                    $connectTimeout,
                ),
            );
        });

        $this->app->alias(XenOrchestraClient::class, 'xen-orchestra');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/xen-orchestra.php' => $this->app->configPath('xen-orchestra.php'),
            ], 'xen-orchestra-config');

            $this->commands([
                PingCommand::class,
                ListVmsCommand::class,
            ]);
        }
    }

    /**
     * `XO_VERIFY_SSL` arrives as a string from the environment. "false"/"0"
     * disable verification, a path pins a CA bundle, anything else is true.
     */
    protected function normaliseVerify(mixed $value): bool|string
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));

            if (in_array($lower, ['false', '0', 'off', 'no'], true)) {
                return false;
            }

            if (in_array($lower, ['true', '1', 'on', 'yes'], true)) {
                return true;
            }

            return $value; // treated as a CA bundle path
        }

        return (bool) $value;
    }

    /** @return list<string> */
    public function provides(): array
    {
        return [XenOrchestraClient::class, 'xen-orchestra'];
    }
}
