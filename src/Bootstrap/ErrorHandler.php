<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use Lav45\MockServer\Engine\Http\ServerResponse;

final readonly class ErrorHandler
{
    public function handleError(int $status, string|null $reason = null): ServerResponse
    {
        $response = new ServerResponse(
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
