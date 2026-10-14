<?php

declare(strict_types=1);

namespace ControlVet\Kernel\Tests;

use ControlVet\Kernel\Http\HttpException;
use ControlVet\Kernel\Http\Request;
use ControlVet\Kernel\Http\Response;
use ControlVet\Kernel\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDespachaRutaConParametroNumerico(): void
    {
        $router = new Router();
        $router->get('/citas/{id}', fn(Request $r) => Response::json(['id' => $r->intParam('id')]));

        self::assertSame(['id' => 42], $router->dispatch(new Request('GET', '/citas/42'))->decoded());
    }

    public function testParametroNoNumericoNoCoincide(): void
    {
        $router = new Router();
        $router->get('/citas/{id}', fn() => Response::noContent());

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);
        $router->dispatch(new Request('GET', '/citas/1 OR 1=1'));
    }

    public function testMetodoNoPermitidoRetorna405(): void
    {
        $router = new Router();
        $router->get('/productos', fn() => Response::noContent());

        try {
            $router->dispatch(new Request('DELETE', '/productos'));
            self::fail('Se esperaba HttpException');
        } catch (HttpException $e) {
            self::assertSame(405, $e->status());
        }
    }
}
