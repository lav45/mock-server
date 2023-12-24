<?php declare(strict_types=1);

namespace lav45\MockServer\Mock;

use lav45\MockServer\Component\DTObject;

class Mock extends DTObject
{
    public array $env = [];
    private Request $request;
    private Response $response;
    private array $webhooks = [];

    public function getRequest(): Request
    {
        return $this->request ??= new Request();
    }

    public function setRequest(array $request): void
    {
        $this->request = new Request($request);
    }

    public function getResponse(): Response
    {
        return $this->response ??= new Response();
    }

    public function setResponse(array $response): void
    {
        $this->response = new Response($response);
    }

    /**
     * @return Webhook[]
     */
    public function getWebhooks(): array
    {
        return $this->webhooks;
    }

    public function setWebhooks(array $webhooks): void
    {
        $this->webhooks = [];
        foreach ($webhooks as $webhook) {
            $this->webhooks[] = new Webhook($webhook);
        }
    }
}
