<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Parser;

use Faker\Generator;
use Lav45\MockServer\Application\Query\Request\Request;

final readonly class ParserFactory
{
    public function __construct(
        private Generator $faker,
        private array     $env,
    ) {}

    public function create(Request $request): GroupParser
    {
        $fakerParser = new FakerParser($this->faker);
        $env = $fakerParser->replace($this->env);

        $envParser = new EnvParser();
        $env = $envParser->replace($env);

        $paramParser = new ParamParser([
            'request' => (array)$request,
            'env' => $env,
        ]);

        return new GroupParser(
            $envParser,
            $fakerParser,
            $paramParser,
        );
    }
}
