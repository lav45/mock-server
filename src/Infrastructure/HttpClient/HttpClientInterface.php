<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\HttpClient;

use Amp\Http\Client\Response;

interface HttpClientInterface
{
    public function request(
        string      $uri,
        string      $method = 'GET',
        null|string $body = null,
        null|array  $headers = null,
        null|string $logLabel = null,
    ): Response;
}
