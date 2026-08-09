<?php

namespace Tests\Feature;

use App\Services\SmsSender;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsSenderTest extends TestCase
{
    public function test_sms_sender_posts_message_to_semaphore_when_configured(): void
    {
        config([
            'services.sms.driver' => 'semaphore',
            'services.semaphore.key' => 'test-api-key',
            'services.semaphore.sender_name' => 'LostFound',
            'services.semaphore.endpoint' => 'https://api.semaphore.co/api/v4/messages',
        ]);

        Http::fake([
            'https://api.semaphore.co/api/v4/messages' => Http::response([
                [
                    'message_id' => 123,
                    'recipient' => '09171234567',
                    'status' => 'Queued',
                ],
            ]),
        ]);

        $sent = app(SmsSender::class)->send('0917 123 4567', 'Found item match: Please check your report.');

        $this->assertTrue($sent);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.semaphore.co/api/v4/messages'
                && $request['apikey'] === 'test-api-key'
                && $request['number'] === '09171234567'
                && $request['message'] === 'Found item match: Please check your report.'
                && $request['sendername'] === 'LostFound';
        });
    }
}
