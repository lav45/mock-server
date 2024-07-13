<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Factory\Entity\Response;

use Lav45\MockServer\Application\Data\Mock\v1\Response\Data as DataData;
use Lav45\MockServer\Domain\Entity\Response\Collection as CollectionEntity;
use Lav45\MockServer\Domain\Factory\Response\Body as BodyFactory;
use Lav45\MockServer\Domain\Factory\Response\HttpHeaders as HttpHeadersFactory;
use Lav45\MockServer\Domain\Service\Parser;
use Lav45\MockServer\Domain\ValueObject\Response\Body;
use Lav45\MockServer\Domain\ValueObject\Response\Delay;
use Lav45\MockServer\Domain\ValueObject\Response\HttpStatus;
use Lav45\MockServer\Domain\ValueObject\Response\Pagination;

final readonly class Collection
{
    public function __construct(
        private DataData     $data,
        private float|string $delay,
        private Parser       $parser,
    ) {}

    public function create(): CollectionEntity
    {
        $delay = Delay::new($this->parser->replace($this->delay));
        $status = new HttpStatus((int)$this->parser->replace($this->data->status));
        $headers = new HttpHeadersFactory($this->parser, $this->data->headers, true);
        $body = new BodyFactory($this->data->result, $this->parser);

        $pagination = new Pagination(
            pageParam: $this->data->pagination->pageParam,
            pageSizeParam: $this->data->pagination->pageSizeParam,
            defaultPageSize: $this->data->pagination->defaultPageSize,
        );

        $items = Body::from(
            json: $this->parser->replace($this->data->json),
            file: $this->data->file,
        )->toArray();

        return new CollectionEntity(
            delay: $delay,
            status: $status,
            headers: $headers,
            body: $body,
            pagination: $pagination,
            items: $items,
        );
    }
}
