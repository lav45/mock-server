<?php declare(strict_types=1);

namespace lav45\MockServer\Request\Handler;

use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\Mock\Response\Data;
use lav45\MockServer\Request\Wrapper\RequestWrapper;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PaginatorException;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

final readonly class DataHandler implements RequestHandlerInterface
{
    public function __construct(
        private Data      $data,
        private EnvParser $parser
    )
    {
    }

    public function handleRequest(RequestWrapper $request): Response
    {
        $pagination = $this->data->getPagination();
        $pageSize = (int)$request->get($pagination->pageSizeParam, $pagination->defaultPageSize);
        $currentPage = (int)($request->get($pagination->pageParam) ?: 1);

        $dataReader = new IterableDataReader($this->data->getJson());
        $dataProvider = (new OffsetPaginator($dataReader))
            ->withPageSize($pageSize)
            ->withCurrentPage($currentPage);

        try {
            $items = $this->parser->replace($dataProvider->read());
            $items = array_values($items);
        } catch (PaginatorException) {
            $items = [];
        }

        $this->parser->addData([
            'response' => [
                'data' => [
                    'items' => $items,
                    'pagination' => [
                        'totalItems' => $dataProvider->getTotalItems(),
                        'currentPage' => $dataProvider->getCurrentPage(),
                        'totalPages' => $dataProvider->getTotalPages(),
                        'pageSize' => $items ? $dataProvider->getCurrentPageSize() : 0,
                    ]
                ]
            ]
        ]);

        $body = $this->parser->replace($this->data->result);
        if (is_array($body)) {
            $body = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $headers = $this->parser->replace($this->data->getHeaders());

        return new Response(
            status: $this->data->status,
            headers: $headers,
            body: $body
        );
    }
}