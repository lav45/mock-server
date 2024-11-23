<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Server;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\Presenter\Service\Request as RequestWrapper;
use Lav45\MockServer\Test\Functional\Server\Component\Storage;
use Lav45\MockServer\Test\Functional\Server\Controller\Content as ContentController;
use Lav45\MockServer\Test\Functional\Server\Controller\Storage as StorageController;

final readonly class RequestHandler implements \Amp\Http\Server\RequestHandler
{
    public function __construct(private Storage $storage) {}

    public function handleRequest(Request $request): Response
    {
        $requestWrapper = new RequestWrapper($request);

        return match ($request->getUri()->getPath()) {
            '/' => new Response(body: 'OK'),
            '/storage' => $this->runStorageController($requestWrapper),
            '/__storage' => $this->runStorageController($requestWrapper, true),
            '/content' => $this->runContentController($requestWrapper),
            default => new Response(status: 404),
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
                $request->getHeaders(),
            );
        }

        return match ($request->getMethod()) {
            'GET' => $controller->flush(),
        };
    }

    protected function runContentController(RequestWrapper $request): Response
    {
        return (new ContentController())->index(
            $request->getMethod(),
            $request->get(),
            $request->post(),
            $request->getHeaders(),
        );
    }
}
