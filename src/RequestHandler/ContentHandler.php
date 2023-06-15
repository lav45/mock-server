<?php

namespace lav45\MockServer\RequestHandler;

use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\Mock\Response\Content;
use lav45\MockServer\Request\WrappedRequest;

/**
 * Class ContentHandler
 * @package lav45\MockServer\RequestHandler
 */
class ContentHandler extends BaseRequestHandler
{
    /**
     * @param Content $content
     * @param EnvParser $parser
     */
    public function __construct(
        private readonly Content   $content,
        private readonly EnvParser $parser,
    )
    {
    }

    /**
     * @param WrappedRequest $request
     * @return Response
     * @throws \JsonException
     */
    public function handleWrappedRequest(WrappedRequest $request): Response
    {
        if ($this->content->getType() === Content::TYPE_JSON) {
            $json = $this->content->getJson();
            $json = $this->parser->replace($json);
            $body = json_encode($json, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $body = $this->parser->replaceAttribute($this->content->getText());
        }

        return new Response(
            $this->content->status,
            $this->content->getHeaders(),
            $body,
        );
    }
}