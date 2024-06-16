<?php declare(strict_types=1);

namespace lav45\MockServer\Domain\Factory\Response;

use lav45\MockServer\Domain\Service\Parser;
use lav45\MockServer\Domain\ValueObject\Response\Body as BodyResponse;

final readonly class Body
{
    public function __construct(
        private string|array $template,
        private Parser       $parser,
    ) {}

    public function withData(array $data): self
    {
        $data = $this->parser->replace($data);
        $parser = $this->parser->withData($data);
        return new self($this->template, $parser);
    }

    public function create(): BodyResponse
    {
        return BodyResponse::fromJson($this->parser->replace($this->template));
    }
}
