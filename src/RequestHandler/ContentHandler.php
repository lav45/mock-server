<?php declare(strict_types=1);

namespace lav45\MockServer\RequestHandler;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\Mock\Response\Content;

final readonly class ContentHandler implements RequestHandler
{
    public function __construct(
        private Content   $content,
        private EnvParser $parser,
    )
    {
    }

    public function handleRequest(Request $request): Response
    {
        if ($this->content->getType() === Content::TYPE_JSON) {
            $json = $this->content->getJson();
            $json = $this->parser->replace($json);
            $body = json_encode($json, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $body = $this->parser->replace($this->content->getText());
        }

        return new Response(
            $this->content->status,
            $this->content->getHeaders(),
            $body,
        );
    }
}