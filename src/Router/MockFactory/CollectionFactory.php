<?php declare(strict_types=1);

namespace Lav45\MockServer\Router\MockFactory;

use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Domain\Mock\Response\Body;
use Lav45\MockServer\Http\RequestData;
use Lav45\MockServer\Parser\VariableParser;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PaginatorException;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

final readonly class CollectionFactory implements ResponseFactoryInterface
{
    public const string TYPE = 'data';

    public function create(VariableParser $parser, array $data, RequestData $request): Response
    {
        $factory = new AttributeBuilder($parser, $data);

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

        $headers = new AttributeBuilder($parser, $data)->createHeaders(true);

        $result = $data['result'] ?? '{{response.items}}';
        $result = $parser->replace($result);
        $body = Body::new($result);

        return new Response\ContentResponse(
            delay: $factory->createDelay(),
            status: $factory->createStatus(),
            headers: $headers,
            body: $body,
        );
    }
}
