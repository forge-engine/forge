<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use App\Modules\ForgeAuth\Exceptions\JwtTokenExpiredException;
use App\Modules\ForgeAuth\Exceptions\JwtTokenInvalidException;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class JwtService
{
    private const string HEADER = '{"typ":"JWT","alg":"HS256"}';
    private ?string $secret = null;

    public function __construct(
        private readonly Config $config
    )
    {
    }

    public function encode(array $payload): string
    {
        $secret = $this->getSecret();
        $header = self::HEADER;
        $headerEncoded = $this->base64UrlEncode($header);
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new JwtTokenInvalidException();
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $secret = $this->getSecret();
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);
        $expectedSignature = $this->base64UrlDecode($signatureEncoded);

        if (!hash_equals($signature, $expectedSignature)) {
            throw new JwtTokenInvalidException();
        }

        $payloadJson = $this->base64UrlDecode($payloadEncoded);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            throw new JwtTokenInvalidException();
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new JwtTokenExpiredException();
        }

        return $payload;
    }

    private function getSecret(): string
    {
        if ($this->secret === null) {
            $secret = $this->config->get('security.jwt.secret');
            if (empty($secret)) {
                throw new JwtTokenInvalidException('JWT secret not configured');
            }
            $this->secret = $secret;
        }

        return $this->secret;
    }

    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode(string $data): string
    {
        $decoded = base64_decode(str_replace(['-', '_'], ['+', '/'], $data), true);
        if ($decoded === false) {
            throw new JwtTokenInvalidException();
        }
        return $decoded;
    }
}

