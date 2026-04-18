<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Amp\Http\Server\Request;
use Lav45\MockServer\Domain\Response\ContentResponse;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Parser\VariableParser;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PaginatorException;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

final readonly class CollectionFactory
{
    public const string TYPE = 'data';

    public function create(Request $request, VariableParser $parser, array $data): ContentResponse
    {
        $factory = new DataBuilder($parser, $data);

        $dataItems = $factory->createItems();

        $pageParam = $data['pagination']['pageParam'] ?? 'page';
        $pageSizeParam = $data['pagination']['pageSizeParam'] ?? 'per-page';
        $defaultPageSize = $data['pagination']['defaultPageSize'] ?? 20;

        $get = new RequestAdapter($request)->getQuery();

        $pageSize = (int)($get[$pageSizeParam] ?? $defaultPageSize);
        $currentPage = (int)(($get[$pageParam] ?? null) ?: 1);

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

        $headers = new DataBuilder($parser, $data)->createHeaders(true);

        $result = $data['result'] ?? '{{response.items}}';
        $result = $parser->replace($result);
        $body = Body::new($result);

        return new ContentResponse(
            status: $factory->createStatus(),
            headers: $headers,
            body: $body,
        );
    }
}
