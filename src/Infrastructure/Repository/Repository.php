<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository;

use Lav45\MockServer\Application\Query\Request\Repository as RequestRepository;
use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Mock;
use Lav45\MockServer\Infrastructure\Parser\DataParser;

final readonly class Repository implements RequestRepository
{
    private DataMapper $dataMapper;

    public function __construct(
        private DataParser $parser,
        private array      $data,
    ) {
        $this->dataMapper = new DataMapper();
    }

    public function find(Request $request): Mock
    {
        $parser = $this->parser->withData([
            'request' => $request->toArray(),
            'env' => $this->parser->replace($this->data['env'] ?? []),
        ]);

        return $this->dataMapper->toModel(
            parser: $parser,
            data: $this->data,
            request: $request,
        );
    }
}
