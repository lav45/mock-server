<?php

namespace lav45\MockServer\middlewares;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use lav45\MockServer\components\RequestHelper;
use lav45\MockServer\mock\MockResponseProxy;

/**
 * Class ResponseProxyMiddleware
 * @package lav45\MockServer\middlewares
 */
class ResponseProxyMiddleware implements Middleware
{
    /**
     * @param MockResponseProxy $mockResponseProxy
     */
    public function __construct(private readonly MockResponseProxy $mockResponseProxy)
    {
    }

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     * @return Response
     * @throws BufferException
     * @throws StreamException
     * @throws \Amp\Http\Server\ClientException
     * @throws GuzzleException
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Response
    {
        if (empty($this->mockResponseProxy->url)) {
            return $requestHandler->handleRequest($request);
        }

        $method = $request->getMethod();
        $url = RequestHelper::replaceAttributes($request, $this->mockResponseProxy->url);

        $options = $this->mockResponseProxy->options;
        $options[RequestOptions::QUERY] = $request->getUri()->getQuery();
        $options[RequestOptions::HEADERS] ??= [];
        $options[RequestOptions::HEADERS] += $this->getHeaders($request->getHeaders());

        if ($method === 'POST') {
            $contentType = $request->getHeader('content-type') ?? '';
            $buffer = $request->getBody()->buffer();
            [$formData, $body] = $this->parseBodyParams($contentType, $buffer);
            if ($formData) {
                $options[RequestOptions::FORM_PARAMS] = $formData;
            } else {
                $options[RequestOptions::BODY] = $body;
            }
        }

        try {
            $response = (new Client())->request($method, $url, $options);
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
        }

        return new Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody()->getContents()
        );
    }

    /**
     * @param string $contentType
     * @param string $buffer
     * @return array
     */
    protected function parseBodyParams($contentType, $buffer)
    {
        $boundary = $this->parseContentBoundary($contentType);
        if ($boundary === null) {
            return [[], $buffer];
        }
        parse_str($buffer, $formData);
        return [$formData, null];
    }

    /**
     * @param string $contentType
     * @return string|null
     */
    private function parseContentBoundary(string $contentType): ?string
    {
        if (\strncmp(
                $contentType,
                "application/x-www-form-urlencoded",
                \strlen("application/x-www-form-urlencoded"),
            ) === 0) {
            return '';
        }

        if (!\preg_match(
            '#^\s*multipart/(?:form-data|mixed)(?:\s*;\s*boundary\s*=\s*("?)([^"]*)\1)?$#',
            $contentType,
            $matches,
        )) {
            return null;
        }

        return $matches[2];
    }

    /**
     * @param array $headers
     * @return array
     */
    protected function getHeaders($headers)
    {
        unset(
            $headers['host'],
            $headers['content-length'],
        );
        return $headers;
    }
}