<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Factory;

use Faker\Generator;
use Lav45\MockServer\Application\Data\Request as RequestData;
use Lav45\MockServer\Domain\Service\Parser as ParserInterface;
use Lav45\MockServer\Infrastructure\Service\Parser\Env;
use Lav45\MockServer\Infrastructure\Service\Parser\Faker;
use Lav45\MockServer\Infrastructure\Service\Parser\Group;
use Lav45\MockServer\Infrastructure\Service\Parser\Param;

final readonly class Parser
{
    public function __construct(
        private Generator $faker,
        private array     $env = [],
    ) {}

    public function create(RequestData $request): ParserInterface
    {
        $fakerParser = new Faker($this->faker);

        $env = $fakerParser->replace($this->env);

        $paramParser = new Param([
            'request' => (array)$request,
            'env' => $env,
        ]);

        $envParser = new Env();

        return new Group(
            $paramParser,
            $envParser,
            $fakerParser,
        );
    }
}
