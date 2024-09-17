<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Factory;

use Lav45\MockServer\Application\Data\Mock\v1\Mock as MockData;
use Lav45\MockServer\Application\Data\Mock\v1\Request;
use Lav45\MockServer\Application\Data\Mock\v1\Response;
use Lav45\MockServer\Application\Data\Mock\v1\Response\Content;
use Lav45\MockServer\Application\Data\Mock\v1\Response\Data;
use Lav45\MockServer\Application\Data\Mock\v1\Response\Data\Pagination;
use Lav45\MockServer\Application\Data\Mock\v1\Response\Proxy;
use Lav45\MockServer\Application\Data\Mock\v1\Webhook;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;

final readonly class Mock
{
    public static function create(array $data): MockData
    {
        $env = ArrayHelper::getValue($data, 'env', []);

        $request = new Request(
            url: ArrayHelper::getValue($data, 'request.url', '/'),
            method: ArrayHelper::getValue($data, 'request.method', ['GET']),
        );

        $responseContent = null;
        $responseProxy = null;
        $responseData = null;
        if (isset($data['response']['proxy'])) {
            $responseProxy = new Proxy(
                url: ArrayHelper::getValue($data, 'response.proxy.url'),
                content: ArrayHelper::getValue($data, 'response.proxy.content'),
                headers: ArrayHelper::getValue($data, 'response.proxy.headers', []),
                options: ArrayHelper::getValue($data, 'response.proxy.options', []),
            );
        } elseif (isset($data['response']['data'])) {
            $pagination = new Pagination(
                pageParam: ArrayHelper::getValue($data, 'response.data.pagination.pageParam', 'page'),
                pageSizeParam: ArrayHelper::getValue($data, 'response.data.pagination.pageSizeParam', 'per-page'),
                defaultPageSize: ArrayHelper::getValue($data, 'response.data.pagination.defaultPageSize', 20),
            );
            $responseData = new Data(
                pagination: $pagination,
                status: ArrayHelper::getValue($data, 'response.data.status', 200),
                headers: ArrayHelper::getValue($data, 'response.data.headers', []),
                result: ArrayHelper::getValue($data, 'response.data.result', '{{response.data.items}}'),
                json: ArrayHelper::getValue($data, 'response.data.json', []),
                file: ArrayHelper::getValue($data, 'response.data.file'),
            );
        } else {
            $responseContent = new Content(
                status: ArrayHelper::getValue($data, 'response.content.status', 200),
                headers: ArrayHelper::getValue($data, 'response.content.headers', []),
                text: ArrayHelper::getValue($data, 'response.content.text', ''),
                json: ArrayHelper::getValue($data, 'response.content.json'),
            );
        }

        $responseDelay = ArrayHelper::getValue($data, 'response.delay', 0.0);

        $response = new Response(
            content: $responseContent,
            proxy: $responseProxy,
            data: $responseData,
            delay: $responseDelay,
        );

        $webhooks = [];
        $webhooksData = ArrayHelper::getValue($data, 'webhooks', []);
        foreach ($webhooksData as $webhook) {
            $webhooks[] = new Webhook(
                url: ArrayHelper::getValue($webhook, 'url'),
                delay: ArrayHelper::getValue($webhook, 'delay', 0.0),
                method: ArrayHelper::getValue($webhook, 'method', 'POST'),
                headers: ArrayHelper::getValue($webhook, 'headers', []),
                json: ArrayHelper::getValue($webhook, 'json'),
                text: ArrayHelper::getValue($webhook, 'text'),
                options: ArrayHelper::getValue($webhook, 'options', []),
            );
        }

        return new MockData(
            request: $request,
            response: $response,
            webhooks: $webhooks,
            env: $env,
        );
    }
}
