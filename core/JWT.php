<?php
/**
 * Ruta: controlvet/core/JWT.php
 *
 * Implementación propia y ligera de JSON Web Tokens (HS256), sin
 * dependencias externas (no requiere Composer/Internet), pensada para
 * ejecutarse 100% local bajo XAMPP.
 *
 * Se usa como capa adicional de seguridad sobre la sesión PHP:
 *  - Al iniciar sesión se emite un JWT firmado con los datos del usuario.
 *  - Cada solicitud a un módulo protegido valida firma, expiración y rol.
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
     * Genera un token firmado a partir de un arreglo de claims.
     */
    public static function generate(array $claims): string
    {
        $header = ['typ' => 'JWT', 'alg' => JWT_ALGO];

        $payload = array_merge($claims, [
            'iss' => JWT_ISSUER,
            'iat' => time(),
            'exp' => time() + JWT_EXPIRATION_SECONDS,
        ]);

        $segments = [
            self::base64UrlEncode(json_encode($header)),
            self::base64UrlEncode(json_encode($payload)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), JWT_SECRET, true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
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

        return $payload;
    }
}
