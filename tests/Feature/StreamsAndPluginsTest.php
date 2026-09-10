<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Tests\Feature;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use SamuelOlavo\XenOrchestra\Client\EventStream;
use SamuelOlavo\XenOrchestra\Client\Response;
use SamuelOlavo\XenOrchestra\Client\StreamingHttpClient;
use SamuelOlavo\XenOrchestra\Exceptions\RequestException;
use SamuelOlavo\XenOrchestra\Models\Host;
use SamuelOlavo\XenOrchestra\Models\Network;
use SamuelOlavo\XenOrchestra\Models\VirtualDisk;
use SamuelOlavo\XenOrchestra\Models\VirtualInterface;
use SamuelOlavo\XenOrchestra\Models\VirtualMachine;
use SamuelOlavo\XenOrchestra\Tests\TestCase;

class StreamsAndPluginsTest extends TestCase
{
    public function test_sse_handles_bom_comments_all_line_endings_and_multiline_data(): void
    {
        $body = "\xEF\xBB\xBF:keepalive\r\nid: e1\r\nretry: 500\r\nevent: init\r\ndata: \"connection\"\r\n\r\n";
        $body .= "event: update\ndata: {\ndata: \"id\": \"vm\"}\n\n";
        $body .= "id:\rdata: done\r\rdata: incomplete";
        $this->fake->stubRaw('events', $body, headers: ['Content-Type' => 'text/event-stream; charset=utf-8']);
        $events = iterator_to_array($this->client()->events()->open());
        $this->assertCount(3, $events);
        $this->assertSame('init', $events[0]->event);
        $this->assertSame('connection', $events[0]->json());
        $this->assertSame(['id' => 'vm'], $events[1]->json());
        $this->assertSame('e1', $events[1]->id);
        $this->assertSame(500, $events[1]->retry);
        $this->assertSame('', $events[2]->id);
        $this->assertSame('message', $events[2]->event);
        $this->assertSame('text/event-stream', $this->fake->lastRequest()->getHeaderLine('Accept'));
    }

    public function test_sse_subscriptions_use_connection_id_and_xo_collection_type(): void
    {
        $this->fake->stub('events/connection%2Fid/subscriptions', ['id' => 'VM'], 201);
        $this->fake->stubRaw('events/connection%2Fid/subscriptions/VM', '', 204);
        $events = $this->client()->events();
        $this->assertSame('VM', $events->subscribe('connection/id', 'VM', ['id', 'name_label']));
        $this->assertSame(['collection' => 'VM', 'fields' => ['id', 'name_label']], json_decode((string) $this->fake->lastRequest()->getBody(), true));
        $events->unsubscribe('connection/id', 'VM');
        $this->fake->assertSent('events/connection%2Fid/subscriptions/VM', 'DELETE');
    }

    public function test_sse_closes_the_stream_and_rejects_reuse(): void
    {
        $body = Utils::streamFor("data: hello\n\n");
        $events = new EventStream($body);
        $this->assertCount(1, iterator_to_array($events));
        $this->assertFalse($body->isReadable());
        $this->expectException(\LogicException::class);
        iterator_to_array($events);
    }

    public function test_sse_rejects_a_proxy_html_response(): void
    {
        $this->fake->stubRaw('events', '<html>login</html>', headers: ['Content-Type' => 'text/html']);
        $this->expectException(\UnexpectedValueException::class);
        $this->client()->events()->open();
    }

    public function test_default_stream_transport_disables_buffering_and_redirects(): void
    {
        $transport = new StreamingHttpClient(['handler' => function ($request, $options) {
            $this->assertTrue($options['stream']);
            $this->assertFalse($options['allow_redirects']);
            $this->assertFalse($options['http_errors']);
            $this->assertSame(0, $options['timeout']);

            return Create::promiseFor(new PsrResponse(200, [], 'body'));
        }]);
        $this->assertSame(200, $transport->sendStreamingRequest(new Request('GET', 'https://xo.test/events'))->getStatusCode());
    }

    public function test_upload_preserves_binary_bytes_current_position_and_caller_ownership(): void
    {
        $this->fake->stubRaw('vdis/disk.raw', '', 204);
        $source = Utils::streamFor("skip\0binary\xff");
        $source->seek(4);
        $disk = new VirtualDisk(['id' => 'disk'], $this->client());
        $disk->importContent($source, 'raw');
        $request = $this->fake->lastRequest();
        $this->assertSame('application/octet-stream', $request->getHeaderLine('Content-Type'));
        $this->assertSame('8', $request->getHeaderLine('Content-Length'));
        $request->getBody()->rewind();
        $this->assertSame("\0binary\xff", $request->getBody()->getContents());
        $this->assertTrue($source->isReadable());
    }

    public function test_download_saves_a_non_seekable_body_without_json_decoding(): void
    {
        $path = sys_get_temp_dir().'/xo-download-'.bin2hex(random_bytes(8));
        $body = str_repeat("\0\xffbytes", 20000);
        $stream = new NoSeekStream(Utils::streamFor($body));
        $response = new Response(new PsrResponse(200, [], $stream), 'GET', 'https://xo.test/vms/vm.xva');
        try {
            $this->assertSame(strlen($body), $response->saveTo($path));
            $this->assertSame(hash('sha256', $body), hash_file('sha256', $path));
            $this->assertFalse($stream->isReadable());
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_download_refuses_to_overwrite_an_existing_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'xo-existing-');
        file_put_contents($path, 'original');
        $response = new Response(new PsrResponse(200, [], 'new'), 'GET', 'https://xo.test/file');
        try {
            try {
                $response->saveTo($path);
                $this->fail('Expected an existing file to be preserved.');
            } catch (\RuntimeException) {
                $this->assertSame('original', file_get_contents($path));
            }
        } finally {
            unlink($path);
        }
    }

    public function test_streaming_http_errors_are_not_returned_as_successful_downloads(): void
    {
        $this->fake->stubError('vms/vm.xva', 403);
        $vm = new VirtualMachine(['id' => 'vm'], $this->client());
        $this->expectException(RequestException::class);
        $vm->export();
    }

    public function test_ipmi_uses_the_plugin_route_and_preserves_sensor_data(): void
    {
        $payload = ['productName' => 'server', 'sensors' => [['name' => 'Fan', 'value' => '4000', 'event' => 'ok']]];
        $this->fake->stub('plugins/ipmi-sensors/hosts/host/ipmi', $payload);
        $host = new Host(['id' => 'host'], $this->client());
        $this->assertSame($payload, $host->ipmi());
    }

    public function test_sdn_operations_use_plugin_routes_and_preserve_null_patches(): void
    {
        foreach ([Network::class, VirtualInterface::class] as $class) {
            foreach (['addTrafficRule' => 'add_traffic_rule', 'deleteTrafficRule' => 'delete_traffic_rule', 'updateTrafficRule' => 'update_traffic_rule'] as $method => $action) {
                $path = 'plugins/sdn-controller/'.$class::endpoint().'/object/actions/'.$action;
                $this->fake->stubRaw($path, '', 204);
                $model = new $class(['id' => 'object'], $this->client());
                $body = ['oldRule' => ['allow' => true, 'port' => 443], 'newRule' => ['port' => null, 'allow' => false]];
                $this->assertTrue($model->$method($body, sync: true)->successful());
                $this->assertSame($body, json_decode((string) $this->fake->lastRequest()->getBody(), true));
                $this->fake->assertSent($path, 'POST')->assertQuery('sync', 'true');
            }
        }
    }

    public function test_missing_identity_is_rejected_before_a_mutation(): void
    {
        $disk = new VirtualDisk([], $this->client());
        try {
            $disk->delete();
            $this->fail('Expected missing identity to fail.');
        } catch (\LogicException) {
            $this->fake->assertNothingSent();
        }
    }

    public function test_empty_json_objects_and_explicit_host_selection(): void
    {
        $this->fake->stubRaw('vms/vm/actions/start', '', 204);
        $vm = new VirtualMachine(['id' => 'vm'], $this->client());
        $vm->start();
        $this->assertSame('{}', (string) $this->fake->lastRequest()->getBody());
        $vm->start(hostId: 'host');
        $this->assertSame(['hostId' => 'host'], json_decode((string) $this->fake->lastRequest()->getBody(), true));
    }

    public function test_snapshot_scopes_do_not_expose_vm_mutations_or_lose_their_path(): void
    {
        $this->fake->stub('vm-snapshots/snapshot', ['id' => 'snapshot']);
        $snapshot = $this->client()->vms()->snapshots()->find('snapshot');
        $this->assertSame('vm-snapshots/snapshot', $snapshot->href());
        $this->assertFalse(method_exists($snapshot, 'start'));
        $this->assertFalse(method_exists($snapshot, 'update'));
    }
}
