<?php declare(strict_types=1);

namespace Lav45\MockServer\Router;

use Lav45\MockServer\Domain\Mock;
use Lav45\MockServer\Http\RequestData;
use Lav45\MockServer\Router\MockFactory\RequestParserContext;
use Lav45\MockServer\Router\MockFactory\ResponseFactoryResolver;
use Lav45\MockServer\Router\MockFactory\WebHooksFactoryInterface;

final readonly class MockFactory implements MockFactoryInterface
{
    public function __construct(
        private RequestParserContext     $parserContext,
        private WebHooksFactoryInterface $webHooksFactory,
        private ResponseFactoryResolver  $responseFactoryResolver,
    ) {}

    public function create(RequestData $request, array $data): Mock
    {
        $parser = $this->parserContext->create($request, $data);

        return new Mock(
            response: $this->responseFactoryResolver->resolve($data)
                ->create(
                    parser: $parser,
                    data: $data['response'] ?? [],
                    request: $request,
                ),
            webHooks: $this->webHooksFactory
                ->create(
                    parser: $parser,
                    data: $data['webhooks'] ?? [],
                ),
        );
    }
}
