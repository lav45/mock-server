<?php declare(strict_types=1);

namespace lav45\MockServer\Infrastructure\Factory;

use Faker\Generator;
use lav45\MockServer\Application\DTO\Request as RequestDTO;
use lav45\MockServer\Domain\Service\Parser as ParserInterface;
use lav45\MockServer\Infrastructure\Service\Parser\Faker;
use lav45\MockServer\Infrastructure\Service\Parser\Group;
use lav45\MockServer\Infrastructure\Service\Parser\Param;

final readonly class Parser
{
    private Faker $fakerParser;
    private Param $paramParser;

    public function __construct(
        private Generator $faker,
        array             $env = [],
    ) {
        $this->fakerParser = new Faker($this->faker);
        $this->paramParser = new Param([
            'env' => $this->fakerParser->replace($env),
        ]);
    }

    public function create(RequestDTO $request): ParserInterface
    {
        $paramParser = $this->paramParser->withData(['request' => (array)$request]);

        return new Group($paramParser, $this->fakerParser);
    }
}
