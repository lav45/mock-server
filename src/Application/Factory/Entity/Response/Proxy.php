<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Factory\Entity\Response;

use lav45\MockServer\Application\Data\Mock\v1\Response\Proxy as ProxyData;
use lav45\MockServer\Domain\Entity\Response\Proxy as ProxyEntity;
use lav45\MockServer\Domain\Factory\Response\HttpHeaders as HttpHeadersFactory;
use lav45\MockServer\Domain\Factory\Response\Url as UrlFactory;
use lav45\MockServer\Domain\Service\Parser;
use lav45\MockServer\Domain\ValueObject\Response\Body;
use lav45\MockServer\Domain\ValueObject\Response\Delay;

final readonly class Proxy
{
    public function __construct(
        private ProxyData    $data,
        private float|string $delay,
        private Parser       $parser,
    ) {}

    public function create(): ProxyEntity
    {
        $delay = Delay::new($this->parser->replace($this->delay));
        $url = new UrlFactory($this->parser->replace($this->data->url));
        $content = Body::new($this->parser->replace($this->data->content));

        $headers = $this->data->options['headers'] ?? $this->data->headers;
        $headers = HttpHeadersFactory::new($this->parser, $headers, \is_array($this->data->content));

        return new ProxyEntity(
            delay: $delay,
            url: $url,
            headers: $headers,
            content: $content,
        );
    }
}
