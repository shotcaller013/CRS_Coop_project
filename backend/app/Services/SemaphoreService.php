<?php
// app/Services/SemaphoreService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SemaphoreService
{
    private const API_URL = 'https://api.semaphore.co/api/v4/messages';

    private string $apiKey;
    private string $senderName;

    public function __construct()
    {
        $this->apiKey     = config('semaphore.api_key', '');
        $this->senderName = config('semaphore.sender_name', 'CRSECCO');
    }

    /**
     * Send an SMS via Semaphore.
     *
     * @param  string $number  Philippine mobile number — e.g. 09171234567 or +639171234567
     * @param  string $message Max 160 chars for a single SMS; Semaphore supports concatenated
     * @return array  ['success' => bool, 'message_id' => string|null, 'raw' => array]
     */
    public function send(string $number, string $message): array
    {
        if (empty($this->apiKey)) {
            Log::warning('SemaphoreService: API key not configured. SMS not sent.', [
                'number' => $number,
            ]);
            return ['success' => false, 'message_id' => null, 'raw' => ['error' => 'API key not set']];
        }

        // Normalize number — Semaphore accepts 09xxxxxxxxx format
        $number = $this->normalizeNumber($number);

        try {
            $response = Http::timeout(15)->post(self::API_URL, [
                'apikey'      => $this->apiKey,
                'number'      => $number,
                'message'     => $message,
                'sendername'  => $this->senderName,
            ]);

            $body = $response->json();

            if ($response->successful() && !empty($body)) {
                $first = is_array($body) ? ($body[0] ?? $body) : $body;
                return [
                    'success'    => true,
                    'message_id' => $first['message_id'] ?? null,
                    'raw'        => $body,
                ];
            }

            Log::error('SemaphoreService: API error', [
                'status' => $response->status(),
                'body'   => $body,
            ]);

            return ['success' => false, 'message_id' => null, 'raw' => $body ?? []];

        } catch (\Throwable $e) {
            Log::error('SemaphoreService: exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message_id' => null, 'raw' => ['error' => $e->getMessage()]];
        }
    }

    /**
     * Normalize Philippine mobile number to 09xxxxxxxxx format.
     * Handles: +63, 63, 0, and bare 9xxxxxxxx
     */
    private function normalizeNumber(string $number): string
    {
        $number = preg_replace('/\D/', '', $number); // strip non-digits
        if (str_starts_with($number, '63')) {
            $number = '0' . substr($number, 2);
        } elseif (str_starts_with($number, '9') && strlen($number) === 10) {
            $number = '0' . $number;
        }
        return $number;
    }
}
