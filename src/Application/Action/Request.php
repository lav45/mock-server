<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Action;

use lav45\MockServer\Application\DTO\Request as RequestDTO;
use lav45\MockServer\Application\DTO\Response as ResponseDTO;
use lav45\MockServer\Application\Factory\Handler\Response as ResponseHandlerFactory;
use lav45\MockServer\Application\Service\Webhook as WebhookService;
use lav45\MockServer\Domain\Entity\Mock;

final readonly class Request
{
    public function __construct(
        private WebhookService         $webhookService,
        private ResponseHandlerFactory $responseHandler,
    ) {}

    public function execute(RequestDTO $request, Mock $mock): ResponseDTO
    {
        $response = $this->responseHandler->create($mock->response)->handle($request);
        $this->webhookService->send(...$mock->webhooks);
        return $response;
    }
}
