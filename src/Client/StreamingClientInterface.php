<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface StreamingClientInterface extends ClientInterface
{
    /** Return after receiving headers, without buffering the response body. */
    public function sendStreamingRequest(RequestInterface $request): ResponseInterface;
}
