<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Amp\Http\Client\Response;

interface HttpClient
{
    public function request(
        string      $uri,
        string      $method = 'GET',
        array|null  $headers = null,
        string|null $body = null,
    ): Response;
}
