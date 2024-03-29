<?php declare(strict_types=1);

namespace lav45\MockServer\Request\Handler;

use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\Mock\Response\Content;
use lav45\MockServer\Request\Wrapper\RequestWrapper;

final readonly class ContentHandler implements RequestHandlerInterface
{
    public function __construct(
        private Content   $content,
        private EnvParser $parser,
    )
    {
    }

    public function handleRequest(RequestWrapper $request): Response
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