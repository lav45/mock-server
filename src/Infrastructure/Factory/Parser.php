<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Factory;

use Faker\Generator;
use Lav45\MockServer\Application\Data\Request as RequestData;
use Lav45\MockServer\Domain\Service\Parser as ParserInterface;
use Lav45\MockServer\Infrastructure\Service\Parser\Faker;
use Lav45\MockServer\Infrastructure\Service\Parser\Group;
use Lav45\MockServer\Infrastructure\Service\Parser\Param;

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
