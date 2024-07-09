<?php declare(strict_types=1);

namespace lav45\MockServer\Infrastructure\Factory;

use Faker\Generator;
use lav45\MockServer\Application\Data\Request as RequestData;
use lav45\MockServer\Domain\Service\Parser as ParserInterface;
use lav45\MockServer\Infrastructure\Service\Parser\Faker;
use lav45\MockServer\Infrastructure\Service\Parser\Group;
use lav45\MockServer\Infrastructure\Service\Parser\Param;

final readonly class Parser
{
    private Faker $fakerParser;

    public function __construct(
        private Generator $faker,
        private array     $env = [],
    ) {
        $this->fakerParser = new Faker($this->faker);
    }

    public function create(RequestData $request): ParserInterface
    {
        $env = $this->fakerParser->replace($this->env);

        $paramParser = new Param([
            'request' => (array)$request,
            'env' => $env,
        ]);

        return new Group($paramParser, $this->fakerParser);
    }
}
