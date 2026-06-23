<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Amp\Cluster\Cluster;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Psr\Log\LoggerInterface;

final class Server
{
    /** @var list<Socket\InternetAddress> */
    private array $addresses = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ErrorHandler    $errorHandler = new ErrorHandler(),
    ) {}

    public function expose(string $host, int $port): void
    {
        $this->addresses[] = new Socket\InternetAddress($host, $port);
    }

    /** @codeCoverageIgnore */
    public function run(RequestHandler $handler): void
    {
        $requestHandler = new AmpRequestHandler($handler);
        $serverSocketFactory = Cluster::getServerSocketFactory();
        $clientFactory = new SocketClientFactory($this->logger);
        $server = new SocketHttpServer($this->logger, $serverSocketFactory, $clientFactory);
        foreach ($this->addresses as $address) {
            $server->expose($address);
        }
        $server->start($requestHandler, $this->errorHandler);

        Cluster::awaitTermination();

        $server->stop();
    }
}
