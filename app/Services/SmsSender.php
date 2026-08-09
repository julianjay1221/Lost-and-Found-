<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsSender
{
    public function send(string $phoneNumber, string $message, array $context = []): bool
    {
        return match (config('services.sms.driver', 'log')) {
            'semaphore' => $this->sendViaSemaphore($phoneNumber, $message, $context),
            default => $this->logSms($phoneNumber, $message, $context),
        };
    }

    private function sendViaSemaphore(string $phoneNumber, string $message, array $context): bool
    {
        $apiKey = config('services.semaphore.key');

        if (! $apiKey) {
            Log::warning('Semaphore SMS notification skipped because the API key is missing.', $context);

            return false;
        }

        $payload = [
            'apikey' => $apiKey,
            'number' => $this->normalizePhoneNumber($phoneNumber),
            'message' => $message,
        ];

        if ($senderName = config('services.semaphore.sender_name')) {
            $payload['sendername'] = $senderName;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post(config('services.semaphore.endpoint'), $payload);
        } catch (Throwable $exception) {
            Log::error('Semaphore SMS notification failed before receiving a response.', array_merge($context, [
                'to' => $phoneNumber,
                'error' => $exception->getMessage(),
            ]));

            return false;
        }

        if ($response->failed() || $this->responseContainsFailure($response->json())) {
            Log::error('Semaphore SMS notification failed.', array_merge($context, [
                'to' => $phoneNumber,
                'status' => $response->status(),
                'response' => $response->json(),
            ]));

            return false;
        }

        Log::info('Semaphore SMS notification sent.', array_merge($context, [
            'to' => $phoneNumber,
            'status' => $response->status(),
        ]));

        return true;
    }

    private function logSms(string $phoneNumber, string $message, array $context): bool
    {
        Log::info('SMS notification logged.', array_merge($context, [
            'to' => $phoneNumber,
            'message' => $message,
        ]));

        return true;
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        return preg_replace('/\D+/', '', $phoneNumber) ?: $phoneNumber;
    }

    private function responseContainsFailure(mixed $response): bool
    {
        if (! is_array($response)) {
            return false;
        }

        $messages = array_is_list($response) ? $response : [$response];

        foreach ($messages as $message) {
            if (is_array($message) && strcasecmp($message['status'] ?? '', 'failed') === 0) {
                return true;
            }
        }

        return false;
    }
}
