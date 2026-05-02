<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use RuntimeException;

class JwtService
{
    public function issue(int $userId, ?int $companyId): string
    {
        $now = Carbon::now();
        $payload = [
            'iss' => config('app.url'),
            'iat' => $now->timestamp,
            'exp' => $now->copy()->addMinutes((int) env('JWT_TTL_MINUTES', 1440))->timestamp,
            'sub' => $userId,
            'user_id' => $userId,
            'company_id' => $companyId,
        ];

        return $this->encode($payload);
    }

    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token.');
        }

        [$header, $payload, $signature] = $parts;
        $expected = $this->sign($header.'.'.$payload);

        if (! hash_equals($expected, $signature)) {
            throw new RuntimeException('Invalid token signature.');
        }

        $claims = json_decode($this->base64UrlDecode($payload), true);

        if (! is_array($claims) || empty($claims['exp']) || $claims['exp'] < Carbon::now()->timestamp) {
            throw new RuntimeException('Expired token.');
        }

        return $claims;
    }

    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $body = $this->base64UrlEncode(json_encode($payload));

        return $header.'.'.$body.'.'.$this->sign($header.'.'.$body);
    }

    private function sign(string $value): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $value, $this->secret(), true));
    }

    private function secret(): string
    {
        $secret = (string) env('JWT_SECRET', config('app.key'));

        if ($secret === '') {
            throw new RuntimeException('JWT secret is missing.');
        }

        return $secret;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/'));
    }
}
