<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository;

use Lav45\MockServer\Application\Query\Request\Repository as RequestRepository;
use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Mock;
use Lav45\MockServer\Infrastructure\Parser\ParserFactory;

final readonly class Repository implements RequestRepository
{
    private DataMapper $dataMapper;

    public function __construct(
        private ParserFactory $parserFactory,
        private array         $data,
    ) {
        $this->dataMapper = new DataMapper();
    }

    public function find(Request $request): Mock
    {
        return $this->dataMapper->toModel(
            parser: $this->parserFactory->create($request),
            data: $this->data,
            request: $request,
        );
    }
}
