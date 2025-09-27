<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Response;
use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Infrastructure\Parser\DataParser;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PaginatorException;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

final readonly class ResponseCollectionHandler implements Handler
{
    public const string TYPE = 'data';

    public function handle(DataParser $parser, array $data, Request $request): Response
    {
        $data = $data['response'] ?? [];

        $factory = new AttributeFactory($parser, $data);

        $start = new Response\Start($request->start);
        $delay = $factory->createDelay();
        $status = $factory->createStatus();
        $dataItems = $factory->createItems();

        $pageParam = $data['pagination']['pageParam'] ?? 'page';
        $pageSizeParam = $data['pagination']['pageSizeParam'] ?? 'per-page';
        $defaultPageSize = $data['pagination']['defaultPageSize'] ?? 20;

        $pageSize = (int)($request->get[$pageSizeParam] ?? $defaultPageSize);
        $currentPage = (int)(($request->get[$pageParam] ?? null) ?: 1);

        $dataProvider = new OffsetPaginator(new IterableDataReader($dataItems))
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

        $parser = $parser->withData([
            'response' => [
                'items' => $items,
                'pagination' => [
                    'totalItems' => $totalItems,
                    'currentPage' => $currentPage,
                    'totalPages' => $totalPages,
                    'pageSize' => $pageSize,
                ],
            ],
        ]);

        $headers = new AttributeFactory($parser, $data)->createHeaders(true);

        $result = $data['result'] ?? '{{response.items}}';
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
