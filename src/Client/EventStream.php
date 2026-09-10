<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Client;

use Psr\Http\Message\StreamInterface;

/** A single SSE connection. Reconnect and resubscribe explicitly after a disconnect. */
final class EventStream implements \IteratorAggregate
{
    private bool $consumed = false;

    public function __construct(private StreamInterface $stream)
    {
    }

    /** @return \Generator<int, ServerEvent> */
    public function getIterator(): \Generator
    {
        if ($this->consumed) {
            throw new \LogicException('An event connection can only be consumed once.');
        }
        $this->consumed = true;
        $data = [];
        $event = 'message';
        $id = null;
        $retry = null;
        $first = true;
        try {
            foreach ($this->lines() as $line) {
                if ($first) {
                    $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
                    $first = false;
                }
                if ($line === '') {
                    if ($data !== []) {
                        yield new ServerEvent($event, implode("\n", $data), $id, $retry);
                    }
                    $data = [];
                    $event = 'message';
                    continue;
                }
                if (str_starts_with($line, ':')) {
                    continue;
                }
                [$field, $value] = array_pad(explode(':', $line, 2), 2, '');
                if (str_starts_with($value, ' ')) {
                    $value = substr($value, 1);
                }
                if ($field === 'data') {
                    $data[] = $value;
                } elseif ($field === 'event') {
                    $event = $value === '' ? 'message' : $value;
                } elseif ($field === 'id' && ! str_contains($value, "\0")) {
                    $id = $value;
                } elseif ($field === 'retry' && preg_match('/^[0-9]+$/D', $value)) {
                    $retry = (int) $value;
                }
            }
        } finally {
            $this->close();
        }
    }

    private function lines(): \Generator
    {
        $line = '';
        $skipLf = false;
        while (! $this->stream->eof()) {
            $char = $this->stream->read(1);
            if ($char === '') {
                if ($this->stream->eof()) {
                    break;
                }
                throw new \RuntimeException('Event stream stopped before EOF.');
            }
            if ($skipLf && $char === "\n") {
                $skipLf = false;
                continue;
            }
            $skipLf = $char === "\r";
            if ($char === "\r" || $char === "\n") {
                yield $line;
                $line = '';
            } else {
                $line .= $char;
            }
        }
        // An incomplete event at EOF is deliberately discarded (SSE framing).
    }

    public function close(): void
    {
        $this->stream->close();
    }
}
