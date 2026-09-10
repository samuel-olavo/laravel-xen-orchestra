<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Client;

final readonly class ServerEvent
{
    public function __construct(
        public string $event,
        public string $data,
        public ?string $id = null,
        public ?int $retry = null,
    ) {
    }

    public function json(): mixed
    {
        return json_decode($this->data, true, 512, JSON_THROW_ON_ERROR);
    }
}
