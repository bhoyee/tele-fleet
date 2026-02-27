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
            // Africa's Talking expects `to` as a comma-separated string or a single phone number.
            'to' => $phone,
            'message' => $message,
        ];
        if (! empty($senderId)) {
            // Sender ID / short code
            $payload['from'] = $senderId;
        }

        try {
            $endpoint = $baseUrl . '/version1/messaging';

            Log::channel('telefleet')->info('sms.request', [
                'base_url' => $baseUrl,
                'endpoint' => $endpoint,
                'username' => $username,
                'sender_id' => $senderId ?: null,
            ]);
            $response = Http::timeout(10)
                ->asJson()
                ->withHeaders([
                    // AT docs use `apiKey`, but allow for servers that expect lowercase.
                    'apiKey' => $apiKey,
                    'apikey' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if ($response->status() === 415) {
                $response = Http::timeout(10)
                    ->asForm()
                    ->withHeaders([
                        'apiKey' => $apiKey,
                        'apikey' => $apiKey,
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Accept' => 'application/json',
                    ])
                    ->post($endpoint, $payload);
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
