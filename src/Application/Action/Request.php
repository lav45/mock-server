<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Action;

use lav45\MockServer\Application\Data\Request as RequestData;
use lav45\MockServer\Application\Data\Response as ResponseData;
use lav45\MockServer\Application\Factory\Handler\Response as ResponseHandlerFactory;
use lav45\MockServer\Application\Service\Webhook as WebhookService;
use lav45\MockServer\Domain\Entity\Mock;

final readonly class Request
{
    public function __construct(
        private WebhookService         $webhookService,
        private ResponseHandlerFactory $responseHandler,
    ) {}

    public function execute(RequestData $request, Mock $mock): ResponseData
    {
        $response = $this->responseHandler->create($mock->response)->handle($request);
        $this->webhookService->send(...$mock->webhooks);
        return $response;
    }
}
