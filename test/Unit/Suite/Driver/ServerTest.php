<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Driver;

use Lav45\MockServer\Driver\Server;
use Lav45\MockServer\Driver\Tls;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use PHPUnit\Framework\TestCase;

final class ServerTest extends TestCase
{
    public function testConstructWithDefaultErrorHandler(): void
    {
        $server = new Server(new FakeLogger());

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testExposeAcceptsHostAndPort(): void
    {
        $server = new Server(new FakeLogger());
        $server->expose('0.0.0.0', 8080);

        $this->assertInstanceOf(Server::class, $server);
    }

    public function testExposeTlsAcceptsHostAndTls(): void
    {
        $server = new Server(new FakeLogger());
        $server->exposeTls('0.0.0.0', new Tls(8443, '/app/etc/ssl/cert.pem', '/app/etc/ssl/key.pem'));

        $this->assertInstanceOf(Server::class, $server);
    }
}
