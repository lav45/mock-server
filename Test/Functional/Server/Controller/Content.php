<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Server\Controller;

use Amp\Http\Server\Response;
use Lav45\MockServer\Test\Functional\Server\Controller\Data\Request;

class Content
{
    public function index(string $method, array $get, array $post, array $headers): Response
    {
        $dto = new Request($method, $get, $post, $headers);
        $responseBody = \json_encode($dto, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $responseHeaders = [
            'content-type' => 'application/json',
            'authorization' => 'Bearer eyJhbGciOiJSUzI1NiJ9',
        ];

        return new Response(
            headers: $responseHeaders,
            body: $responseBody,
        );
    }
}
