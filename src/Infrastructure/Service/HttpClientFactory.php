<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Service;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Lav45\MockServer\Infrastructure\Service\HttpClient as HttpClientWrapper;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class HttpClientFactory
{
    public static function create(
        LoggerInterface $logger = new NullLogger(),
        mixed           $logLevelOk = Level::Info,
        mixed           $logLevelError = Level::Error,
    ): HttpClientWrapper {
        $tls = (new ClientTlsContext(''))
            ->withoutPeerVerification()
            ->withSecurityLevel(0);

        $context = (new ConnectContext())
            ->withTlsContext($tls);

        $factory = new DefaultConnectionFactory(null, $context);

        $pool = new UnlimitedConnectionPool($factory);

        $client = (new HttpClientBuilder())
            ->usingPool($pool)
            ->build();

        return new HttpClientWrapper(
            client: $client,
            logger: $logger,
            logLevelOk: $logLevelOk,
            logLevelError: $logLevelError,
        );
    }
}
