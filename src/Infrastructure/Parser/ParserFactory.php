<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Parser;

use Faker\Generator;

final readonly class ParserFactory
{
    public static function create(Generator $faker): DataParser
    {
        $fakerParser = new FakerParser($faker);
        $envParser = new EnvParser($fakerParser);
        $dateParse = new DateParse($envParser);
        return new ParamParser($dateParse);
    }
}
