<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Handler;

use Lav45\MockServer\Application\Data\Request as RequestData;
use Lav45\MockServer\Application\Data\Response as ResponseData;
use Lav45\MockServer\Application\Handler\Response as ResponseHandler;
use Lav45\MockServer\Domain\Entity\Response\Collection as CollectionEntity;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PaginatorException;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

final readonly class Collection implements ResponseHandler
{
    use DelayTrait;

    public function __construct(private CollectionEntity $data) {}

    public function handle(RequestData $request): ResponseData
    {
        $pageSize = (int)ArrayHelper::getValue($request->get, $this->data->pagination->pageSizeParam, $this->data->pagination->defaultPageSize);
        $currentPage = (int)(ArrayHelper::getValue($request->get, $this->data->pagination->pageParam) ?: 1);

        $dataReader = new IterableDataReader($this->data->items);
        $dataProvider = (new OffsetPaginator($dataReader))
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

        $data = [
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
        ];

        $status = $this->data->status->value;
        $headers = $this->data->headers->withData($data)->create()->all();
        $body = $this->data->body->withData($data)->create()->toString();

        $this->delay($request->start, $this->data->delay->value);

        return new ResponseData(
            status: $status,
            headers: $headers,
            body: $body,
        );
    }
}
