<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\HttpClient;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class Factory
{
    public static function create(
        LoggerInterface $logger = new NullLogger(),
    ): HttpClient {
        $tls = new ClientTlsContext('')
            ->withoutPeerVerification()
            ->withSecurityLevel(0);

        $context = new ConnectContext()
            ->withTlsContext($tls);

        $factory = new DefaultConnectionFactory(connectContext: $context);

        $pool = new UnlimitedConnectionPool($factory);

        $client = new HttpClientBuilder()
            ->intercept(new Interceptor\Logger($logger, Level::Info, Level::Error))
            ->usingPool($pool)
            ->build();

        return new HttpClient(
            client: $client,
        );
    }
}
