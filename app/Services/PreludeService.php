<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PreludeService
{
    private string $baseUrl = 'https://api.prelude.dev/v2';

    public function __construct()
    {
    }

    private function getApiKey(): ?string
    {
        return env('PRELUDE_API_KEY');
    }

    /**
     * Send OTP via SMS using Prelude Verify API.
     *
     * @param string $phone
     * @return string|null Verification ID if successful, null otherwise.
     */
    public function sendSmsVerification(string $phone): ?string
    {
        $apiKey = $this->getApiKey();
        
        if (!$apiKey) {
            Log::error('Prelude API Key is missing.');
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->post("{$this->baseUrl}/verification", [
                    'target' => [
                        'type' => 'phone_number',
                        'value' => $phone,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['id'] ?? null;
            }

            Log::error('Prelude API send verification failed', ['response' => $response->json()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Prelude API exception during send', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Validate OTP using Prelude Verify API.
     *
     * @param string $phone
     * @param string $code
     * @return bool True if valid, False otherwise.
     */
    public function validateSmsVerification(string $phone, string $code): bool
    {
        $apiKey = $this->getApiKey();
        
        if (!$apiKey) {
            Log::error('Prelude API Key is missing.');
            return false;
        }

        try {
            $response = Http::withToken($apiKey)
                ->post("{$this->baseUrl}/verification/check", [
                    'target' => [
                        'type' => 'phone_number',
                        'value' => $phone,
                    ],
                    'code' => $code,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['status']) && $data['status'] === 'success';
            }

            if ($response->status() === 400 || $response->status() === 404) {
                 // 400 typically means invalid code
                 return false;
            }

            Log::error('Prelude API validation failed', ['status' => $response->status(), 'response' => $response->json()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Prelude API exception during validate', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Placeholder for generic SMS sending (Prelude Verify API does not natively support custom SMS).
     * Prevents Fatal Errors when called from jobs.
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function sendSms(string $phone, string $message): bool
    {
        Log::warning('Attempted to send custom SMS via Prelude, but Prelude Verify API is only for OTP.', [
            'phone' => $phone,
            'message' => $message,
        ]);
        return false;
    }
}
