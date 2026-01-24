<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message): void
    {
        $apiKey = config('services.termii.key');
        $senderId = config('services.termii.sender_id');
        $baseUrl = rtrim((string) config('services.termii.base_url'), '/');

        if (! $apiKey || ! $senderId) {
            Log::channel('telefleet')->warning('sms.skipped', [
                'phone' => $phone,
                'message' => $message,
                'reason' => 'Missing TERMII_API_KEY or TERMII_SENDER_ID',
            ]);
            return;
        }

        $payload = [
            'to' => $phone,
            'from' => $senderId,
            'sms' => $message,
            'type' => 'plain',
            'api_key' => $apiKey,
        ];

        try {
            $response = Http::timeout(10)->post($baseUrl.'/api/sms/send', $payload);

            Log::channel('telefleet')->info('sms.sent', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        } catch (\Throwable $exception) {
            Log::channel('telefleet')->error('sms.failed', [
                'phone' => $phone,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
