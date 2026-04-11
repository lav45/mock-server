<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

final readonly class ErrorHandler implements \Amp\Http\Server\ErrorHandler
{
    public function handleError(int $status, string|null $reason = null, Request|null $request = null): Response
    {
        $response = new Response(
            headers: ['content-type' => 'application/json'],
        );

        $response->setStatus($status, $reason);

        $body = [
            'status' => $status,
            'message' => $response->getReason(),
        ];

        $response->setBody(
            \json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );

        return $response;
    }
}
