<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Security;

/**
 * JSON Web Token HS256 sin dependencias externas (derivado de la
 * implementación original de ControlVet, ahora parametrizado: el secreto
 * llega por variable de entorno, nunca desde el código versionado).
 */
final class Jwt
{
    public function __construct(
        private readonly string $secret,
        private readonly string $issuer = 'ControlVet',
        private readonly int $ttlSeconds = 14400,
    ) {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('JWT_SECRET debe tener al menos 32 caracteres.');
        }
    }

    public static function fromEnv(): self
    {
        return new self(
            (string)getenv('JWT_SECRET'),
            (string)(getenv('JWT_ISSUER') ?: 'ControlVet'),
            (int)(getenv('JWT_TTL_SECONDS') ?: 14400),
        );
    }

    /** @param array<string,mixed> $claims */
    public function encode(array $claims, ?int $now = null): string
    {
        $now ??= time();
        $payload = array_merge($claims, ['iss' => $this->issuer, 'iat' => $now, 'exp' => $now + $this->ttlSeconds]);
        $segments = [
            self::b64(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])),
            self::b64((string)json_encode($payload)),
        ];
        $segments[] = self::b64(hash_hmac('sha256', implode('.', $segments), $this->secret, true));
        return implode('.', $segments);
    }

    /** @return array<string,mixed>|null Claims o null si el token es inválido/expirado. */
    public function decode(?string $token, ?int $now = null): ?array
    {
        if (!$token || substr_count($token, '.') !== 2) {
            return null;
        }
        [$h, $p, $s] = explode('.', $token);

        $header = json_decode(self::unb64($h), true);
        if (!is_array($header) || ($header['alg'] ?? '') !== 'HS256') {
            return null; // Bloquea "alg":"none" y cambios de algoritmo.
        }
        if (!hash_equals(self::b64(hash_hmac('sha256', "$h.$p", $this->secret, true)), $s)) {
            return null;
        }
        $payload = json_decode(self::unb64($p), true);
        if (!is_array($payload) || !isset($payload['exp']) || $payload['exp'] < ($now ?? time())) {
            return null;
        }
        if (($payload['iss'] ?? null) !== $this->issuer) {
            return null;
        }
        return $payload;
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function unb64(string $data): string
    {
        $pad = strlen($data) % 4;
        return (string)base64_decode(strtr($data . ($pad ? str_repeat('=', 4 - $pad) : ''), '-_', '+/'));
    }
}
