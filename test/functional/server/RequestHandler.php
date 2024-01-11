<?php declare(strict_types=1);

namespace lav45\MockServer\test\functional\server;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use lav45\MockServer\Request\Wrapper\RequestWrapper;
use lav45\MockServer\test\functional\server\components\Storage;
use lav45\MockServer\test\functional\server\controllers\ContentController;
use lav45\MockServer\test\functional\server\controllers\StorageController;

final readonly class RequestHandler implements \Amp\Http\Server\RequestHandler
{
    public function __construct(private Storage $storage)
    {
    }

    public function handleRequest(Request $request): Response
    {
        $requestWrapper = new RequestWrapper($request);

        return match ($request->getUri()->getPath()) {
            '/' => new Response(body: 'OK'),
            '/storage' => $this->runStorageController($requestWrapper),
            '/__storage' => $this->runStorageController($requestWrapper, true),
            '/content' => $this->runContentController($requestWrapper),
            default => new Response(status: 404)
        };
    }

    protected function runStorageController(RequestWrapper $request, bool $control = false): Response
    {
        $controller = new StorageController($this->storage);

        if ($control === false) {
            return $controller->create(
                $request->getMethod(),
                $request->get(),
                $request->post(),
                $request->getHeaders()
            );
        }

        return match ($request->getMethod()) {
            'GET' => $controller->flush()
        };
    }

    protected function runContentController(RequestWrapper $request): Response
    {
        return (new ContentController())->index(
            $request->getMethod(),
            $request->get(),
            $request->post(),
            $request->getHeaders()
        );
    }
}
