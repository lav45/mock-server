<?php declare(strict_types=1);

namespace lav45\MockServer\test\server\controllers;

use Amp\Http\Server\Response;
use lav45\MockServer\test\server\controllers\dto\RequestDTO;

class ContentController
{
    public function index(string $method, array $get, array $post, array $headers): Response
    {
        $dto = new RequestDTO($method, $get, $post, $headers);
        return new Response(
            headers: ["content-type" => "application/json"],
            body: json_encode($dto, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}