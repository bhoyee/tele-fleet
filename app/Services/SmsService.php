<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message): array
    {
        $username = config('services.africastalking.username');
        $apiKey = config('services.africastalking.api_key');
        $senderId = config('services.africastalking.sender_id');
        $baseUrl = rtrim((string) config('services.africastalking.base_url'), '/');

        if (! $apiKey || ! $username) {
            Log::channel('telefleet')->warning('sms.skipped', [
                'phone' => $phone,
                'message' => $message,
                'reason' => 'Missing AFRICASTALKING credentials',
            ]);
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Missing AFRICASTALKING credentials',
            ];
        }

        $payload = [
            'username' => $username,
            'phoneNumbers' => [$phone],
            'message' => $message,
        ];
        if (! empty($senderId)) {
            $payload['senderId'] = $senderId;
        }

        try {
            Log::info('sms.request', [
                'base_url' => $baseUrl,
                'username' => $username,
                'sender_id' => $senderId ?: null,
            ]);
            $response = Http::timeout(10)
                ->asJson()
                ->withHeaders([
                    'apiKey' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->post($baseUrl.'/version1/messaging/bulk', $payload);

            if ($response->status() === 415) {
                $response = Http::timeout(10)
                    ->asForm()
                    ->withHeaders([
                        'apiKey' => $apiKey,
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Accept' => 'application/json',
                    ])
                    ->post($baseUrl.'/version1/messaging/bulk', $payload);
            }

            Log::channel('telefleet')->info('sms.sent', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->json(),
                'raw' => $response->body(),
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            Log::channel('telefleet')->error('sms.failed', [
                'phone' => $phone,
                'error' => $exception->getMessage(),
            ]);
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'raw' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }
}
