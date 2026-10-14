<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Tests;

use ControlVet\Kernel\Security\Jwt;
use PHPUnit\Framework\TestCase;

final class JwtTest extends TestCase
{
    private const SECRET = 'secreto-de-pruebas-unitarias-de-32-caracteres!';

    private static function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    public function testTokenValidoRetornaClaims(): void
    {
        $jwt = new Jwt(self::SECRET);
        $claims = $jwt->decode($jwt->encode(['sub' => 7, 'rol' => 'veterinario']));

        self::assertSame(7, $claims['sub']);
        self::assertSame('veterinario', $claims['rol']);
        self::assertSame('ControlVet', $claims['iss']);
    }

    public function testTokenExpiradoEsRechazado(): void
    {
        $jwt = new Jwt(self::SECRET, 'ControlVet', 60);
        $token = $jwt->encode(['sub' => 1, 'rol' => 'administrador'], time() - 120);

        self::assertNull($jwt->decode($token));
    }

    public function testFirmaManipuladaEsRechazada(): void
    {
        $jwt = new Jwt(self::SECRET);
        [$h, , $s] = explode('.', $jwt->encode(['sub' => 4, 'rol' => 'cliente']));
        $falso = self::b64((string)json_encode(['sub' => 1, 'rol' => 'administrador', 'iss' => 'ControlVet', 'exp' => time() + 999]));

        self::assertNull($jwt->decode("$h.$falso.$s"));
    }

    public function testAlgoritmoNoneEsRechazado(): void
    {
        $jwt = new Jwt(self::SECRET);
        $h = self::b64('{"typ":"JWT","alg":"none"}');
        $p = self::b64((string)json_encode(['sub' => 1, 'rol' => 'administrador', 'iss' => 'ControlVet', 'exp' => time() + 999]));

        self::assertNull($jwt->decode("$h.$p."));
    }

    public function testTokenDeOtroSecretoEsRechazado(): void
    {
        $otro = new Jwt(str_repeat('x', 40));
        self::assertNull((new Jwt(self::SECRET))->decode($otro->encode(['sub' => 1, 'rol' => 'administrador'])));
    }

    public function testSecretoCortoNoSePermite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Jwt('corto');
    }
}
