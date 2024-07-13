<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Server\Controller;

use Amp\Http\Server\Response;
use Lav45\MockServer\Test\Functional\Server\Component\Storage as StorageComponent;
use Lav45\MockServer\Test\Functional\Server\Controller\Data\Request;

final readonly class Storage
{
    public function __construct(private StorageComponent $storage) {}

    public function create(string $method, array $get, array $post, array $headers): Response
    {
        $dto = new Request($method, $get, $post, $headers);
        $this->storage->add($dto);
        return new Response();
    }

    public function flush(): Response
    {
        $headers = ["content-type" => "application/json"];
        $data = $this->storage->all();
        $data = \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->storage->flush();

        return new Response(
            headers: $headers,
            body: $data,
        );
    }
}
