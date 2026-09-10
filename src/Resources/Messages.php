<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Resources;

use SamuelOlavo\XenOrchestra\Models\Message;

/** @method Message find(string $id, array $fields = []) */
class Messages extends Resource
{
    public function endpoint(): string
    {
        return 'messages';
    }

    public function modelClass(): string
    {
        return Message::class;
    }
}
