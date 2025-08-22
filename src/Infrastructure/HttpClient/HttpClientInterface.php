<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\HttpClient;

use Amp\Http\Client\Response;

interface HttpClientInterface
{
    public function request(
        string      $uri,
        string      $method = 'GET',
        string|null $body = null,
        array|null  $headers = null,
        string|null $logLabel = null,
    ): Response;
}
