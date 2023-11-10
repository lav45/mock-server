<?php declare(strict_types=1);

namespace lav45\MockServer;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpContent;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Psr\Http\Message\UriInterface;

class HttpClient
{
    private \Amp\Http\Client\HttpClient $client;

    public function build(): static
    {
        $this->client = $this->getBuilder()->build();
        return $this;
    }

    protected function getBuilder(): HttpClientBuilder
    {
        $tls = (new ClientTlsContext(''))
            ->withoutPeerVerification()
            ->withSecurityLevel(0);

        $context = (new ConnectContext)
            ->withTlsContext($tls);

        $factory = new DefaultConnectionFactory(null, $context);

        $pool = new UnlimitedConnectionPool($factory);

        return (new HttpClientBuilder())
            ->usingPool($pool);
    }

    public function request(
        UriInterface|string $url,
        string              $method = 'GET',
        array               $query = [],
        HttpContent|string  $body = '',
        array               $headers = []
    ): Response
    {
        $request = new Request($url, $method);
        $request->setBody($body);
        $request->setQueryParameters($query);
        $request->setHeaders($headers);

        return $this->client->request($request);
    }
}