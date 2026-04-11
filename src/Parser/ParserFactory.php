<?php declare(strict_types=1);

namespace Lav45\MockServer\Parser;

use Faker\Generator;

final readonly class ParserFactory
{
    public function __construct(
        private Generator $faker,
    ) {}

    public function create(): VariableParser
    {
        $fakerParser = new FakerParser($this->faker);
        $envParser = new EnvParser($fakerParser);
        $dateParse = new DateParse($envParser);
        return new ParamParser($dateParse);
    }
}
