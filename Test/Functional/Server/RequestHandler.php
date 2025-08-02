<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Server;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\Application\Query\Request\Request as RequestData;
use Lav45\MockServer\Presenter\Service\RequestFactory;
use Lav45\MockServer\Test\Functional\Server\Component\Storage;
use Lav45\MockServer\Test\Functional\Server\Controller\Content as ContentController;
use Lav45\MockServer\Test\Functional\Server\Controller\Storage as StorageController;

final readonly class RequestHandler implements \Amp\Http\Server\RequestHandler
{
    private StorageController $storageController;

    private ContentController $contentController;

    public function __construct(Storage $storage)
    {
        $this->storageController = new StorageController($storage);
        $this->contentController = new ContentController();
    }

    public function handleRequest(Request $request): Response
    {
        $requestData = RequestFactory::create($request);

        return match ($request->getUri()->getPath()) {
            '/' => new Response(body: 'OK'),
            '/storage' => $this->runStorageController($requestData),
            '/__storage' => $this->flushStorageController($requestData),
            '/content' => $this->runContentController($requestData),
            default => new Response(status: 404),
        };
    }

    protected function runStorageController(RequestData $request): Response
    {
        return $this->storageController->create(
            $request->method,
            $request->get,
            $request->post,
            $request->headers,
        );
    }

    protected function flushStorageController(RequestData $request): Response
    {
        return match ($request->method) {
            'GET' => $this->storageController->flush(),
        };
    }

    protected function runContentController(RequestData $request): Response
    {
        return $this->contentController->index(
            $request->method,
            $request->get,
            $request->post,
            $request->headers,
        );
    }
}
