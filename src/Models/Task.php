<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Models;

use SamuelOlavo\XenOrchestra\Exceptions\ConnectionException;
use SamuelOlavo\XenOrchestra\Exceptions\TaskFailedException;
use SamuelOlavo\XenOrchestra\Exceptions\TaskTimeoutException;

/**
 * An asynchronous XO operation.
 *
 * The important part of this class is `wait()`, and specifically how it does
 * *not* work. The obvious implementation is a busy loop:
 *
 *     while (true) { sleep(2); $task = $client->get("tasks/{$id}"); ... }
 *
 * That hammers the XOA and still reports completion up to two seconds late. XO
 * offers `GET /tasks/<id>?wait=true`, which blocks server-side and answers the
 * instant the task reaches a terminal state — one request, no polling interval,
 * no lag. That is what `wait()` uses.
 *
 * The loop that remains exists only because an HTTP transport has its own
 * timeout: if the connection drops before the task finishes, we re-establish
 * the long poll rather than giving up. It is not a polling interval.
 */
class Task extends Model
{
    /** Statuses XO uses for a task that will not change again. */
    public const TERMINAL = ['success', 'failure', 'interrupted', 'cancelled', 'aborted'];

    public static function endpoint(): string
    {
        return 'tasks';
    }

    public function status(): string
    {
        $status = $this->get('status');

        return is_string($status) ? $status : 'unknown';
    }

    public function name(): ?string
    {
        $properties = $this->get('properties');

        if (is_array($properties)) {
            foreach (['name', 'method', 'type'] as $key) {
                if (isset($properties[$key]) && is_string($properties[$key])) {
                    return $properties[$key];
                }
            }
        }

        $name = $this->get('name');

        return is_string($name) ? $name : null;
    }

    public function finished(): bool
    {
        return in_array($this->status(), self::TERMINAL, true);
    }

    public function successful(): bool
    {
        return $this->status() === 'success';
    }

    public function failed(): bool
    {
        return $this->finished() && ! $this->successful();
    }

    /** Whatever the operation returned — for a snapshot, the new object's id. */
    public function result(): mixed
    {
        return $this->get('result');
    }

    public function errorMessage(): ?string
    {
        foreach (['result', 'error'] as $key) {
            $value = $this->get($key);

            if (is_string($value) && $value !== '') {
                return $value;
            }

            if (is_array($value)) {
                foreach (['message', 'code', 'name'] as $inner) {
                    if (isset($value[$inner]) && is_string($value[$inner])) {
                        return $value[$inner];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Block until the task reaches a terminal state.
     *
     * @param  int   $timeout  Overall budget in seconds, across reconnections.
     * @param  bool  $throw    Raise on failure. Pass false when failure is an
     *                         expected outcome you want to branch on.
     *
     * @throws TaskTimeoutException  budget exhausted; the task keeps running
     * @throws TaskFailedException   terminal but unsuccessful, and $throw is on
     */
    public function wait(int $timeout = 300, bool $throw = true): static
    {
        if (! $this->finished()) {
            $id = $this->id();

            if ($id === null) {
                throw new TaskTimeoutException('unknown', $timeout);
            }

            $deadline = time() + $timeout;
            $client = $this->client->forLongPolling($timeout);

            while (! $this->finished()) {
                if (time() >= $deadline) {
                    throw new TaskTimeoutException($id, $timeout);
                }

                try {
                    $payload = $client->get(self::endpoint().'/'.$id, ['wait' => true]);

                    if (is_array($payload)) {
                        $this->attributes = $payload + $this->attributes;
                    }
                } catch (ConnectionException) {
                    // Transport timeout while the XOA held the connection open.
                    // Re-establish the long poll if there is budget left.
                    if (time() >= $deadline) {
                        throw new TaskTimeoutException($id, $timeout);
                    }
                }
            }
        }

        if ($throw && $this->failed()) {
            throw new TaskFailedException($this);
        }

        return $this;
    }

    /** `POST /tasks/<id>/actions/abort` */
    public function abort(): static
    {
        $this->client->post($this->path('actions', 'abort'));

        return $this;
    }

    /** `DELETE /tasks/<id>` — removes a finished task from the list. */
    public function forget(): void
    {
        $this->client->delete((string) $this->href());
    }

    /**
     * Build a task from whatever an action endpoint returned.
     *
     * XO is inconsistent here: some actions answer with a bare JSON string
     * holding the task id, others with an object, others with an href. All
     * three end up as a usable Task.
     */
    public static function fromActionResponse(
        mixed $payload,
        \SamuelOlavo\XenOrchestra\Client\XenOrchestraClient $client,
        bool $sync = false,
    ): self {
        if ($sync) {
            return new self(['status' => 'success', 'result' => $payload], $client);
        }

        if (is_string($payload) && $payload !== '') {
            return new self(
                str_contains($payload, '/')
                    ? ['href' => $payload]
                    : ['id' => $payload, 'href' => self::endpoint().'/'.$payload],
                $client,
            );
        }

        if (is_array($payload)) {
            return new self($payload, $client);
        }

        // A synchronous action (`?sync=true`) has nothing to wait for.
        return new self(['status' => 'success', 'result' => $payload], $client);
    }
}
