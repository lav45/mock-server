<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\DataFactory\RequestAdapter;
use Lav45\MockServer\Domain\Response\ContentResponse;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Extension\Collection\Repository\ArrayDataRepository;
use Lav45\MockServer\Extension\Collection\Repository\CursorQuery;
use Lav45\MockServer\Extension\Collection\Repository\DataRepositoryInterface;
use Lav45\MockServer\Extension\Collection\Repository\FileDataRepository;
use Lav45\MockServer\Extension\Collection\Repository\OffsetQuery;
use Lav45\MockServer\Parser\VariableParser;

final readonly class CollectionFactory
{
    private const string TYPE = 'data';

    private const string PAGINATION_TYPE_OFFSET = 'offset';
    private const string PAGINATION_TYPE_KEYSET = 'keyset';
    private const string PAGINATION_TYPE_ITERATOR = 'iterator';

    public function __construct(
        private DataBuilder $dataBuilder,
    ) {}

    public function has(array $data): bool
    {
        return isset($data['type']) && $data['type'] === self::TYPE;
    }

    public function create(ServerRequest $request, VariableParser $parser, array $data): ContentResponse
    {
        $factory = $this->dataBuilder->withData($data)->withParser($parser);

        $repository = $this->repository($parser, $data);
        $pagination = $data['pagination'] ?? [];
        $get = new RequestAdapter($request)->getQuery();

        $type = $pagination['type'] ?? self::PAGINATION_TYPE_OFFSET;
        $response = match ($type) {
            self::PAGINATION_TYPE_KEYSET => $this->keyset($get, KeysetPagination::fromArray($pagination), $repository),
            self::PAGINATION_TYPE_ITERATOR => $this->iterator($get, IteratorPagination::fromArray($pagination), $repository),
            self::PAGINATION_TYPE_OFFSET => $this->offset($get, OffsetPagination::fromArray($pagination), $repository),
        };

        $response['items'] = $parser->replace($response['items']);

        $parser = $parser->withData([
            'response' => $response,
        ]);

        $factory = $factory->withParser($parser);
        $headers = $factory->createHeaders(withJson: true);

        $result = $data['result'] ?? '{{response.items}}';
        $result = $parser->replace($result);
        $body = Body::new($result);

        return new ContentResponse(
            status: $factory->createStatus(),
            headers: $headers,
            body: $body,
        );
    }

    private function repository(VariableParser $parser, array $data): DataRepositoryInterface
    {
        if (isset($data['file'])) {
            return new FileDataRepository(
                (string)$parser->replace($data['file']),
            );
        }
        return new ArrayDataRepository($data['items'] ?? []);
    }

    private function offset(array $get, OffsetPagination $pagination, DataRepositoryInterface $repository): array
    {
        $query = new OffsetQuery(
            page: (int)(($get[$pagination->pageParam] ?? null) ?: 1),
            limit: (int)($get[$pagination->pageSizeParam] ?? $pagination->defaultPageSize),
        );

        $page = $repository->offsetPage($query);

        return [
            'items' => $page->items,
            'pagination' => [
                'totalItems' => $page->totalItems,
                'currentPage' => $page->currentPage,
                'totalPages' => $page->totalPages,
                'pageSize' => $page->pageSize,
            ],
        ];
    }

    private function keyset(array $get, KeysetPagination $pagination, DataRepositoryInterface $repository): array
    {
        $query = new CursorQuery(
            primaryKey: $pagination->primaryKey,
            after: isset($get[$pagination->afterParam]) ? (string)$get[$pagination->afterParam] : null,
            before: isset($get[$pagination->beforeParam]) ? (string)$get[$pagination->beforeParam] : null,
            limit: (int)($get[$pagination->limitParam] ?? $pagination->defaultPageSize),
        );

        $page = $repository->cursorPage($query);
        $items = $page->items;
        $count = \count($items);
        $primaryKey = $pagination->primaryKey;

        return [
            'items' => $items,
            'pagination' => [
                'next' => $page->hasNext ? (string)($items[$count - 1][$primaryKey] ?? '') : null,
                'prev' => $page->hasPrev ? (string)($items[0][$primaryKey] ?? '') : null,
                'hasNext' => $page->hasNext,
                'hasPrev' => $page->hasPrev,
                'pageSize' => $count,
            ],
        ];
    }

    private function iterator(array $get, IteratorPagination $pagination, DataRepositoryInterface $repository): array
    {
        $iterator = isset($get[$pagination->iteratorParam]) ? (string)$get[$pagination->iteratorParam] : '';

        $before = $after = null;
        if ($iterator !== '') {
            if (\str_starts_with($iterator, '-')) {
                $before = \substr($iterator, 1);
            } else {
                $after = $iterator;
            }
        }

        $query = new CursorQuery(
            primaryKey: $pagination->primaryKey,
            after: $after,
            before: $before,
            limit: (int)($get[$pagination->limitParam] ?? $pagination->defaultPageSize),
        );

        $page = $repository->cursorPage($query);
        $items = $page->items;
        $count = \count($items);
        $primaryKey = $pagination->primaryKey;

        return [
            'items' => $items,
            'pagination' => [
                'next' => $page->hasNext ? (string)($items[$count - 1][$primaryKey] ?? '') : null,
                'prev' => $page->hasPrev ? '-' . ($items[0][$primaryKey] ?? '') : null,
                'hasNext' => $page->hasNext,
                'hasPrev' => $page->hasPrev,
                'pageSize' => $count,
            ],
        ];
    }
}
