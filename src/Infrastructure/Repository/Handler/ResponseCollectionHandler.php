<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

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

final readonly class ResponseCollectionHandler implements Handler
{
    public const string TYPE = 'data';

    public function __construct(
        private Parser          $parser,
        private LoggerInterface $logger,
    ) {}

    private function getData(array $data): array
    {
        $response = ArrayHelper::getValue($data, 'response', []);
        if (isset($response['type']) && $response['type'] === self::TYPE) {
            return $response;
        }
        if (isset($response[self::TYPE])) { // TODO deprecated
            $this->logger->info("Data:\n" . \json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $this->logger->warning("Option `response." . self::TYPE . "` is deprecated, you can use `response.type` = '" . self::TYPE . "' or run `upgrade` script.");

            $result = $response[self::TYPE];
            if (isset($response['delay'])) {
                $result['delay'] = $response['delay'];
            }
            return $result;
        }
        throw new \InvalidArgumentException('Invalid data type!');
    }

    public function handle(array $data, Request $request): Response
    {
        $data = $this->getData($data);

        $start = new Response\Start($request->start);

        $delay = (new DelayFactory($this->parser))->create($data, 'delay');

        $status = (new StatusFactory($this->parser))->create($data, 'status');

        $dataItems = (new ItemsFactory($this->parser))->from($data, 'json', 'file');

        $pageParam = ArrayHelper::getValue($data, 'pagination.pageParam', 'page');
        $pageSizeParam = ArrayHelper::getValue($data, 'pagination.pageSizeParam', 'per-page');
        $defaultPageSize = ArrayHelper::getValue($data, 'pagination.defaultPageSize', 20);

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
                'data' => [ // TODO deprecated
                    'items' => $items,
                    'pagination' => [
                        'totalItems' => $totalItems,
                        'currentPage' => $currentPage,
                        'totalPages' => $totalPages,
                        'pageSize' => $pageSize,
                    ],
                ],
                'items' => $items,
                'pagination' => [
                    'totalItems' => $totalItems,
                    'currentPage' => $currentPage,
                    'totalPages' => $totalPages,
                    'pageSize' => $pageSize,
                ],
            ],
        ]);

        $headers = (new HeadersFactory(
            parser: $parser,
            logger: $this->logger,
            withJson: true,
        ))->create(
            data: $data,
            path: 'headers',
        );

        $result = ArrayHelper::getValue($data, 'result', '{{response.items}}');
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
