<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Client;

use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class StreamingHttpClient extends Client implements StreamingClientInterface
{
    public function sendStreamingRequest(RequestInterface $request): ResponseInterface
    {
        return $this->send($request, [
            'stream' => true,
            'timeout' => 0,
            'http_errors' => false,
            'allow_redirects' => false,
        ]);
    }
}
