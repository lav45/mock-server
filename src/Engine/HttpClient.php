<?php declare(strict_types=1);

namespace Lav45\MockServer\Engine;

use Lav45\MockServer\Engine\Http\ClientResponse;

interface HttpClient
{
    public function withLabel(string $label): self;

    public function request(
        string      $uri,
        string      $method = 'GET',
        array|null  $headers = null,
        string|null $body = null,
    ): ClientResponse;
}
