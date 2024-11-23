<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Query\Request;

final readonly class Handler
{
    public function __construct(
        private WebHook        $webHookHandler,
        private ResponseFabric $responseFabric,
        private Repository     $repository,
    ) {}

    public function execute(Request $request): Response
    {
        $mock = $this->repository->find($request);

        $response = $this->responseFabric->create($mock->response)->execute();

        $this->webHookHandler->send(...$mock->webHooks->items);

        return $response;
    }
}
