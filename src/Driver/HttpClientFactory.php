<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Lav45\MockServer\Engine\HttpClient as HttpClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class HttpClientFactory
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
        private int             $retryLimit = 3,
    ) {}

    public function create(): HttpClientInterface
    {
        $tls = new ClientTlsContext('')
            ->withoutPeerVerification()
            ->withSecurityLevel(0);

        $context = new ConnectContext()
            ->withTlsContext($tls);

        $factory = new DefaultConnectionFactory(connectContext: $context);

        $pool = new UnlimitedConnectionPool($factory);

        $client = new HttpClientBuilder()
            ->intercept(new Interceptor\Logger($this->logger))
            ->intercept(new Interceptor\RetryRequests($this->retryLimit))
            ->usingPool($pool)
            ->build();

        return new HttpClient($client);
    }
}
