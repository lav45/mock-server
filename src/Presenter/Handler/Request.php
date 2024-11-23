<?php declare(strict_types=1);

namespace Lav45\MockServer\Presenter\Handler;

use Amp\Http\Server\Request as HttpRequest;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response as HttpResponse;
use Lav45\MockServer\Application\Query\Request\Handler;
use Lav45\MockServer\Application\Query\Request\Repository;
use Lav45\MockServer\Application\Query\Request\ResponseFabric;
use Lav45\MockServer\Application\Query\Request\WebHook;
use Lav45\MockServer\Presenter\Service\RequestFactory;

final readonly class Request implements RequestHandler
{
    public function __construct(
        private Repository     $repository,
        private WebHook        $webHookHandler,
        private ResponseFabric $responseFabric,
    ) {}

    public function handleRequest(HttpRequest $request): HttpResponse
    {
        $requestData = RequestFactory::create($request);

        $responseData = (new Handler(
            webHookHandler: $this->webHookHandler,
            responseFabric: $this->responseFabric,
            repository: $this->repository,
        ))->execute($requestData);

        return new HttpResponse(
            status: $responseData->status,
            headers: $responseData->headers,
            body: $responseData->body,
        );
    }
}
