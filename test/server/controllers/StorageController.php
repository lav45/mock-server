<?php declare(strict_types=1);

namespace lav45\MockServer\test\server\controllers;

use Amp\Http\Server\Response;
use lav45\MockServer\test\server\components\Storage;
use lav45\MockServer\test\server\controllers\dto\RequestDTO;

final readonly class StorageController
{
    public function __construct(private Storage $storage)
    {
    }

    public function create(string $method, array $get, array $post, array $headers): Response
    {
        $dto = new RequestDTO($method, $get, $post, $headers);
        $this->storage->add($dto);
        return new Response();
    }

    public function index(): Response
    {
        $headers = ["content-type" => "application/json"];
        $data = $this->storage->all();
        $data = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return new Response(
            headers: $headers,
            body: $data
        );
    }

    public function delete(): Response
    {
        $this->storage->flush();
        return new Response();
    }
}