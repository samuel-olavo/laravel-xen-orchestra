<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests\Feature;

use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use SamuelOlavo\XenOrchestra\Client\Collection;
use SamuelOlavo\XenOrchestra\Client\Response;
use SamuelOlavo\XenOrchestra\Models\Model;
use SamuelOlavo\XenOrchestra\Models\Task;
use SamuelOlavo\XenOrchestra\Tests\TestCase;

class RestApi040CoverageTest extends TestCase
{
    public static function routes(): iterable
    {
        $fixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/rest-api-0.40-calls.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($fixture['cases'] as $case) {
            yield $case['method'].' '.$case['template'] => [$case];
        }
    }

    /** Exercise every non-deprecated controller route through its public helper. */
    #[DataProvider('routes')]
    public function test_public_helper_matches_pinned_controller_route(array $case): void
    {
        $client = $this->client();
        $target = match ($case['target']) {
            'client' => $client,
            'resource' => $client->{$case['accessor']}(),
            'model' => new ('SamuelOlavo\\XenOrchestra\\Models\\'.$case['model'])(['id' => 'object'], $client),
        };
        $path = ltrim($case['path'], '/');
        $kind = $case['kind'];
        $payload = match ($kind) {
            'task' => '/rest/v0/tasks/task-1',
            'collection' => [],
            'created', 'upload-created', 'object' => ['id' => 'object'],
            default => ['ok' => true],
        };
        if (in_array($kind, ['binary', 'empty', 'upload'], true)) {
            $this->fake->stubRaw($path, $kind === 'binary' ? "binary\0data" : '', $kind === 'binary' ? 200 : 204);
        } else {
            $this->fake->stub($path, $payload, $kind === 'task' ? 202 : 200);
            if ($kind === 'task') {
                $this->fake->stub($path, ['id' => 'created']);
            }
        }

        $args = $case['args'];
        if ($kind === 'upload-created') {
            $args = [Utils::streamFor("binary\0data")];
        } elseif ($kind === 'upload') {
            $args = [Utils::streamFor("binary\0data"), ...$args];
        }
        $result = $target->{$case['call']}(...$args);

        $this->assertSame($case['method'], $this->fake->lastRequest()->getMethod());
        $this->assertSame('/rest/v0'.$case['path'], $this->fake->lastRequest()->getUri()->getPath());
        $this->assertSame(1, $this->fake->requestCount(), 'Helpers must not perform an implicit refresh.');
        if ($kind === 'task') {
            $this->assertInstanceOf(Task::class, $result);
            $this->assertSame('task-1', $result->id());
            $args['sync'] = true;
            $completed = $target->{$case['call']}(...$args);
            $this->assertTrue($completed->successful());
            $this->assertSame(['id' => 'created'], $completed->result());
            $count = $this->fake->requestCount();
            $completed->wait();
            $this->assertSame($count, $this->fake->requestCount());
            $this->fake->assertQuery('sync', 'true');
        } elseif (in_array($kind, ['created', 'upload-created', 'object'], true)) {
            $this->assertInstanceOf(Model::class, $result);
            $this->assertSame('object', $result->id());
        } elseif ($kind === 'collection') {
            $this->assertInstanceOf(Collection::class, $result);
        } elseif ($kind === 'binary' && $case['call'] !== 'auditLog') {
            $this->assertInstanceOf(Response::class, $result);
            $this->assertSame("binary\0data", $result->body());
        }
    }

    public function test_every_controller_route_is_accounted_for(): void
    {
        $source = json_decode(file_get_contents(__DIR__.'/../Fixtures/rest-api-0.40-routes.json'), true, flags: JSON_THROW_ON_ERROR);
        $calls = json_decode(file_get_contents(__DIR__.'/../Fixtures/rest-api-0.40-calls.json'), true, flags: JSON_THROW_ON_ERROR);
        $expected = array_map(fn ($r) => $r['method'].' '.$r['path'], $source['routes']);
        $covered = array_map(fn ($r) => $r['method'].' '.$r['template'], $calls['cases']);
        $covered = [...$covered, ...$calls['deprecated'], 'GET /events', 'POST /events/{id}/subscriptions', 'DELETE /events/{id}/subscriptions/{subscriptionId}'];
        sort($expected);
        sort($covered);
        $this->assertSame($expected, $covered);
    }
}
