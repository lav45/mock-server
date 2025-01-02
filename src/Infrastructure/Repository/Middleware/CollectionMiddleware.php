<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Middleware;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Response;
use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;
use Lav45\MockServer\Infrastructure\Repository\Factory\DelayFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\HeadersFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\ItemsFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\StatusFactory;
use Psr\Log\LoggerInterface;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PaginatorException;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

final readonly class CollectionMiddleware implements Middleware
{
    public function __construct(
        private Parser          $parser,
        private LoggerInterface $logger,
    ) {}

    public function handle(array $data, Request $request, \Closure $next): Response
    {
        $response = ArrayHelper::getValue($data, 'response', []);
        if (isset($response['data'])) {
            return $this->createResponseCollection($response, $request);
        }
        return $next($data, $request);
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
            logger: $this->logger,
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
}
