<?php declare(strict_types=1);

namespace Lav45\MockServer\Parser;

use Faker\Factory as FakerFactory;

final readonly class ParserFactory
{
    public function __construct(
        private string $locale,
    ) {}

    public function create(): VariableParser
    {
        return new ParamParser(
            new DateParse(
                new EnvParser(
                    new FakerParser(
                        FakerFactory::create($this->locale),
                    ),
                ),
            ),
        );
    }
}
