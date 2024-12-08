<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Mock;
use Lav45\MockServer\Domain\Model\Response;
use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Domain\Model\Response\HttpMethod;
use Lav45\MockServer\Domain\Model\WebHook;
use Lav45\MockServer\Domain\Model\WebHooks;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;
use Lav45\MockServer\Infrastructure\Repository\Factory\BodyFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\DelayFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\HeadersFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\ItemsFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\MethodFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\StatusFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\UrlFactory;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PaginatorException;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

final readonly class DataMapper
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function toModel(array $data, Request $request): Mock
    {
        return new Mock(
            response: $this->createResponse($data, $request),
            webHooks: $this->createWebHooks($data),
        );
    }

    protected function createWebHooks(array $data): WebHooks
    {
        $items = [];
        $webHooks = ArrayHelper::getValue($data, 'webhooks', []);
        foreach ($webHooks as $webHook) {
            $items[] = $this->createWebHookItem($webHook);
        }
        return new WebHooks(...$items);
    }

    protected function createResponse(array $data, Request $request): Response
    {
        $response = ArrayHelper::getValue($data, 'response', []);
        if (isset($response['proxy'])) {
            return $this->createResponseProxy($response, $request);
        }
        if (isset($response['data'])) {
            return $this->createResponseCollection($response, $request);
        }
        return $this->createResponseContent($response, $request);
    }

    private function createResponseCollection(array $data, Request $request): Response
    {
        $start = new Response\Start($request->start);

        $delay = (new DelayFactory($this->parser))->create($data, 'delay');

        $status = (new StatusFactory($this->parser))->create($data, 'data.status');

        $dataItems = (new ItemsFactory($this->parser))->from($data, 'data.json', 'data.file');

        $pageParam = ArrayHelper::getValue($data, 'data.pagination.pageParam', 'page');
        $pageSizeParam = ArrayHelper::getValue($data, 'data.pagination.pageSizeParam', 'per-page');
        $defaultPageSize = ArrayHelper::getValue($data, 'data.pagination.defaultPageSize', 20);

        $pageSize = (int)ArrayHelper::getValue($request->get, $pageSizeParam, $defaultPageSize);
        $currentPage = (int)(ArrayHelper::getValue($request->get, $pageParam) ?: 1);

        $dataProvider = (new OffsetPaginator(new IterableDataReader($dataItems)))
            ->withPageSize($pageSize)
            ->withCurrentPage($currentPage);

        try {
            $items = $dataProvider->read();
            $items = \iterator_to_array($items);
            $items = \array_values($items);
        } catch (PaginatorException) {
            $items = [];
        }

        $totalItems = $dataProvider->getTotalItems();
        $currentPage = $dataProvider->getCurrentPage();
        $totalPages = $dataProvider->getTotalPages();
        $pageSize = $items ? $dataProvider->getCurrentPageSize() : 0;

        $parser = $this->parser->withData([
            'response' => [
                'data' => [
                    'items' => $items,
                    'pagination' => [
                        'totalItems' => $totalItems,
                        'currentPage' => $currentPage,
                        'totalPages' => $totalPages,
                        'pageSize' => $pageSize,
                    ],
                ],
            ],
        ]);

        $headers = (new HeadersFactory(
            parser: $parser,
            withJson: true,
        ))->create(
            data: $data,
            path: 'data.headers',
        );

        $result = ArrayHelper::getValue($data, 'data.result', '{{response.data.items}}');
        $result = $parser->replace($result);
        $body = Body::new($result);

        return new Response\Content(
            start: $start,
            delay: $delay,
            status: $status,
            headers: $headers,
            body: $body,
        );
    }

    private function createResponseContent(array $data, Request $request): Response
    {
        $start = new Response\Start($request->start);

        $delay = (new DelayFactory($this->parser))->create($data, 'delay');

        $status = (new StatusFactory($this->parser))->create($data, 'content.status');

        $headers = (new HeadersFactory(
            parser: $this->parser,
            withJson: isset($data['content']['json']),
        ))->create(
            data: $data,
            path: 'content.headers',
        );

        $body = (new BodyFactory($this->parser))->fromContent($data, 'content.text', 'content.json');

        return new Response\Content(
            start: $start,
            delay: $delay,
            status: $status,
            headers: $headers,
            body: $body,
        );
    }

    private function createResponseProxy(array $data, Request $request): Response
    {
        $start = new Response\Start($request->start);

        $delay = (new DelayFactory($this->parser))->create($data, 'delay');

        $url = (new UrlFactory($this->parser))->create($data, 'proxy.url', $request->get);

        $method = new HttpMethod($request->method);

        $withJson = isset($data['proxy']['content']) && \is_array($data['proxy']['content']);

        $headers = (new HeadersFactory(
            parser: $this->parser,
            withJson: $withJson,
            appendHeaders: $request->headers,
        ))->create(
            data: $data,
            path: 'proxy.headers',
            optionPath: 'proxy.options.headers',
        );

        if (isset($data['proxy']['content'])) {
            $body = (new BodyFactory($this->parser))->from($data, 'proxy.content');
        } else {
            $body = Body::fromText($request->body);
        }

        return new Response\Proxy(
            start: $start,
            delay: $delay,
            url: $url,
            method: $method,
            headers: $headers,
            body: $body,
        );
    }

    private function createWebHookItem(array $item): WebHook
    {
        $delay = (new DelayFactory($this->parser))->create($item, 'delay');

        $url = (new UrlFactory($this->parser))->create($item, 'url');

        $method = (new MethodFactory($this->parser))->create($item, 'method', 'POST');

        $json = ArrayHelper::getValue($item, 'options.json'); // deprecated
        if ($json === null) {
            $json = ArrayHelper::getValue($item, 'json');
        }
        $json = $this->parser->replace($json);

        $headers = (new HeadersFactory(
            parser: $this->parser,
            withJson: isset($json),
        ))->create(
            data: $item,
            path: 'headers',
            optionPath: 'options.headers', // deprecated
        );

        $text = ArrayHelper::getValue($item, 'options.text'); // deprecated
        if ($text === null) {
            $text = ArrayHelper::getValue($item, 'text');
        }
        $text = $this->parser->replace($text);

        if ($json) {
            $body = Body::fromJson($json);
        } else {
            $body = Body::fromText($text);
        }

        return new WebHook(
            delay: $delay,
            url: $url,
            method: $method,
            headers: $headers,
            body: $body,
        );
    }
}
