<?php
/**
 * Ruta: controlvet/core/JWT.php
 *
 * Implementación propia y ligera de JSON Web Tokens (HS256), sin
 * dependencias externas (no requiere Composer/Internet), pensada para
 * ejecutarse 100% local bajo XAMPP.
 *
 * Se usa como capa adicional de seguridad sobre la sesión PHP:
 *  - Desde v1.1.0 el JWT lo emite el microservicio auth al iniciar sesión.
 *  - Cada solicitud a un módulo protegido valida firma, expiración y emisor.
 *  - Los endpoints de api/ (AJAX) se autentican EXCLUSIVAMENTE por JWT
 *    (Authorization: Bearer <token>), de forma independiente a la sesión.
 */

class JWT
{
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Valida un token y retorna sus claims, o null si es inválido/expirado.
     */
    public static function validate(?string $token): ?array
    {
        if (!$token || substr_count($token, '.') !== 2) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = explode('.', $token);

        $header = json_decode(self::base64UrlDecode($headerB64), true);
        if (!is_array($header) || ($header['alg'] ?? '') !== JWT_ALGO) {
            return null; // Bloquea "alg":"none" y cambios de algoritmo.
        }

        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "$headerB64.$payloadB64", JWT_SECRET, true)
        );

        if (!hash_equals($expectedSignature, $signatureB64)) {
            return null; // Firma inválida: token manipulado.
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);

        if (!is_array($payload) || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null; // Token expirado o mal formado.
        }

        if (($payload['iss'] ?? null) !== JWT_ISSUER) {
            return null; // Token emitido por otro sistema.
        }

        return $payload;
    }
}
