<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Signs and verifies EHR share-link JWTs (FR-05 / FR-06).
 *
 * The JWT carries a unique token id (jti), the pet id, and an expiry.
 * Only a SHA-256 HASH of the emitted JWT is persisted (ehr_tokens.jwt_hash);
 * the raw token is never stored, so a database leak cannot reconstruct a
 * working share link. Verification re-hashes the presented token and looks
 * up the row by that hash.
 *
 * Uses a self-contained HS256 implementation to avoid adding a JWT
 * dependency. The signing key is APP_KEY (already a high-entropy secret).
 */
final class EhrTokenService
{
    private const ALGO = 'HS256';

    /**
     * Build a signed JWT for a pet EHR share link.
     *
     * @return array{jwt: string, jti: string, expires_at: Carbon}
     */
    public function issue(string $petId, int $ttlHours = 72): array
    {
        $jti = (string) Str::uuid();
        $issuedAt = Carbon::now();
        $expiresAt = $issuedAt->copy()->addHours($ttlHours);

        $payload = [
            'jti' => $jti,
            'pet_id' => $petId,
            'iat' => $issuedAt->timestamp,
            'exp' => $expiresAt->timestamp,
            'purpose' => 'ehr_share',
        ];

        $jwt = $this->encode($payload);

        return [
            'jwt' => $jwt,
            'jti' => $jti,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Verify a JWT's signature and expiry.
     *
     * @return array<string, mixed>|null  Decoded claims, or null if the
     *         token is malformed, tampered, or expired.
     */
    public function verify(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $providedSignature] = $parts;

        // Recompute the signature over header.payload and compare in
        // constant time to resist timing attacks.
        $expectedSignature = $this->sign("{$encodedHeader}.{$encodedPayload}");
        if (! hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);
        if (! is_array($payload)) {
            return null;
        }

        // Expiry check.
        if (! isset($payload['exp']) || Carbon::now()->timestamp >= (int) $payload['exp']) {
            return null;
        }

        // Purpose check — reject tokens minted for anything else.
        if (($payload['purpose'] ?? null) !== 'ehr_share') {
            return null;
        }

        return $payload;
    }

    /**
     * SHA-256 hash of a JWT for at-rest storage (fits the varchar(64) column).
     */
    public function hash(string $jwt): string
    {
        return hash('sha256', $jwt);
    }

    // ── Internal HS256 encoding ──────────────────────────────────────────

    /**
     * @param array<string, mixed> $payload
     */
    private function encode(array $payload): string
    {
        $header = ['typ' => 'JWT', 'alg' => self::ALGO];

        $encodedHeader = $this->base64UrlEncode(json_encode($header));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->sign("{$encodedHeader}.{$encodedPayload}");

        return "{$encodedHeader}.{$encodedPayload}.{$signature}";
    }

    private function sign(string $data): string
    {
        $key = $this->signingKey();
        $raw = hash_hmac('sha256', $data, $key, true);

        return $this->base64UrlEncode($raw);
    }

    private function signingKey(): string
    {
        $key = (string) config('app.key');

        // Laravel prefixes keys with "base64:"; decode to raw bytes.
        if (str_starts_with($key, 'base64:')) {
            return base64_decode(substr($key, 7));
        }

        return $key;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}