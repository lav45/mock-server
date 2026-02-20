<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Parser;

use Faker\Generator;

final readonly class ParserFactory implements ParserFactoryInterface
{
    public function __construct(
        private Generator $faker,
    ) {}

    public function create(): DataParser
    {
        $fakerParser = new FakerParser($this->faker);
        $envParser = new EnvParser($fakerParser);
        $dateParse = new DateParse($envParser);
        return new ParamParser($dateParse);
    }
}
