<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Laravel\Commands;

use Illuminate\Console\Command;
use SamuelOlavo\XenOrchestra\Client\XenOrchestraClient;
use SamuelOlavo\XenOrchestra\Exceptions\AuthenticationException;
use SamuelOlavo\XenOrchestra\Exceptions\ConnectionException;
use SamuelOlavo\XenOrchestra\Exceptions\XenOrchestraException;

/**
 * The first thing anyone should run after installing this package.
 *
 * Its value is in the failure paths: the three ways a XOA connection goes wrong
 * (unreachable, bad certificate, rejected token) look very similar from a stack
 * trace and very different from here.
 */
class PingCommand extends Command
{
    protected $signature = 'xo:ping';

    protected $description = 'Check connectivity and authentication against Xen Orchestra';

    public function handle(XenOrchestraClient $client): int
    {
        $this->line('Connecting to <info>'.$client->baseUrl().'</info> ...');

        try {
            $client->ping();
        } catch (ConnectionException $e) {
            $this->components->error('Could not reach the appliance.');
            $this->line('  '.$e->getMessage());
            $this->newLine();
            $this->line('  Check XO_URL, and whether the certificate is self-signed.');
            $this->line('  If it is, set <comment>XO_VERIFY_SSL=false</comment> or point it at a CA bundle.');

            return self::FAILURE;
        } catch (AuthenticationException $e) {
            $this->components->error('The appliance answered, but rejected the token.');
            $this->line('  '.$e->getMessage());
            $this->newLine();
            $this->line('  Check XO_TOKEN. Remember the token is sent as a cookie, not a Bearer header,');
            $this->line('  so a token that fails with <comment>curl -H "Authorization: ..."</comment> may still be valid.');

            return self::FAILURE;
        } catch (XenOrchestraException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Connected and authenticated.');

        try {
            $pools = $client->pools()->fields(['name_label'])->get();

            if ($pools->isNotEmpty()) {
                $this->line('  Pools: <info>'.implode(', ', array_filter($pools->pluck('name_label'))).'</info>');
            }
        } catch (XenOrchestraException) {
            // Ping succeeded; a restricted ACL on /pools is not a failure here.
        }

        return self::SUCCESS;
    }
}
