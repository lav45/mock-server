<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Amp\Cluster\Cluster;
use Amp\Http\Server\Driver\DefaultHttpDriverFactory;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket;
use Lav45\MockServer\Driver\RequestHandler as AmpRequestHandler;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Psr\Log\LoggerInterface;

final class Server
{
    /** @var list<array{Socket\InternetAddress, Socket\BindContext|null}> */
    private array $addresses = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly int             $maxBufferSize = 33_554_432,
        private readonly ErrorHandler    $errorHandler = new ErrorHandler(),
    ) {}

    public function expose(string $host, int $port): void
    {
        $bindContext = new Socket\BindContext()->withTcpNoDelay();
        $this->addresses[] = [new Socket\InternetAddress($host, $port), $bindContext];
    }

    public function exposeTls(string $host, Tls $tls): void
    {
        $certificate = new Socket\Certificate($tls->cert, $tls->key, $tls->passphrase);
        $tlsContext = new Socket\ServerTlsContext()->withDefaultCertificate($certificate);
        $bindContext = new Socket\BindContext()
            ->withTlsContext($tlsContext)
            ->withTcpNoDelay();
        $this->addresses[] = [new Socket\InternetAddress($host, $tls->port), $bindContext];
    }

    /** @codeCoverageIgnore */
    public function run(RequestHandler $handler): void
    {
        $requestHandler = new AmpRequestHandler($handler, $this->maxBufferSize, $this->errorHandler);
        $serverSocketFactory = Cluster::getServerSocketFactory();
        $clientFactory = new SocketClientFactory($this->logger);
        $httpDriverFactory = new DefaultHttpDriverFactory($this->logger, bodySizeLimit: \PHP_INT_MAX);
        $server = new SocketHttpServer(
            $this->logger,
            $serverSocketFactory,
            $clientFactory,
            httpDriverFactory: $httpDriverFactory,
        );
        foreach ($this->addresses as [$address, $bindContext]) {
            $server->expose($address, $bindContext);
        }
        $server->start($requestHandler, $this->errorHandler);

        Cluster::awaitTermination();

        $server->stop();
    }
}
