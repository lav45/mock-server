<?php declare(strict_types=1);

namespace lav45\MockServer\Infrastructure\Factory;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use lav45\MockServer\Infrastructure\Wrapper\HttpClient as HttpClientWrapper;
use Monolog\Level;
use Psr\Log\LoggerInterface;

final readonly class HttpClient
{
    public static function create(
        LoggerInterface|null $logger = null,
        mixed                $logLevelOk = Level::Info,
        mixed                $logLevelError = Level::Error
    ): HttpClientWrapper
    {
        $tls = (new ClientTlsContext(''))
            ->withoutPeerVerification()
            ->withSecurityLevel(0);

        $context = (new ConnectContext)
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