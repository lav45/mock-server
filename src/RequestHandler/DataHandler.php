<?php

namespace lav45\MockServer\RequestHandler;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\Mock\Response\Data;
use lav45\MockServer\RequestHelper;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PaginatorException;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

/**
 * Class DataHandler
 * @package lav45\MockServer\RequestHandler
 */
class DataHandler implements RequestHandler
{
    /**
     * @param Data $data
     * @param EnvParser $parser
     */
    public function __construct(
        private readonly Data      $data,
        private readonly EnvParser $parser
    )
    {
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \JsonException
     */
    public function handleRequest(Request $request): Response
    {
        $helper = new RequestHelper($request);
        $pagination = $this->data->getPagination();
        $pageSize = $helper->get($pagination->pageSizeParam, $pagination->defaultPageSize);
        $currentPage = $helper->get($pagination->pageParam, 1);

        $dataReader = new IterableDataReader($this->data->getJson());
        $dataProvider = (new OffsetPaginator($dataReader))
            ->withPageSize($pageSize)
            ->withCurrentPage($currentPage);

        try {
            $data = $this->parser->replace($dataProvider->read());
        } catch (PaginatorException $exception) {
            return new Response(
                status: HttpStatus::NOT_FOUND,
                body: $exception->getMessage()
            );
        }

        $data = array_values($data);
        $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return new Response(
            $this->data->status,
            $this->data->getHeaders(),
            $body,
        );
    }
}