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
     * @param array<string, string> $data
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

            $response = Http::withToken($accessToken)
                ->timeout(5)
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    [
                        'message' => [
                            'token' => $deviceToken,
                            'notification' => ['title' => $title, 'body' => $body],
                            'data' => array_map(static fn ($v): string => (string) $v, $data),
                            'android' => [
                                'priority' => 'high',
                                'notification' => [
                                    'channel_id' => 'default',
                                    'sound' => 'default',
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
        $token = $credentials->fetchAuthToken();

        return $token['access_token'] ?? throw new \RuntimeException('No FCM access token.');
    }
}