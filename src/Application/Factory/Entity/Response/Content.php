<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Factory\Entity\Response;

use lav45\MockServer\Application\DTO\Mock\v1\Response\Content as ContentDTO;
use lav45\MockServer\Domain\Entity\Response\Content as ContentEntity;
use lav45\MockServer\Domain\Factory\Response\HttpHeaders as HttpHeadersFactory;
use lav45\MockServer\Domain\Service\Parser;
use lav45\MockServer\Domain\ValueObject\Response\Body;
use lav45\MockServer\Domain\ValueObject\Response\Delay;
use lav45\MockServer\Domain\ValueObject\Response\HttpStatus;

final readonly class Content
{
    public function __construct(
        private ContentDTO   $data,
        private float|string $delay,
        private Parser       $parser
    )
    {
    }

    public function create(): ContentEntity
    {
        $delay = Delay::new($this->parser->replace($this->delay));
        $status = new HttpStatus((int)$this->parser->replace($this->data->status));

        $headers = HttpHeadersFactory::new($this->parser, $this->data->headers, isset($this->data->json));

        $body = Body::from(
            json: $this->parser->replace($this->data->json),
            text: $this->parser->replace($this->data->text)
        );

        return new ContentEntity(
            delay: $delay,
            status: $status,
            headers: $headers,
            body: $body,
        );
    }
}