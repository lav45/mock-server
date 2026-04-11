<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Components;

use Amp\Http\Server\Driver\Client;
use Amp\Socket\SocketAddress;
use Amp\Socket\TlsInfo;

final class FakeHttpDriverClient implements Client
{
    public function getId(): int
    {
        return 1;
    }

    public function getLocalAddress(): SocketAddress
    {
        throw new \BadMethodCallException('Not implemented in FakeHttpDriverClient');
    }

    public function getRemoteAddress(): SocketAddress
    {
        throw new \BadMethodCallException('Not implemented in FakeHttpDriverClient');
    }

    public function getTlsInfo(): TlsInfo|null
    {
        return null;
    }

    public function isClosed(): bool
    {
        return false;
    }

    public function close(): void {}

    public function onClose(\Closure $onClose): void {}
}
