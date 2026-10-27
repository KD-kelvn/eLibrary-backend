<?php

namespace Tests\Feature;

use App\Http\Middleware\AssignRequestId;
use App\Logging\JsonLogContext;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class JsonLoggingTest extends TestCase
{
    public function test_response_includes_request_id_header(): void
    {
        $id = $this->get('/up')->assertOk()->headers->get(AssignRequestId::HEADER);

        $this->assertNotNull($id);
        $this->assertTrue(Str::isUuid($id));
    }

    public function test_keeps_valid_incoming_request_id(): void
    {
        $id = '0192a4f6-7c1e-7a3b-9f2d-1a2b3c4d5e6f';

        $this->get('/up', [AssignRequestId::HEADER => strtoupper($id)])
            ->assertOk()
            ->assertHeader(AssignRequestId::HEADER, $id);

        $this->assertSame($id, Context::get('request_id'));
    }

    public function test_json_log_lines_include_service_env_and_request_id(): void
    {
        $path = sys_get_temp_dir().'/elibrary-log-'.bin2hex(random_bytes(6)).'.log';

        config(['logging.channels.json_test' => [
            'driver' => 'single',
            'path' => $path,
            'tap' => [JsonLogContext::class],
        ]]);

        Context::add('request_id', '0192a4f6-7c1e-7a3b-9f2d-1a2b3c4d5e6f');

        Log::channel('json_test')->info('sms.sent', ['provider' => 'log']);

        $line = json_decode(trim((string) file_get_contents($path)), true, 512, JSON_THROW_ON_ERROR);
        @unlink($path);

        $this->assertSame('sms.sent', $line['message']);
        $this->assertSame('INFO', $line['level_name']);
        $this->assertSame(['provider' => 'log'], $line['context']);
        $this->assertSame('0192a4f6-7c1e-7a3b-9f2d-1a2b3c4d5e6f', $line['extra']['request_id'] ?? null);
        $this->assertSame('console', $line['extra']['service'] ?? null);
        $this->assertSame('testing', $line['extra']['env'] ?? null);
    }
}
