<?php declare(strict_types=1);

namespace Lav45\MockServer\Router\MockFactory;

use Lav45\MockServer\Http\RequestData;
use Lav45\MockServer\Parser\VariableParser;

final readonly class RequestParserContext
{
    public function __construct(
        private VariableParser $parser,
    ) {}

    public function create(RequestData $request, array $data): VariableParser
    {
        return $this->parser->withData([
            'request' => $request->toArray(),
            'env' => $this->parser->replace(
                $data['env'] ?? [],
            ),
        ]);
    }
}
