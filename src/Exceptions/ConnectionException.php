<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Exceptions;

/**
 * The request never got an HTTP response: DNS failure, refused connection,
 * TLS handshake rejected, or a timeout.
 *
 * Self-signed certificates are the usual culprit on internal XOA deployments —
 * see the verify_ssl config option.
 */
class ConnectionException extends XenOrchestraException
{
}
