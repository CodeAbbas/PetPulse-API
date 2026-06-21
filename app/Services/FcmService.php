<?php

declare(strict_types=1);

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Firebase Cloud Messaging client (HTTP v1 API).
 *
 * Sends data+notification messages to a single device token using
 * OAuth2 service-account auth. All failures are caught and logged —
 * a push failure must never propagate into the caller's DB transaction.
 *
 * Reference: AT2 Section 3.3, FR-07 (real-time owner alerting).
 */
class FcmService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function __construct(
        private readonly ?string $credentialsPath = null,
        private readonly ?string $projectId = null,
    ) {}

    /**
     * Send a notification to a single device token.
     *
     * Returns true on a 2xx response, false on any failure (network,
     * auth, invalid token). Never throws.
     *
     * @param array<string, mixed> $data
     */
    public function sendToToken(
        string $deviceToken,
        string $title,
        string $body,
        array $data = [],
    ): bool {
        $credentialsPath = $this->credentialsPath ?? config('services.fcm.credentials');
        $projectId = $this->projectId ?? config('services.fcm.project_id');

        if (empty($credentialsPath) || empty($projectId)) {
            Log::warning('FCM not configured; skipping push.');
            return false;
        }

        try {
            $accessToken = $this->fetchAccessToken($credentialsPath);

            $response = Http::withoutVerifying()
                ->withToken($accessToken)
                ->timeout(5)
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    [
                        'message' => [
                            'token' => $deviceToken,
                            // 🚨 Forces the Android OS to render a visual banner automatically
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            // Explicit mapping using keys or fallback patterns to guarantee safe structures
                            'data' => [
                                'event_id' => (string) ($data['event_id'] ?? ''),
                                'pet_id' => (string) ($data['pet_id'] ?? ''),
                                'event_type' => (string) ($data['event_type'] ?? ''),
                                'severity' => (string) ($data['severity'] ?? ''),
                            ],
                            // 🚨 Force high priority to bypass Android doze/battery saving modes
                            'android' => [
                                'priority' => 'high',
                                'notification' => [
                                    'channel_id' => 'default',
                                    'sound' => 'default',
                                    'default_sound' => true,
                                    'default_vibrate_timings' => true,
                                ],
                            ],
                        ],
                    ],
                );

            if ($response->successful()) {
                return true;
            }

            Log::warning('FCM push failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (Throwable $e) {
            Log::error('FCM push threw.', ['message' => $e->getMessage()]);
            return false;
        }
    }

    private function fetchAccessToken(string $credentialsPath): string
    {
        $credentials = new ServiceAccountCredentials(self::SCOPE, $credentialsPath);

        // 🚨 Create the unverified client AND wrap it in Google's required HttpHandler
        $guzzle = new \GuzzleHttp\Client(['verify' => false]);
        $handler = \Google\Auth\HttpHandler\HttpHandlerFactory::build($guzzle);
        
        $token = $credentials->fetchAuthToken($handler);

        return $token['access_token'] ?? throw new \RuntimeException('No FCM access token.');
    }
}